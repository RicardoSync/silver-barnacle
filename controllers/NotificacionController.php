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

    case 'getNuevasAlertasWeb':
        $last_alerta_id = isset($_GET['last_alerta_id']) ? intval($_GET['last_alerta_id']) : 0;
        $last_caida_id = isset($_GET['last_caida_id']) ? intval($_GET['last_caida_id']) : 0;

        // Fase de inicialización para que el cliente sepa desde dónde empezar
        if ($last_alerta_id === 0 && $last_caida_id === 0) {
            $maxA = $con->query("SELECT MAX(id) FROM alertas")->fetchColumn();
            $maxC = $con->query("SELECT MAX(id) FROM historial_caidas")->fetchColumn();
            echo json_encode([
                'status' => 'init',
                'last_alerta_id' => $maxA ? intval($maxA) : 0,
                'last_caida_id' => $maxC ? intval($maxC) : 0
            ]);
            exit;
        }

        $nuevas_alertas = [];
        $nuevas_caidas = [];

        try {
            // Buscar nuevas alertas
            $stmt1 = $con->prepare("
                SELECT a.id, a.tipo, a.mensaje, a.fecha_registro, m.nombre as router 
                FROM alertas a 
                JOIN mikrotiks m ON a.mikrotik_id = m.id 
                WHERE a.id > ? 
                ORDER BY a.id ASC
            ");
            $stmt1->execute([$last_alerta_id]);
            $nuevas_alertas = $stmt1->fetchAll(PDO::FETCH_ASSOC);

            // Buscar nuevas caídas (en curso)
            $stmt2 = $con->prepare("
                SELECT id, tipo_nodo, nombre_nodo, fecha_caida, estado 
                FROM historial_caidas 
                WHERE id > ? AND estado = 'en_curso'
                ORDER BY id ASC
            ");
            $stmt2->execute([$last_caida_id]);
            $nuevas_caidas = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'alertas' => $nuevas_alertas,
                'caidas' => $nuevas_caidas
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
}
