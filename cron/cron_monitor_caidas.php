<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/whatsapp_helper.php';

// Conexión a BD
$con = (new Conexion())->conectar();

// Función auxiliar para ping
function isOnline($ip) {
    if (empty($ip)) return false;
    // Ejecutar ping de 1 paquete, con timeout de 1 segundo
    $ping = shell_exec("ping -c 1 -W 1 " . escapeshellarg($ip) . " 2>&1");
    // Si la salida contiene "1 received" o "1 packets received", está online
    return (strpos($ping, '1 received') !== false || strpos($ping, '1 packets received') !== false);
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

// Consultas preparadas para el historial
$stmtGetCaida = $con->prepare("
    SELECT id, fecha_caida, notificado_3m, notificado_30m, 
           TIMESTAMPDIFF(MINUTE, fecha_caida, NOW()) as minutos 
    FROM historial_caidas 
    WHERE nodo_id = ? AND tipo_nodo = ? AND estado = 'en_curso'
");

$stmtInsertCaida = $con->prepare("
    INSERT INTO historial_caidas (nodo_id, tipo_nodo, nombre_nodo, estado) 
    VALUES (?, ?, ?, 'en_curso')
");

$stmtUpdateNotificado3m = $con->prepare("UPDATE historial_caidas SET notificado_3m = 1 WHERE id = ?");
$stmtUpdateNotificado30m = $con->prepare("UPDATE historial_caidas SET notificado_30m = 1 WHERE id = ?");

$stmtResolverCaida = $con->prepare("
    UPDATE historial_caidas 
    SET fecha_recuperacion = NOW(), 
        duracion_minutos = TIMESTAMPDIFF(MINUTE, fecha_caida, NOW()), 
        estado = 'resuelta' 
    WHERE id = ?
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
            // Ya estaba caído, comprobamos tiempos
            $minutos = (int)$caida['minutos'];
            
            // Notificar 3 minutos
            if ($minutos >= 3 && $caida['notificado_3m'] == 0) {
                $mensaje = "🚨 *ALERTA DE CAÍDA*\n\nEl nodo *{$nodo['nombre']}* ({$nodo['tipo']}) se encuentra CAÍDO. Ya superó los 3 minutos sin respuesta.";
                
                foreach ($contactos as $telefono) {
                    enviarNotificacionCustomWhatsApp($con, $telefono, $mensaje);
                }
                
                $stmtUpdateNotificado3m->execute([$caida['id']]);
            }
            
            // Notificar 30 minutos
            if ($minutos >= 30 && $caida['notificado_30m'] == 0) {
                $mensaje = "🆘 *ALERTA CRÍTICA*\n\nEl nodo *{$nodo['nombre']}* ({$nodo['tipo']}) lleva más de 30 MINUTOS CAÍDO. ¡Se requiere atención inmediata!";
                
                foreach ($contactos as $telefono) {
                    enviarNotificacionCustomWhatsApp($con, $telefono, $mensaje);
                }
                
                $stmtUpdateNotificado30m->execute([$caida['id']]);
            }
        }
    } else {
        // EL NODO ESTÁ ONLINE
        if ($caida) {
            // Acaba de recuperarse
            $stmtResolverCaida->execute([$caida['id']]);
            
            // Si llegamos a notificar la caída (minutos >= 3), notificamos la recuperación
            if ($caida['notificado_3m'] == 1) {
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
