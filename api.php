<?php
header('Content-Type: application/json');
require_once __DIR__ . '/DAO/MikrotikDAO.php';
require_once __DIR__ . '/DAO/ServicioDAO.php';
require_once __DIR__ . '/vendor/autoload.php';

use RouterOS\Client;
use RouterOS\Exceptions\Exception;

$action = isset($_GET['action']) ? $_GET['action'] : '';
$dao = new MikrotikDAO();

if (!function_exists('helperEjecutarPing')) {
    function helperEjecutarPing($ip) {
        if (empty($ip)) return ['ms' => -1, 'online' => false];
        $ip_escaped = escapeshellarg(trim($ip));
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $cmd = $isWindows ? "ping -n 1 -w 1000 {$ip_escaped} 2>&1" : "ping -c 1 -W 1 {$ip_escaped} 2>&1";
        $output = shell_exec($cmd);

        $ms = -1;
        $online = false;

        if (!empty($output)) {
            if (preg_match('/(?:time|tiempo)[=<]([\d\.]+)\s*ms/i', $output, $matches)) {
                $ms = intval(round(floatval($matches[1])));
                $online = true;
            } elseif (preg_match('/rtt min\/avg\/max\/mdev = [\d\.]+\/([\d\.]+)\//i', $output, $matches)) {
                $ms = intval(round(floatval($matches[1])));
                $online = true;
            } elseif (strpos($output, 'TTL=') !== false || strpos($output, 'ttl=') !== false || strpos($output, '1 received') !== false || strpos($output, '1 recibidos') !== false || strpos($output, '1 packets received') !== false) {
                $ms = 0;
                $online = true;
            }
        }

        return [
            'ms' => $online ? max(0, $ms) : -1,
            'online' => $online
        ];
    }
}

switch ($action) {
    case 'listar':
        // 1. Lista de mikrotik con nombre, ip, puerto, estado activo o offline
        $datos = $dao->listarActivos();
        $array = array();
        foreach ($datos as $row) {
            $resPing = helperEjecutarPing($row['ip_address']);
            $estado = $resPing['online'] ? 'activo' : 'offline';

            $array[] = array(
                "id" => $row['id'],
                "nombre" => $row['nombre'],
                "ip_address" => $row['ip_address'],
                "puerto_api" => $row['puerto_api'],
                "estado" => $estado
            );
        }
        echo json_encode(array("status" => "success", "data" => $array));
        break;

    case 'ping':
        // 2. API que retorne el ping dentre mikrotik -> google y servidor -> mikrotik
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $data = $dao->obtenerPorId($id);
        if ($data) {
            // Ping Server -> MikroTik
            $resPing = helperEjecutarPing($data['ip_address']);
            $ping_server = $resPing['ms'];

            // Ping MikroTik -> Google
            $ping_google = -1;
            try {
                $client = new Client([
                    'host' => $data['ip_address'], 
                    'user' => $data['usuario'], 
                    'pass' => $data['password'], 
                    'port' => intval($data['puerto_api']), 
                    'timeout' => 2
                ]);
                $query = (new \RouterOS\Query('/ping'))
                    ->equal('address', '8.8.8.8')
                    ->equal('count', '1');
                $response = $client->query($query)->read();
                
                if(isset($response[0]['time'])) {
                    $time_str = $response[0]['time'];
                    if (strpos($time_str, 's') !== false && strpos($time_str, 'ms') !== false) {
                        $parts = explode('s', $time_str);
                        $ping_google = (intval($parts[0]) * 1000) + intval(str_replace('ms', '', $parts[1]));
                    } elseif (strpos($time_str, 'ms') !== false) {
                        $ping_google = intval(str_replace('ms', '', $time_str));
                    } elseif (strpos($time_str, 's') !== false) {
                        $ping_google = intval(str_replace('s', '', $time_str)) * 1000;
                    }
                }
            } catch (\Exception $e) { }

            echo json_encode(array(
                "status" => "success",
                "ping_server_mikrotik_ms" => $ping_server,
                "ping_mikrotik_google_ms" => $ping_google
            ));
        } else {
            echo json_encode(array("status" => "error", "message" => "MikroTik no encontrado"));
        }
        break;

    case 'interfaces':
        // 3. Listado de interfaces
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $data = $dao->obtenerPorId($id);
        if ($data) {
            try {
                $client = new Client(['host' => $data['ip_address'], 'user' => $data['usuario'], 'pass' => $data['password'], 'port' => intval($data['puerto_api']), 'timeout' => 3]);
                $query = new \RouterOS\Query('/interface/print');
                $response = $client->query($query)->read();
                echo json_encode(array("status" => "success", "data" => is_array($response) ? $response : []));
            } catch (\Exception $e) { 
                echo json_encode(array("status" => "error", "message" => $e->getMessage())); 
            }
        } else {
            echo json_encode(array("status" => "error", "message" => "MikroTik no encontrado"));
        }
        break;

    case 'arp':
        // 4. Listado de ARP
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $data = $dao->obtenerPorId($id);
        if ($data) {
            try {
                $client = new Client(['host' => $data['ip_address'], 'user' => $data['usuario'], 'pass' => $data['password'], 'port' => intval($data['puerto_api']), 'timeout' => 3]);
                $query = new \RouterOS\Query('/ip/arp/print');
                $response = $client->query($query)->read();
                echo json_encode(array("status" => "success", "data" => is_array($response) ? $response : []));
            } catch (\Exception $e) { 
                echo json_encode(array("status" => "error", "message" => $e->getMessage())); 
            }
        } else {
            echo json_encode(array("status" => "error", "message" => "MikroTik no encontrado"));
        }
        break;

    case 'neighbors':
        // 4. Listado de Neighbors
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $data = $dao->obtenerPorId($id);
        if ($data) {
            try {
                $client = new Client(['host' => $data['ip_address'], 'user' => $data['usuario'], 'pass' => $data['password'], 'port' => intval($data['puerto_api']), 'timeout' => 3]);
                $query = new \RouterOS\Query('/ip/neighbor/print');
                $response = $client->query($query)->read();
                echo json_encode(array("status" => "success", "data" => is_array($response) ? $response : []));
            } catch (\Exception $e) { 
                echo json_encode(array("status" => "error", "message" => $e->getMessage())); 
            }
        } else {
            echo json_encode(array("status" => "error", "message" => "MikroTik no encontrado"));
        }
        break;

    case 'estadisticas':
        // 5. Retorne estadisticas del mikrotik (trafico, pings y recursos historicos 24h)
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            try {
                $con = (new Conexion())->conectar();
                
                // Recursos 24h
                $stmt = $con->prepare("SELECT cpu_uso, ram_total, ram_libre, disco_libre, disco_total, DATE_FORMAT(fecha_registro, '%H:%i') as hora FROM historico_recursos WHERE mikrotik_id = ? AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY fecha_registro ASC");
                $stmt->execute([$id]);
                $recursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Pings 24h
                $stmt = $con->prepare("SELECT tipo, ms, DATE_FORMAT(fecha_registro, '%H:%i') as hora FROM historico_pings WHERE mikrotik_id = ? AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY fecha_registro ASC");
                $stmt->execute([$id]);
                $pings_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $pings = ['google' => [], 'servidor' => []];
                foreach ($pings_raw as $p) {
                    $pings[$p['tipo']][] = ['ms' => $p['ms'], 'hora' => $p['hora']];
                }

                // Trafico 24h
                $stmt = $con->prepare("SELECT interface, rx_bits, tx_bits, DATE_FORMAT(fecha_registro, '%H:%i') as hora FROM historico_trafico WHERE mikrotik_id = ? AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY fecha_registro ASC");
                $stmt->execute([$id]);
                $trafico_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $trafico = [];
                foreach ($trafico_raw as $t) {
                    if (!isset($trafico[$t['interface']])) {
                        $trafico[$t['interface']] = [];
                    }
                    $trafico[$t['interface']][] = ['rx' => $t['rx_bits'], 'tx' => $t['tx_bits'], 'hora' => $t['hora']];
                }

                echo json_encode(array("status" => "success", "data" => array(
                    "recursos" => $recursos,
                    "pings" => $pings,
                    "trafico" => $trafico
                )));
            } catch (\Exception $e) { 
                echo json_encode(array("status" => "error", "message" => $e->getMessage())); 
            }
        } else {
            echo json_encode(array("status" => "error", "message" => "ID inválido"));
        }
        break;

    case 'logs':
        // 6. Retorne los logs de mikrotik
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $data = $dao->obtenerPorId($id);
        if ($data) {
            try {
                $client = new Client(['host' => $data['ip_address'], 'user' => $data['usuario'], 'pass' => $data['password'], 'port' => intval($data['puerto_api']), 'timeout' => 3]);
                $query = new \RouterOS\Query('/log/print');
                $response = $client->query($query)->read();
                
                if (is_array($response)) {
                    $response = array_reverse($response);
                }
                
                echo json_encode(array("status" => "success", "data" => is_array($response) ? $response : []));
            } catch (\Exception $e) { 
                echo json_encode(array("status" => "error", "message" => $e->getMessage())); 
            }
        } else {
            echo json_encode(array("status" => "error", "message" => "MikroTik no encontrado"));
        }
        break;

    case 'recursos':
        // 7. Obtener los recursos nombre, ip, routeros, uptime, cpu, ram y disco
        // Puede leerse directamente de la DB o en tiempo real del equipo. Lo leemos de la DB como en el Dashboard.
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            try {
                $con = (new Conexion())->conectar();
                $stmt = $con->prepare("
                    SELECT m.id, m.nombre, m.ip_address, 
                           r.cpu_uso, r.ram_total, r.ram_libre, r.disco_total, r.disco_libre, r.uptime, r.version_ros, r.fecha_registro 
                    FROM mikrotiks m 
                    LEFT JOIN (
                        SELECT hr1.* FROM historico_recursos hr1
                        INNER JOIN (SELECT mikrotik_id, MAX(fecha_registro) as max_f FROM historico_recursos GROUP BY mikrotik_id) hr2 
                        ON hr1.mikrotik_id = hr2.mikrotik_id AND hr1.fecha_registro = hr2.max_f
                    ) r ON m.id = r.mikrotik_id 
                    WHERE m.id = ?
                ");
                $stmt->execute([$id]);
                $recursos = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($recursos) {
                    echo json_encode(array("status" => "success", "data" => $recursos));
                } else {
                    echo json_encode(array("status" => "error", "message" => "Sin datos"));
                }
            } catch (\Exception $e) { 
                echo json_encode(array("status" => "error", "message" => $e->getMessage())); 
            }
        } else {
            // Retornar de todos
            try {
                $con = (new Conexion())->conectar();
                $stmt = $con->prepare("
                    SELECT m.id, m.nombre, m.ip_address, 
                           r.cpu_uso, r.ram_total, r.ram_libre, r.disco_total, r.disco_libre, r.uptime, r.version_ros, r.fecha_registro 
                    FROM mikrotiks m 
                    LEFT JOIN (
                        SELECT hr1.* FROM historico_recursos hr1
                        INNER JOIN (SELECT mikrotik_id, MAX(fecha_registro) as max_f FROM historico_recursos GROUP BY mikrotik_id) hr2 
                        ON hr1.mikrotik_id = hr2.mikrotik_id AND hr1.fecha_registro = hr2.max_f
                    ) r ON m.id = r.mikrotik_id 
                    WHERE m.estado_actual = 1
                ");
                $stmt->execute();
                $nodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(array("status" => "success", "data" => $nodos));
            } catch (\Exception $e) { 
                echo json_encode(array("status" => "error", "message" => $e->getMessage())); 
            }
        }
        break;

    case 'dashboard':
        // 8. La misma información que mostramos en el noc o dashboard
        try {
            $con = (new Conexion())->conectar();
            $stmt = $con->prepare("
                SELECT m.id, m.nombre, m.ip_address,
                (SELECT ms FROM historico_pings WHERE mikrotik_id = m.id AND tipo = 'servidor' ORDER BY fecha_registro DESC LIMIT 1) as ultimo_ping,
                (SELECT cpu_uso FROM historico_recursos WHERE mikrotik_id = m.id ORDER BY fecha_registro DESC LIMIT 1) as cpu_uso,
                (SELECT SUM(rx_bits + tx_bits) FROM historico_trafico WHERE mikrotik_id = m.id AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)) as trafico_total
                FROM mikrotiks m WHERE m.estado_actual = 1
            ");
            $stmt->execute();
            $nodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totales = count($nodos);
            $online = 0;
            $offline = 0;
            $alertas = 0;

            foreach ($nodos as &$n) {
                $resPing = helperEjecutarPing($n['ip_address']);
                $ping_real = $resPing['ms'];
                $n['ultimo_ping'] = $resPing['online'] ? $ping_real : null;
                $ping = $ping_real;
                
                $cpu = $n['cpu_uso'] !== null ? intval($n['cpu_uso']) : 0;
                
                $traf_mbps = $n['trafico_total'] !== null ? ($n['trafico_total'] / 1000000) : 0;
                $n['trafico_mbps'] = round($traf_mbps, 2);
                
                $estado = 'online';
                if (!$resPing['online'] || $ping === -1) {
                    $estado = 'offline';
                    $offline++;
                } else {
                    $online++;
                    if ($cpu > 80 || $ping > 200) {
                        $estado = 'alerta';
                        $alertas++;
                    }
                }
                $n['estado_noc'] = $estado;
            }

            echo json_encode(array("status" => "success", "data" => array(
                "kpis" => ["total" => $totales, "online" => $online, "offline" => $offline, "alertas" => $alertas],
                "nodos" => $nodos
            )));
        } catch (\Exception $e) { 
            echo json_encode(array("status" => "error", "message" => $e->getMessage())); 
        }
        break;

    case 'obtener_dispositivos_ping':
        try {
            $con = (new Conexion())->conectar();
            $opciones = [];

            // Presets
            $opciones[] = [
                "id" => "preset_google",
                "nombre" => "Google DNS (8.8.8.8)",
                "ip" => "8.8.8.8",
                "categoria" => "Predefinidos"
            ];
            $opciones[] = [
                "id" => "preset_cloudflare",
                "nombre" => "Cloudflare DNS (1.1.1.1)",
                "ip" => "1.1.1.1",
                "categoria" => "Predefinidos"
            ];

            // MikroTiks
            $stmtMk = $con->query("SELECT id, nombre, ip_address FROM mikrotiks WHERE estado_actual = 1 ORDER BY nombre ASC");
            while ($m = $stmtMk->fetch(PDO::FETCH_ASSOC)) {
                $opciones[] = [
                    "id" => "mk_" . $m['id'],
                    "nombre" => "MikroTik: " . $m['nombre'] . " (" . $m['ip_address'] . ")",
                    "ip" => $m['ip_address'],
                    "categoria" => "MikroTiks"
                ];
            }

            // Equipos (ONUs, APs, etc)
            $stmtEq = $con->query("SELECT id, nombre, ip_address FROM equipos WHERE estado = 1 ORDER BY nombre ASC");
            while ($e = $stmtEq->fetch(PDO::FETCH_ASSOC)) {
                $opciones[] = [
                    "id" => "eq_" . $e['id'],
                    "nombre" => "Equipo: " . $e['nombre'] . " (" . $e['ip_address'] . ")",
                    "ip" => $e['ip_address'],
                    "categoria" => "Equipos / ONUs / APs"
                ];
            }

            echo json_encode(["status" => "success", "data" => $opciones]);
        } catch (\Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    case 'ping_batch':
        $ips_raw = isset($_REQUEST['ips']) ? $_REQUEST['ips'] : [];
        if (is_string($ips_raw)) {
            $ips_decoded = json_decode($ips_raw, true);
            if (is_array($ips_decoded)) {
                $ips_raw = $ips_decoded;
            } else {
                $ips_raw = array_filter(array_map('trim', explode(',', $ips_raw)));
            }
        }

        $resultados = [];
        if (is_array($ips_raw)) {
            foreach ($ips_raw as $ip) {
                $cleanIp = trim($ip);
                if (!empty($cleanIp)) {
                    $res = helperEjecutarPing($cleanIp);
                    $resultados[$cleanIp] = [
                        "ip" => $cleanIp,
                        "ms" => $res['ms'],
                        "online" => $res['online'],
                        "status" => $res['online'] ? 'online' : 'offline'
                    ];
                }
            }
        }

        echo json_encode(["status" => "success", "data" => $resultados]);
        break;

    case 'servicios_listar':
        $sDao = new ServicioDAO();
        $servicios = $sDao->obtenerUltimoEstadoCompleto();
        echo json_encode(array("status" => "success", "data" => $servicios));
        break;

    case 'servicios_guardar':
        $sDao = new ServicioDAO();
        $id = isset($_POST['id']) && !empty($_POST['id']) ? intval($_POST['id']) : null;
        $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
        $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : 'dns';
        $target = isset($_POST['target']) ? trim($_POST['target']) : '';
        $puerto = isset($_POST['puerto']) && $_POST['puerto'] !== '' ? intval($_POST['puerto']) : null;
        $umbral_ms = isset($_POST['umbral_ms']) && intval($_POST['umbral_ms']) > 0 ? intval($_POST['umbral_ms']) : 300;
        $estado = isset($_POST['estado']) ? intval($_POST['estado']) : 1;

        if (empty($nombre) || empty($target)) {
            echo json_encode(array("status" => "error", "message" => "El nombre y objetivo son obligatorios."));
            break;
        }

        if ($id) {
            $res = $sDao->actualizar($id, $nombre, $tipo, $target, $puerto, $umbral_ms, $estado);
        } else {
            $res = $sDao->crear($nombre, $tipo, $target, $puerto, $umbral_ms);
        }

        if ($res) {
            echo json_encode(array("status" => "success", "message" => "Servicio guardado con éxito."));
        } else {
            echo json_encode(array("status" => "error", "message" => "Error al guardar el servicio en la BD."));
        }
        break;

    case 'servicios_eliminar':
        $sDao = new ServicioDAO();
        $id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
        if ($id > 0) {
            if ($sDao->eliminar($id)) {
                echo json_encode(array("status" => "success", "message" => "Servicio eliminado correctamente."));
            } else {
                echo json_encode(array("status" => "error", "message" => "No se pudo eliminar el servicio."));
            }
        } else {
            echo json_encode(array("status" => "error", "message" => "ID no válido."));
        }
        break;

    case 'servicios_historico':
        $sDao = new ServicioDAO();
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $horas = isset($_GET['horas']) ? intval($_GET['horas']) : 24;
        $historico = $sDao->obtenerHistoricoGrafica($id, $horas);
        echo json_encode(array("status" => "success", "data" => $historico));
        break;

    case 'servicios_resumen_dashboard':
        $sDao = new ServicioDAO();
        $resumen = $sDao->obtenerResumenDashboard();
        $servicios = $sDao->obtenerUltimoEstadoCompleto();
        echo json_encode(array("status" => "success", "kpis" => $resumen, "data" => $servicios));
        break;

    case 'trafico_servidor_local':
        $interfaces = array();
        $netDirs = @glob('/sys/class/net/*');
        if (!empty($netDirs)) {
            foreach ($netDirs as $dir) {
                $iface = basename($dir);
                $rx_file = "$dir/statistics/rx_bytes";
                $tx_file = "$dir/statistics/tx_bytes";
                if (@file_exists($rx_file) && @file_exists($tx_file)) {
                    $rx_bytes = floatval(trim(@file_get_contents($rx_file)));
                    $tx_bytes = floatval(trim(@file_get_contents($tx_file)));
                    $interfaces[] = array(
                        "interface" => $iface,
                        "rx_bytes" => $rx_bytes,
                        "tx_bytes" => $tx_bytes
                    );
                }
            }
        }
        
        if (empty($interfaces)) {
            $rawDev = '';
            if (@file_exists('/proc/net/dev') && @is_readable('/proc/net/dev')) {
                $rawDev = @file_get_contents('/proc/net/dev');
            }
            if (empty($rawDev) && function_exists('shell_exec')) {
                $rawDev = @shell_exec('cat /proc/net/dev 2>&1');
            }
            
            if (!empty($rawDev)) {
                $lines = explode("\n", trim($rawDev));
                for ($i = 2; $i < count($lines); $i++) {
                    $line = trim($lines[$i]);
                    if (empty($line)) continue;
                    $parts = preg_split('/\s+/', $line);
                    if (count($parts) >= 10) {
                        $iface = rtrim($parts[0], ':');
                        $rx_bytes = floatval($parts[1]);
                        $tx_bytes = floatval($parts[9]);
                        $interfaces[] = array(
                            "interface" => $iface,
                            "rx_bytes" => $rx_bytes,
                            "tx_bytes" => $tx_bytes
                        );
                    }
                }
            }
        }

        echo json_encode(array(
            "status" => "success",
            "timestamp" => microtime(true),
            "interfaces" => $interfaces
        ));
        break;

    default:
        echo json_encode(array("status" => "error", "message" => "Acción no válida"));
        break;
}
