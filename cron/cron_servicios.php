<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../DAO/ServicioDAO.php';
require_once __DIR__ . '/../includes/whatsapp_helper.php';

$dao = new ServicioDAO();
$servicios = $dao->listarActivos();
$con = (new Conexion())->conectar();

// Obtener contactos de alerta activos
$stmtContactos = $con->query("SELECT telefono FROM contactos_alerta WHERE estado = 1");
$contactos = $stmtContactos->fetchAll(PDO::FETCH_COLUMN);

echo "Iniciando Monitoreo de Servicios (DNS, HTTP, Puertos)...\n";

foreach ($servicios as $s) {
    $id = $s['id'];
    $nombre = $s['nombre'];
    $tipo = $s['tipo'];
    $target = trim($s['target']);
    $puerto = $s['puerto'] ? intval($s['puerto']) : null;
    $umbral_ms = intval($s['umbral_ms']);

    $ms = 0;
    $codigo_http = null;
    $ip_resuelta = null;
    $estado_check = 'offline';
    $detalle = null;

    // Obtener estado anterior del servicio
    $stmtPrev = $con->prepare("SELECT estado_check FROM historico_servicios WHERE servicio_id = ? ORDER BY id DESC LIMIT 1");
    $stmtPrev->execute([$id]);
    $estadoAnterior = $stmtPrev->fetchColumn();

    if ($tipo === 'dns') {
        $start = microtime(true);
        $records = @dns_get_record($target, DNS_A);
        $end = microtime(true);

        if ($records && !empty($records[0]['ip'])) {
            $ms = intval(round(($end - $start) * 1000));
            $ip_resuelta = $records[0]['ip'];
            $estado_check = ($ms > $umbral_ms) ? 'lento' : 'online';
            $detalle = "IP: {$ip_resuelta} | Latencia DNS: {$ms}ms";
        } else {
            // Intentar con gethostbyname como fallback
            $start = microtime(true);
            $ip = gethostbyname($target);
            $end = microtime(true);
            if ($ip !== $target && filter_var($ip, FILTER_VALIDATE_IP)) {
                $ms = intval(round(($end - $start) * 1000));
                $ip_resuelta = $ip;
                $estado_check = ($ms > $umbral_ms) ? 'lento' : 'online';
                $detalle = "IP: {$ip_resuelta} | Latencia DNS: {$ms}ms";
            } else {
                $estado_check = 'offline';
                $detalle = "Fallo en la resolución DNS para '{$target}'";
            }
        }
    } elseif ($tipo === 'http') {
        $ch = curl_init();
        $url = (strpos($target, 'http://') === 0 || strpos($target, 'https://') === 0) ? $target : "http://{$target}";

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Elissa-Service-Monitor/1.0'
        ]);

        $start = microtime(true);
        $response = curl_exec($ch);
        $end = microtime(true);

        $curl_error = curl_error($ch);
        $codigo_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ip_resuelta = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
        curl_close($ch);

        $ms = intval(round(($end - $start) * 1000));

        if ($codigo_http >= 200 && $codigo_http < 400) {
            $estado_check = ($ms > $umbral_ms) ? 'lento' : 'online';
            $detalle = "HTTP {$codigo_http} OK | Tiempo: {$ms}ms";
        } elseif ($codigo_http > 0) {
            $estado_check = 'offline';
            $detalle = "Error HTTP Código {$codigo_http}";
        } else {
            $estado_check = 'offline';
            $detalle = "Error de conexión cURL: {$curl_error}";
        }
    } elseif ($tipo === 'puerto') {
        $targetHost = $target;
        $targetPort = $puerto ?? 80;

        $start = microtime(true);
        $connection = @fsockopen($targetHost, $targetPort, $errno, $errstr, 3);
        $end = microtime(true);

        if (is_resource($connection)) {
            $ms = intval(round(($end - $start) * 1000));
            fclose($connection);
            $ip_resuelta = gethostbyname($targetHost);
            $estado_check = ($ms > $umbral_ms) ? 'lento' : 'online';
            $detalle = "Puerto {$targetPort} Abierto | Conexión: {$ms}ms";
        } else {
            $estado_check = 'offline';
            $detalle = "Puerto {$targetPort} Cerrado / No responde ({$errstr})";
        }
    }

    // Registrar resultado
    $dao->registrarHistorico($id, $ms, $codigo_http, $ip_resuelta, $estado_check, $detalle);

    echo "Servicio [{$nombre}] ({$tipo}): {$estado_check} ({$ms}ms) - {$detalle}\n";

    // Alertas y Notificaciones si cambia de estado
    if ($estadoAnterior && $estadoAnterior !== 'offline' && $estado_check === 'offline') {
        // Registrar alerta en BD
        $stmtAlert = $con->prepare("INSERT INTO alertas (mikrotik_id, tipo, mensaje, estado) VALUES (0, 'servicio_offline', ?, 'no_leido')");
        $msgAlerta = "Servicio caído: {$nombre} ({$tipo} -> {$target})";
        $stmtAlert->execute([$msgAlerta]);

        // WhatsApp
        if (!empty($contactos)) {
            $msgWa = "🚨 *ALERTA DE SERVICIO CAÍDO*\n\nEl servicio *{$nombre}* ({$tipo}) dejó de responder.\n🎯 Objetivo: {$target}\n⚠️ Detalle: {$detalle}";
            foreach ($contactos as $tel) {
                enviarNotificacionCustomWhatsApp($con, $tel, $msgWa);
            }
        }
    } elseif ($estadoAnterior === 'offline' && $estado_check === 'online') {
        $stmtAlert = $con->prepare("INSERT INTO alertas (mikrotik_id, tipo, mensaje, estado) VALUES (0, 'servicio_recuperado', ?, 'no_leido')");
        $msgAlerta = "Servicio recuperado: {$nombre} ({$tipo} -> {$target})";
        $stmtAlert->execute([$msgAlerta]);

        if (!empty($contactos)) {
            $msgWa = "✅ *SERVICIO RECUPERADO*\n\nEl servicio *{$nombre}* ({$tipo}) ha vuelto a responder normalmente.\n⏱️ Latencia: {$ms}ms";
            foreach ($contactos as $tel) {
                enviarNotificacionCustomWhatsApp($con, $tel, $msgWa);
            }
        }
    }
}

echo "Monitoreo de Servicios finalizado.\n";
?>
