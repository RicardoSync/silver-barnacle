<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/whatsapp_helper.php';

// Conexión a BD
$con = (new Conexion())->conectar();

// Función auxiliar para ping
function isOnline($ip) {
    if (empty($ip)) return false;
    $ip_escaped = escapeshellarg(trim($ip));
    $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $ping = $isWindows ? shell_exec("ping -n 1 -w 1000 {$ip_escaped} 2>&1") : shell_exec("ping -c 1 -W 1 {$ip_escaped} 2>&1");
    if (empty($ping)) return false;

    return (
        strpos($ping, 'TTL=') !== false || strpos($ping, 'ttl=') !== false ||
        strpos($ping, '1 received') !== false || strpos($ping, '1 packets received') !== false ||
        strpos($ping, '1 recibidos') !== false ||
        preg_match('/(?:time|tiempo)[=<]([\d\.]+)\s*ms/i', $ping) ||
        preg_match('/rtt min\/avg\/max/i', $ping)
    );
}

// Obtener contactos de alerta activos
$stmtContactos = $con->query("SELECT telefono FROM contactos_alerta WHERE estado = 1");
$contactos = $stmtContactos->fetchAll(PDO::FETCH_COLUMN);

// Combinar Mikrotiks y Equipos en un solo arreglo
$nodos = [];

$stmtMk = $con->query("SELECT id, nombre, ip_address FROM mikrotiks WHERE estado_actual = 1");
while ($row = $stmtMk->fetch(PDO::FETCH_ASSOC)) {
    $nodos[] = [
        'id' => $row['id'],
        'nombre' => $row['nombre'],
        'ip' => $row['ip_address'],
        'tipo' => 'mikrotik'
    ];
}

$stmtEq = $con->query("SELECT id, nombre, ip_address FROM equipos WHERE estado = 1");
while ($row = $stmtEq->fetch(PDO::FETCH_ASSOC)) {
    $nodos[] = [
        'id' => $row['id'],
        'nombre' => $row['nombre'],
        'ip' => $row['ip_address'],
        'tipo' => 'equipo'
    ];
}

// Obtener plantillas de alerta activas ordenadas por minutos
$stmtPlantillas = $con->query("SELECT id, minutos, mensaje FROM plantillas_alerta WHERE estado = 1 ORDER BY minutos ASC");
$plantillas = $stmtPlantillas->fetchAll(PDO::FETCH_ASSOC);

// Consultas preparadas para el historial
$stmtGetCaida = $con->prepare("
    SELECT id, fecha_caida, 
           TIMESTAMPDIFF(MINUTE, fecha_caida, NOW()) as minutos 
    FROM historial_caidas 
    WHERE nodo_id = ? AND tipo_nodo = ? AND estado = 'en_curso'
");

$stmtInsertCaida = $con->prepare("
    INSERT INTO historial_caidas (nodo_id, tipo_nodo, nombre_nodo, estado) 
    VALUES (?, ?, ?, 'en_curso')
");

$stmtCheckNotif = $con->prepare("
    SELECT 1 FROM historial_notificaciones_caidas 
    WHERE historial_caida_id = ? AND plantilla_alerta_id = ?
");

$stmtInsertNotif = $con->prepare("
    INSERT INTO historial_notificaciones_caidas (historial_caida_id, plantilla_alerta_id) 
    VALUES (?, ?)
");

$stmtCountNotif = $con->prepare("
    SELECT COUNT(*) FROM historial_notificaciones_caidas 
    WHERE historial_caida_id = ?
");

$stmtResolverCaida = $con->prepare("
    UPDATE historial_caidas 
    SET fecha_recuperacion = NOW(), 
        duracion_minutos = TIMESTAMPDIFF(MINUTE, fecha_caida, NOW()), 
        estado = 'resuelta' 
    WHERE nodo_id = ? AND tipo_nodo = ? AND estado = 'en_curso'
");

// Iterar y procesar cada nodo
foreach ($nodos as $nodo) {
    $online = isOnline($nodo['ip']);
    
    // Obtener si hay una caída en curso
    $stmtGetCaida->execute([$nodo['id'], $nodo['tipo']]);
    $caida = $stmtGetCaida->fetch(PDO::FETCH_ASSOC);

    if (!$online) {
        // EL NODO ESTÁ CAÍDO
        if (!$caida) {
            // Registrar nueva caída (aún no notificamos)
            $stmtInsertCaida->execute([$nodo['id'], $nodo['tipo'], $nodo['nombre']]);
        } else {
            // Ya estaba caído, comprobamos tiempos de plantillas dinámicas
            $minutos = (int)$caida['minutos'];
            
            foreach ($plantillas as $tpl) {
                if ($minutos >= (int)$tpl['minutos']) {
                    // Verificar si ya se envió notificación para esta plantilla
                    $stmtCheckNotif->execute([$caida['id'], $tpl['id']]);
                    $yaNotificado = $stmtCheckNotif->fetchColumn();
                    
                    if (!$yaNotificado) {
                        // Reemplazar marcadores en el mensaje de la plantilla
                        $mensaje = str_ireplace(
                            ['%nombre%', '%tipo%', '%minutos%'],
                            [$nodo['nombre'], $nodo['tipo'], $tpl['minutos']],
                            $tpl['mensaje']
                        );
                        
                        foreach ($contactos as $telefono) {
                            enviarNotificacionCustomWhatsApp($con, $telefono, $mensaje);
                        }
                        
                        // Registrar que ya se notificó esta plantilla
                        $stmtInsertNotif->execute([$caida['id'], $tpl['id']]);
                    }
                }
            }
        }
    } else {
        // EL NODO ESTÁ ONLINE
        if ($caida) {
            // Acaba de recuperarse
            $stmtResolverCaida->execute([$nodo['id'], $nodo['tipo']]);
            
            // Si llegamos a notificar al menos una caída, notificamos la recuperación
            $stmtCountNotif->execute([$caida['id']]);
            $totalNotificaciones = (int)$stmtCountNotif->fetchColumn();
            
            if ($totalNotificaciones > 0) {
                // Volvemos a calcular los minutos exactos para el mensaje
                $duracionSql = $con->query("SELECT duracion_minutos FROM historial_caidas WHERE id = " . $caida['id']);
                $duracion = $duracionSql->fetchColumn();
                
                $mensaje = "✅ *RECUPERACIÓN DE NODO*\n\nEl nodo *{$nodo['nombre']}* ({$nodo['tipo']}) ha regresado a la normalidad.\n\n⏱️ Tiempo fuera de línea: {$duracion} minutos.";
                
                foreach ($contactos as $telefono) {
                    enviarNotificacionCustomWhatsApp($con, $telefono, $mensaje);
                }
            }
        }
    }
}
?>
