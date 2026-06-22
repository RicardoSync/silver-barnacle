<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$con = (new Conexion())->conectar();

switch ($action) {
    case 'campana':
        // Obtener ultimas 5 no leidas
        try {
            $stmt = $con->prepare("
                SELECT a.*, m.nombre as router 
                FROM alertas a 
                JOIN mikrotiks m ON a.mikrotik_id = m.id 
                WHERE a.estado = 'no_leido' 
                ORDER BY a.fecha_registro DESC LIMIT 5
            ");
            $stmt->execute();
            $ultimas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmtCount = $con->prepare("SELECT count(id) as total FROM alertas WHERE estado = 'no_leido'");
            $stmtCount->execute();
            $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo json_encode(['status' => 'success', 'data' => $ultimas, 'total' => $total]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'listar':
        try {
            $stmt = $con->prepare("
                SELECT a.*, m.nombre as router 
                FROM alertas a 
                JOIN mikrotiks m ON a.mikrotik_id = m.id 
                ORDER BY a.fecha_registro DESC LIMIT 1000
            ");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['data' => []]);
        }
        break;

    case 'marcar_leida':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $stmt = $con->prepare("UPDATE alertas SET estado = 'leido' WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success']);
        break;

    case 'marcar_todas':
        $stmt = $con->prepare("UPDATE alertas SET estado = 'leido' WHERE estado = 'no_leido'");
        $stmt->execute();
        echo json_encode(['status' => 'success']);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
}
