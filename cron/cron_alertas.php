<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../vendor/autoload.php';
use \RouterOS\Client;
use \RouterOS\Query;

$con = (new Conexion())->conectar();

$stmt = $con->prepare("SELECT * FROM mikrotiks WHERE estado_actual = 1");
$stmt->execute();
$mikrotiks = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($mikrotiks as $m) {
    $mikrotik_id = $m['id'];
    
    // 1. REGLA: CAÍDO (OFFLINE)
    $ip = escapeshellarg($m['ip_address']);
    $output = shell_exec("ping -c 1 -W 1 $ip 2>&1");
    $is_online = preg_match('/time=([\d\.]+)\s*ms/', $output);
    
    if (!$is_online) {
        insertarAlerta($con, $mikrotik_id, 'offline', "El router no responde a PING (Corte de comunicación)");
        continue; // Si está caído, no tiene sentido checar API
    }
    
    // 2. REGLA: LATENCIA ALTA
    if (preg_match('/time=([\d\.]+)\s*ms/', $output, $matches)) {
        $latencia = floatval($matches[1]);
        if ($latencia > 250) {
            insertarAlerta($con, $mikrotik_id, 'latencia', "Latencia crítica detectada: {$latencia}ms");
        }
    }

    // 3. REGLA: CPU ALTA (>85%)
    $stmtCPU = $con->prepare("SELECT cpu_uso FROM historico_recursos WHERE mikrotik_id = ? ORDER BY fecha_registro DESC LIMIT 1");
    $stmtCPU->execute([$mikrotik_id]);
    $recurso = $stmtCPU->fetch(PDO::FETCH_ASSOC);
    if ($recurso && $recurso['cpu_uso'] > 85) {
        insertarAlerta($con, $mikrotik_id, 'cpu', "Uso de CPU elevado: {$recurso['cpu_uso']}%");
    }

    // 4. REGLA: LOGS DE MIKROTIK
    try {
        $api = new Client([
            'host' => $m['ip_address'],
            'port' => (int) $m['puerto_api'],
            'user' => $m['usuario'],
            'pass' => $m['password'],
            'socket_timeout' => 2
        ]);
        
        $query_error = (new Query('/log/print'))->where('topics', 'error');
        $logs = $api->query($query_error)->read();
        
        if(is_array($logs)) {
            foreach($logs as $log) {
                if(isset($log['message'])) {
                    insertarAlerta($con, $mikrotik_id, 'log_mikrotik', "LOG MIKROTIK [Error]: {$log['message']}");
                }
            }
        }
        
        $query_critical = (new Query('/log/print'))->where('topics', 'critical');
        $logs_crit = $api->query($query_critical)->read();
        
        if(is_array($logs_crit)) {
            foreach($logs_crit as $log) {
                if(isset($log['message'])) {
                    insertarAlerta($con, $mikrotik_id, 'log_mikrotik', "LOG MIKROTIK [Critical]: {$log['message']}");
                }
            }
        }

    } catch (Exception $e) {
        // Si no pudo entrar por API, generamos alerta de autenticación fallida o timeout de API.
        // Pero ya tenemos el ping arriba, así que evitamos duplicar ruido.
    }
}

function insertarAlerta($con, $mikrotik_id, $tipo, $mensaje) {
    // ANTI-SPAM: Verificar si ya existe una alerta de este TIPO y MENSAJE en las últimas 2 horas
    $check = $con->prepare("SELECT id FROM alertas WHERE mikrotik_id = ? AND tipo = ? AND mensaje = ? AND fecha_registro > DATE_SUB(NOW(), INTERVAL 2 HOUR)");
    $check->execute([$mikrotik_id, $tipo, $mensaje]);
    if ($check->fetch()) {
        return; // Ya alertado recientemente, ignorar
    }
    
    $stmt = $con->prepare("INSERT INTO alertas (mikrotik_id, tipo, mensaje) VALUES (?, ?, ?)");
    $stmt->execute([$mikrotik_id, $tipo, $mensaje]);
}

echo "Alertas procesadas correctamente.";
