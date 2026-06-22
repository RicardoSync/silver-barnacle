<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../DAO/MikrotikDAO.php';
require_once __DIR__ . '/../DTO/MikrotikDTO.php';
require_once __DIR__ . '/../vendor/autoload.php';

use RouterOS\Client;
use RouterOS\Exceptions\Exception;

$action = isset($_GET['action']) ? $_GET['action'] : '';
$dao = new MikrotikDAO();

switch ($action) {
    case 'listar':
        $datos = $dao->listarActivos();
        $array = array();
        foreach ($datos as $row) {
            $acciones = '
                <button class="btn btn-sm btn-info text-white" onclick="loadView(\'mikrotik/detalles\', {id: '.$row['id'].'})"><i class="bi bi-eye"></i></button>
                <button class="btn btn-sm btn-primary btn-edit" onclick="editarMikrotik('.$row['id'].')"><i class="bi bi-pencil-square"></i></button>
                <button class="btn btn-sm btn-danger btn-delete" onclick="eliminarMikrotik('.$row['id'].')"><i class="bi bi-trash"></i></button>
            ';
            
            $estado = '<span class="badge bg-success">Activo</span>';
            $conexion = '<span class="badge bg-secondary status-check" data-id="'.$row['id'].'" data-status="pending"><div class="spinner-border spinner-border-sm" role="status"></div> Comprobando...</span>';
            
            $array[] = array(
                "id" => $row['id'],
                "nombre" => $row['nombre'],
                "ip_address" => $row['ip_address'],
                "puerto_api" => $row['puerto_api'],
                "estado" => $estado,
                "conexion" => $conexion,
                "acciones" => $acciones
            );
        }
        echo json_encode(array("data" => $array));
        break;

    case 'obtener':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            $data = $dao->obtenerPorId($id);
            if($data) {
                $data['password'] = ''; 
                echo json_encode(array("status" => "success", "data" => $data));
            } else {
                echo json_encode(array("status" => "error", "message" => "No encontrado"));
            }
        }
        break;

    case 'guardar':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        $dto = new MikrotikDTO();
        $dto->setNombre($_POST['nombre']);
        $dto->setIpAddress($_POST['ip_address']);
        $dto->setPuertoApi(!empty($_POST['puerto_api']) ? intval($_POST['puerto_api']) : 8728);
        $dto->setUsuario($_POST['usuario']);
        $dto->setPassword($_POST['password']);
        $dto->setLatitud(!empty($_POST['latitud']) ? $_POST['latitud'] : null);
        $dto->setLongitud(!empty($_POST['longitud']) ? $_POST['longitud'] : null);

        if ($id > 0) {
            // Actualizar
            $dto->setId($id);
            $res = $dao->actualizar($dto);
            $msg = $res ? "MikroTik actualizado correctamente" : "Error al actualizar";
        } else {
            // Insertar
            $res = $dao->insertar($dto);
            $msg = $res ? "MikroTik registrado correctamente" : "Error al registrar";
        }

        echo json_encode(array("status" => $res ? "success" : "error", "message" => $msg));
        break;

    case 'eliminar':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id > 0) {
            $res = $dao->borradoLogico($id);
            if ($res) {
                echo json_encode(array("status" => "success", "message" => "MikroTik eliminado correctamente"));
            } else {
                echo json_encode(array("status" => "error", "message" => "Error al eliminar"));
            }
        }
        break;

    case 'ping':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            $data = $dao->obtenerPorId($id);
            if ($data) {
                try {
                    $client = new Client([
                        'host'    => $data['ip_address'],
                        'user'    => $data['usuario'],
                        'pass'    => $data['password'],
                        'port'    => intval($data['puerto_api']),
                        'timeout' => 3
                    ]);
                    // If no exception was thrown, connection is successful
                    echo json_encode(array("status" => "online"));
                } catch (\Exception $e) {
                    echo json_encode(array("status" => "offline", "error" => $e->getMessage()));
                }
            } else {
                echo json_encode(array("status" => "offline", "error" => "MikroTik no encontrado"));
            }
        } else {
            echo json_encode(array("status" => "offline", "error" => "ID inválido"));
        }
        break;

    case 'get_historico':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        // Mocked response for now since CRON is not running
        echo json_encode(array("status" => "success", "data" => array(
            "cpu_uso" => rand(5, 50),
            "ram_libre" => rand(500000000, 1000000000),
            "disco_libre" => rand(1000000000, 5000000000),
            "uptime" => "2d 4h 10m"
        )));
        break;

    case 'api_interfaces':
    case 'api_arp':
    case 'api_neighbors':
    case 'api_logs':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $data = $dao->obtenerPorId($id);
        if ($data) {
            try {
                $client = new Client(['host' => $data['ip_address'], 'user' => $data['usuario'], 'pass' => $data['password'], 'port' => intval($data['puerto_api']), 'timeout' => 3]);
                $cmd = '';
                if ($action == 'api_interfaces') $cmd = '/interface/print';
                if ($action == 'api_arp') $cmd = '/ip/arp/print';
                if ($action == 'api_neighbors') $cmd = '/ip/neighbor/print';
                if ($action == 'api_logs') $cmd = '/log/print';
                
                $query = new \RouterOS\Query($cmd);
                $response = $client->query($query)->read();
                
                // Si son logs, revertir el array para mostrar los mas recientes primero
                if ($action == 'api_logs' && is_array($response)) {
                    $response = array_reverse($response);
                }
                
                echo json_encode(array("data" => is_array($response) ? $response : []));
            } catch (\Exception $e) { echo json_encode(array("data" => [])); }
        } else {
            echo json_encode(array("data" => []));
        }
        break;

    case 'api_traffic_monitor':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $interface = isset($_GET['interface']) ? $_GET['interface'] : '';
        if ($id > 0 && !empty($interface)) {
            $data = $dao->obtenerPorId($id);
            if ($data) {
                try {
                    $client = new Client(['host' => $data['ip_address'], 'user' => $data['usuario'], 'pass' => $data['password'], 'port' => intval($data['puerto_api']), 'timeout' => 3]);
                    $query = (new \RouterOS\Query('/interface/monitor-traffic'))
                        ->equal('interface', $interface)
                        ->equal('once', '');
                    $response = $client->query($query)->read();
                    
                    if (isset($response[0])) {
                        echo json_encode(array(
                            "status" => "success", 
                            "rx_bits" => isset($response[0]['rx-bits-per-second']) ? intval($response[0]['rx-bits-per-second']) : 0,
                            "tx_bits" => isset($response[0]['tx-bits-per-second']) ? intval($response[0]['tx-bits-per-second']) : 0
                        ));
                    } else {
                        echo json_encode(array("status" => "error", "message" => "Sin datos"));
                    }
                } catch (\Exception $e) { echo json_encode(array("status" => "error", "error" => $e->getMessage())); }
            } else { echo json_encode(array("status" => "error")); }
        } else { echo json_encode(array("status" => "error", "message" => "Faltan parámetros")); }
        break;

    case 'api_historico_graficas':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            try {
                $con = (new Conexion())->conectar();
                
                // Recursos 24h
                $stmt = $con->prepare("SELECT cpu_uso, ram_total, ram_libre, DATE_FORMAT(fecha_registro, '%H:%i') as hora, fecha_registro as hora_completa FROM historico_recursos WHERE mikrotik_id = ? AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY fecha_registro ASC");
                $stmt->execute([$id]);
                $recursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Pings 24h
                $stmt = $con->prepare("SELECT tipo, ms, DATE_FORMAT(fecha_registro, '%H:%i') as hora, fecha_registro as hora_completa FROM historico_pings WHERE mikrotik_id = ? AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY fecha_registro ASC");
                $stmt->execute([$id]);
                $pings_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $pings = ['google' => [], 'servidor' => []];
                foreach ($pings_raw as $p) {
                    $pings[$p['tipo']][] = ['ms' => $p['ms'], 'hora' => $p['hora'], 'hora_completa' => $p['hora_completa']];
                }

                // Trafico 24h
                $stmt = $con->prepare("SELECT interface, rx_bits, tx_bits, DATE_FORMAT(fecha_registro, '%H:%i') as hora, fecha_registro as hora_completa FROM historico_trafico WHERE mikrotik_id = ? AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY fecha_registro ASC");
                $stmt->execute([$id]);
                $trafico_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $trafico = [];
                foreach ($trafico_raw as $t) {
                    if (!isset($trafico[$t['interface']])) {
                        $trafico[$t['interface']] = [];
                    }
                    $trafico[$t['interface']][] = ['rx' => $t['rx_bits'], 'tx' => $t['tx_bits'], 'hora' => $t['hora'], 'hora_completa' => $t['hora_completa']];
                }

                echo json_encode(array("status" => "success", "data" => array(
                    "recursos" => $recursos,
                    "pings" => $pings,
                    "trafico" => $trafico
                )));
            } catch (\Exception $e) { echo json_encode(array("status" => "error", "error" => $e->getMessage())); }
        } else { echo json_encode(array("status" => "error")); }
        break;

    case 'api_dashboard_noc':
        try {
            $con = (new Conexion())->conectar();
            
            // 1. Obtener Mikrotiks
            $stmtM = $con->prepare("
                SELECT m.id, m.nombre, m.ip_address, 'mikrotik' as tipo,
                (SELECT ms FROM historico_pings WHERE mikrotik_id = m.id AND tipo = 'servidor' ORDER BY fecha_registro DESC LIMIT 1) as ultimo_ping,
                (SELECT cpu_uso FROM historico_recursos WHERE mikrotik_id = m.id ORDER BY fecha_registro DESC LIMIT 1) as cpu_uso,
                (SELECT SUM(rx_bits + tx_bits) FROM historico_trafico WHERE mikrotik_id = m.id AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)) as trafico_total
                FROM mikrotiks m WHERE m.estado_actual = 1
            ");
            $stmtM->execute();
            $nodosM = $stmtM->fetchAll(PDO::FETCH_ASSOC);

            // 2. Obtener Equipos
            $stmtE = $con->prepare("
                SELECT e.id, e.nombre, e.ip_address, 'equipo' as tipo,
                (SELECT ms FROM historico_pings_equipos WHERE equipo_id = e.id ORDER BY fecha_registro DESC LIMIT 1) as ultimo_ping,
                NULL as cpu_uso,
                NULL as trafico_total
                FROM equipos e WHERE e.estado = 1
            ");
            $stmtE->execute();
            $nodosE = $stmtE->fetchAll(PDO::FETCH_ASSOC);

            // Combinar ambos arrays
            $nodos = array_merge($nodosM, $nodosE);

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
                
                if ($n['tipo'] === 'mikrotik') {
                    $traf_mbps = $n['trafico_total'] !== null ? ($n['trafico_total'] / 1000000) : 0;
                    $n['trafico_mbps'] = round($traf_mbps, 2);
                } else {
                    $n['trafico_mbps'] = 'N/A';
                }
                
                $estado = 'online';
                // Consideramos offline si no hay ping registrado o es 0 (falla)
                if ($ping == -1 || $ping == 0) {
                    $estado = 'offline';
                    $offline++;
                } else {
                    $online++;
                    if (($n['tipo'] === 'mikrotik' && $cpu > 80) || $ping > 200) {
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
        } catch (\Exception $e) { echo json_encode(array("status" => "error", "error" => $e->getMessage())); }
        break;

    case 'api_inventario_recursos':
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
        } catch (\Exception $e) { echo json_encode(array("status" => "error", "error" => $e->getMessage())); }
        break;

    case 'api_ping_google':
    case 'api_ping_server':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $data = $dao->obtenerPorId($id);
        if ($data) {
            try {
                $client = new Client(['host' => $data['ip_address'], 'user' => $data['usuario'], 'pass' => $data['password'], 'port' => intval($data['puerto_api']), 'timeout' => 2]);
                $ip = ($action == 'api_ping_google') ? '8.8.8.8' : $_SERVER['SERVER_ADDR'];
                if ($ip == '::1' || $ip == '127.0.0.1') $ip = '8.8.8.8'; // Fallback if localhost
                
                $query = (new \RouterOS\Query('/ping'))
                    ->equal('address', $ip)
                    ->equal('count', '1');
                $response = $client->query($query)->read();
                
                $ms = 0;
                if(isset($response[0]['time'])) {
                    $time_str = $response[0]['time'];
                    if (strpos($time_str, 's') !== false && strpos($time_str, 'ms') !== false) {
                        $parts = explode('s', $time_str);
                        $ms = (intval($parts[0]) * 1000) + intval(str_replace('ms', '', $parts[1]));
                    } elseif (strpos($time_str, 'ms') !== false) {
                        $ms = intval(str_replace('ms', '', $time_str));
                    } elseif (strpos($time_str, 's') !== false) {
                        $ms = intval(str_replace('s', '', $time_str)) * 1000;
                    }
                }
                echo json_encode(array("status" => "success", "ms" => $ms));
            } catch (\Exception $e) { echo json_encode(array("status" => "error")); }
        } else { echo json_encode(array("status" => "error")); }
        break;

    case 'api_reboot':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $data = $dao->obtenerPorId($id);
        if ($data) {
            try {
                $client = new Client(['host' => $data['ip_address'], 'user' => $data['usuario'], 'pass' => $data['password'], 'port' => intval($data['puerto_api']), 'timeout' => 3]);
                $query = new \RouterOS\Query('/system/reboot');
                $client->query($query)->read();
                echo json_encode(array("status" => "success"));
            } catch (\Exception $e) { echo json_encode(array("status" => "error", "error" => $e->getMessage())); }
        }
        break;

    case 'api_backup':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $data = $dao->obtenerPorId($id);
        if ($data) {
            try {
                $client = new Client(['host' => $data['ip_address'], 'user' => $data['usuario'], 'pass' => $data['password'], 'port' => intval($data['puerto_api']), 'timeout' => 10]);
                $query = new \RouterOS\Query('/export');
                $response = $client->query($query)->read();
                $content = "";
                if (is_array($response)) {
                    foreach($response as $line) {
                        // El export usualmente regresa items donde la llave es vacia o trae strings
                        $content .= print_r($line, true) . "\n";
                    }
                }
                echo json_encode(array("status" => "success", "content" => $content));
            } catch (\Exception $e) { echo json_encode(array("status" => "error", "error" => $e->getMessage())); }
        }
        break;

    default:
        echo json_encode(array("status" => "error", "message" => "Acción no válida"));
        break;
}
?>
