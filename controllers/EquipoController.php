<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../DAO/EquipoDAO.php';
require_once __DIR__ . '/../DTO/EquipoDTO.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$dao = new EquipoDAO();

switch ($action) {
    case 'listar':
        $datos = $dao->listarActivos();
        $array = array();
        foreach ($datos as $row) {
            $acciones = '
                <button class="btn btn-sm btn-info text-white me-1" onclick="loadView(\'equipos/detalles\', { id: '.$row['id'].' })" title="Ver Detalles"><i class="bi bi-eye"></i></button>
                <button class="btn btn-sm btn-primary me-1 btn-edit" onclick="editarEquipo('.$row['id'].')"><i class="bi bi-pencil-square"></i></button>
                <button class="btn btn-sm btn-danger btn-delete" onclick="eliminarEquipo('.$row['id'].')"><i class="bi bi-trash"></i></button>
            ';
            
            $estado = '<span class="status-check-equipo badge bg-warning text-dark" data-id="'.$row['id'].'" data-status="pending"><span class="spinner-border spinner-border-sm" style="width: 10px; height: 10px;"></span> Verificando...</span>';
            
            $array[] = array(
                "id" => $row['id'],
                "nombre" => htmlspecialchars($row['nombre']),
                "ip_address" => htmlspecialchars($row['ip_address']),
                "comunidad_snmp" => htmlspecialchars($row['comunidad_snmp']),
                "contacto_snmp" => htmlspecialchars($row['contacto_snmp']),
                "estado" => $estado,
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
                // Return data without displaying password securely if needed, but here it's simple
                echo json_encode(array("status" => "success", "data" => $data));
            } else {
                echo json_encode(array("status" => "error", "message" => "Equipo no encontrado"));
            }
        } else {
            echo json_encode(array("status" => "error", "message" => "ID inválido"));
        }
        break;

    case 'guardar':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        $dto = new EquipoDTO();
        $dto->setNombre($_POST['nombre']);
        $dto->setUsuario($_POST['usuario']);
        $dto->setPassword($_POST['password']);
        $dto->setIpAddress($_POST['ip_address']);
        $dto->setComunidadSnmp($_POST['comunidad_snmp']);
        $dto->setContactoSnmp($_POST['contacto_snmp']);

        if ($id > 0) {
            $dto->setId($id);
            $res = $dao->actualizar($dto);
            $msg = $res ? "Equipo actualizado correctamente" : "Error al actualizar el equipo";
        } else {
            $res = $dao->insertar($dto);
            $msg = $res ? "Equipo registrado correctamente" : "Error al registrar el equipo";
        }

        if ($res) {
            require_once __DIR__ . '/../DAO/TopologiaDAO.php';
            (new TopologiaDAO())->sincronizarConInventario();
        }

        echo json_encode(array("status" => $res ? "success" : "error", "message" => $msg));
        break;

    case 'eliminar':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id > 0) {
            $res = $dao->borradoLogico($id);
            if ($res) {
                require_once __DIR__ . '/../DAO/TopologiaDAO.php';
                (new TopologiaDAO())->sincronizarConInventario();
                echo json_encode(array("status" => "success", "message" => "Equipo eliminado correctamente"));
            } else {
                echo json_encode(array("status" => "error", "message" => "Error al eliminar el equipo"));
            }
        } else {
            echo json_encode(array("status" => "error", "message" => "ID inválido"));
        }
        break;

    case 'api_ping_server':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            $eq = $dao->obtenerPorId($id);
            if ($eq) {
                $ip = escapeshellarg($eq['ip_address']);
                $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
                $output = $isWindows ? shell_exec("ping -n 1 -w 1500 $ip 2>&1") : shell_exec("ping -c 1 -W 2 $ip 2>&1");
                $ms = -1;
                if (preg_match('/(?:time|tiempo)[=<]([\d\.]+)\s*ms/i', $output, $matches)) {
                    $ms = intval(round(floatval($matches[1])));
                } elseif (strpos($output, 'TTL=') !== false || strpos($output, 'ttl=') !== false || strpos($output, '1 received') !== false || strpos($output, '1 recibidos') !== false) {
                    $ms = 0;
                }
                echo json_encode(array("status" => "success", "ms" => max(0, $ms)));
            } else {
                echo json_encode(array("status" => "error", "message" => "Equipo no encontrado"));
            }
        } else {
            echo json_encode(array("status" => "error", "message" => "ID inválido"));
        }
        break;

    case 'api_historico_pings':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            $data = $dao->obtenerHistoricoPings($id);
            echo json_encode(array("status" => "success", "data" => $data));
        } else {
            echo json_encode(array("status" => "error", "message" => "ID inválido"));
        }
        break;

    case 'ping':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            $eq = $dao->obtenerPorId($id);
            if ($eq) {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }
                
                $ip = escapeshellarg($eq['ip_address']);
                $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
                $output = $isWindows ? shell_exec("ping -n 2 -w 1500 $ip 2>&1") : shell_exec("ping -c 2 -w 2 $ip 2>&1");
                $ping_real = -1;
                $is_online = false;
                
                if (preg_match('/rtt min\/avg\/max\/mdev = [\d\.]+\/([\d\.]+)\//i', $output, $matches)) {
                    $ping_real = intval(round(floatval($matches[1])));
                    $is_online = true;
                } elseif (preg_match('/(?:time|tiempo)[=<]([\d\.]+)\s*ms/i', $output, $matches)) {
                    $ping_real = intval(round(floatval($matches[1])));
                    $is_online = true;
                } elseif (strpos($output, 'TTL=') !== false || strpos($output, 'ttl=') !== false || strpos($output, '1 received') !== false || strpos($output, '2 received') !== false || strpos($output, '1 recibidos') !== false || strpos($output, '2 recibidos') !== false) {
                    $ping_real = 0;
                    $is_online = true;
                }
                
                if ($is_online) {
                    echo json_encode(array("status" => "online", "ms" => max(0, $ping_real)));
                } else {
                    echo json_encode(array("status" => "offline"));
                }
            } else {
                echo json_encode(array("status" => "offline", "error" => "No encontrado"));
            }
        } else {
            echo json_encode(array("status" => "offline", "error" => "ID inválido"));
        }
        break;

    default:
        echo json_encode(array("status" => "error", "message" => "Acción no válida"));
        break;
}
?>
