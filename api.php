<?php
header('Content-Type: application/json');
require_once __DIR__ . '/DAO/MikrotikDAO.php';
require_once __DIR__ . '/vendor/autoload.php';

use RouterOS\Client;
use RouterOS\Exceptions\Exception;

$action = isset($_GET['action']) ? $_GET['action'] : '';
$dao = new MikrotikDAO();

switch ($action) {
    case 'listar':
        // 1. Lista de mikrotik con nombre, ip, puerto, estado activo o offline
        $datos = $dao->listarActivos();
        $array = array();
        foreach ($datos as $row) {
            $ip = escapeshellarg($row['ip_address']);
            $output = shell_exec("ping -c 1 -W 1 $ip 2>&1");
            $estado = 'offline';
            if (preg_match('/time=([\d\.]+)\s*ms/', $output, $matches)) {
                $estado = 'activo';
            }
            
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
            $ip = escapeshellarg($data['ip_address']);
            $output = shell_exec("ping -c 1 -W 1 $ip 2>&1");
            $ping_server = -1;
            if (preg_match('/time=([\d\.]+)\s*ms/', $output, $matches)) {
                $ping_server = intval(round(floatval($matches[1])));
            }

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
                // Hacer un ping real de 1 paquete con 1 segundo de timeout
                $ip = escapeshellarg($n['ip_address']);
                $output = shell_exec("ping -c 1 -W 1 $ip 2>&1");
                $ping_real = -1;
                if (preg_match('/time=([\d\.]+)\s*ms/', $output, $matches)) {
                    $ping_real = intval(round(floatval($matches[1])));
                }
                
                $n['ultimo_ping'] = $ping_real > -1 ? $ping_real : null;
                $ping = $ping_real;
                
                $cpu = $n['cpu_uso'] !== null ? intval($n['cpu_uso']) : 0;
                
                $traf_mbps = $n['trafico_total'] !== null ? ($n['trafico_total'] / 1000000) : 0;
                $n['trafico_mbps'] = round($traf_mbps, 2);
                
                $estado = 'online';
                if ($ping == -1 || $ping == 0) {
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

    default:
        echo json_encode(array("status" => "error", "message" => "Acción no válida"));
        break;
}
