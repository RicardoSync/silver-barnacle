<?php
if (!headers_sent()) {
    header('Content-Type: application/json');
}
require_once __DIR__ . '/../DAO/TopologiaDAO.php';

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
$dao = new TopologiaDAO();

switch ($action) {
    case 'obtener':
        $nodos = $dao->obtenerNodos();
        $enlaces = $dao->obtenerEnlaces();
        echo json_encode([
            'status' => 'success',
            'nodos' => $nodos,
            'enlaces' => $enlaces
        ]);
        break;

    case 'guardar_nodo':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
        $ip_address = isset($_POST['ip_address']) ? trim($_POST['ip_address']) : '';
        $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : 'ap';
        $pos_x = isset($_POST['pos_x']) ? intval($_POST['pos_x']) : 150;
        $pos_y = isset($_POST['pos_y']) ? intval($_POST['pos_y']) : 150;

        if (empty($nombre) || empty($ip_address)) {
            echo json_encode(['status' => 'error', 'message' => 'El nombre y la dirección IP son obligatorios.']);
            exit;
        }

        if ($id > 0) {
            $res = $dao->actualizarNodo($id, $nombre, $ip_address, $tipo);
            $msg = $res ? 'Nodo actualizado correctamente.' : 'Error al actualizar el nodo.';
            $nodeId = $id;
        } else {
            $nodeId = $dao->insertarNodo($nombre, $ip_address, $tipo, $pos_x, $pos_y);
            $res = $nodeId !== false;
            $msg = $res ? 'Nodo creado correctamente.' : 'Error al crear el nodo.';
        }

        echo json_encode([
            'status' => $res ? 'success' : 'error',
            'message' => $msg,
            'node_id' => $nodeId
        ]);
        break;

    case 'actualizar_posicion':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $pos_x = isset($_POST['pos_x']) ? intval($_POST['pos_x']) : 0;
        $pos_y = isset($_POST['pos_y']) ? intval($_POST['pos_y']) : 0;

        if ($id > 0) {
            $res = $dao->actualizarPosicion($id, $pos_x, $pos_y);
            echo json_encode(['status' => $res ? 'success' : 'error']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID de nodo inválido.']);
        }
        break;

    case 'eliminar_nodo':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id > 0) {
            $res = $dao->eliminarNodo($id);
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'message' => $res ? 'Nodo eliminado.' : 'Error al eliminar nodo.'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
        }
        break;

    case 'crear_enlace':
        $origen_id = isset($_POST['nodo_origen_id']) ? intval($_POST['nodo_origen_id']) : 0;
        $destino_id = isset($_POST['nodo_destino_id']) ? intval($_POST['nodo_destino_id']) : 0;
        $tipo_enlace = isset($_POST['tipo_enlace']) ? trim($_POST['tipo_enlace']) : 'inalambrico';
        $etiqueta = isset($_POST['etiqueta']) ? trim($_POST['etiqueta']) : '';

        if ($origen_id <= 0 || $destino_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Nodos de origen y destino requeridos.']);
            exit;
        }

        if ($origen_id === $destino_id) {
            echo json_encode(['status' => 'error', 'message' => 'No puedes conectar un nodo consigo mismo.']);
            exit;
        }

        $enlaceId = $dao->insertarEnlace($origen_id, $destino_id, $tipo_enlace, $etiqueta);

        if ($enlaceId) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Enlace creado correctamente.',
                'enlace_id' => $enlaceId
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No se pudo crear el enlace o ya existe una conexión entre estos nodos.'
            ]);
        }
        break;

    case 'eliminar_enlace':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id > 0) {
            $res = $dao->eliminarEnlace($id);
            echo json_encode([
                'status' => $res ? 'success' : 'error',
                'message' => $res ? 'Enlace eliminado.' : 'Error al eliminar enlace.'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
        }
        break;

    case 'importar':
        $creados = $dao->importarDispositivosExistentes();
        echo json_encode([
            'status' => 'success',
            'message' => $creados > 0 ? "Se importaron {$creados} dispositivos al mapa de topología." : 'Todos los dispositivos existentes ya están en el mapa.',
            'creados' => $creados
        ]);
        break;

    case 'ping_batch':
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $nodos = $dao->obtenerNodos();
        $resultados = [];

        foreach ($nodos as $nodo) {
            $pingRes = $dao->hacerPing($nodo['ip_address']);
            $resultados[$nodo['id']] = [
                'id' => $nodo['id'],
                'ip' => $nodo['ip_address'],
                'status' => $pingRes['status'],
                'ms' => $pingRes['ms']
            ];
        }

        echo json_encode([
            'status' => 'success',
            'data' => $resultados
        ]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}
