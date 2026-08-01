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

            // 1. Resolver caídas activas de nodos que hayan sido eliminados lógicamente (estado_actual = 0 o estado = 0) o borrados
            $con->exec("
                UPDATE historial_caidas h
                LEFT JOIN mikrotiks m ON h.tipo_nodo = 'mikrotik' AND h.nodo_id = m.id
                LEFT JOIN equipos e ON h.tipo_nodo = 'equipo' AND h.nodo_id = e.id
                SET h.estado = 'resuelta', h.fecha_recuperacion = NOW()
                WHERE h.estado = 'en_curso'
                  AND (
                    (h.tipo_nodo = 'mikrotik' AND (m.id IS NULL OR m.estado_actual = 0)) OR
                    (h.tipo_nodo = 'equipo' AND (e.id IS NULL OR e.estado = 0))
                  )
            ");

            // 2. Limpiar registros duplicados de caídas en curso si existían por nombre de nodo
            $con->exec("
                UPDATE historial_caidas h1
                JOIN historial_caidas h2 
                  ON LOWER(TRIM(h1.nombre_nodo)) = LOWER(TRIM(h2.nombre_nodo))
                 AND h1.estado = 'en_curso' 
                 AND h2.estado = 'en_curso' 
                 AND h1.id > h2.id
                SET h1.estado = 'resuelta', h1.fecha_recuperacion = NOW()
            ");

            // 3. Buscar caídas activas (en curso) ÚNICAMENTE de nodos ACTIVOS (estado_actual = 1 o estado = 1)
            $stmt2 = $con->prepare("
                SELECT MIN(h.id) as id, MAX(h.nodo_id) as nodo_id, MAX(h.tipo_nodo) as tipo_nodo, h.nombre_nodo, MIN(h.fecha_caida) as fecha_caida, h.estado,
                       TIMESTAMPDIFF(SECOND, MIN(h.fecha_caida), NOW()) as segundos_caida 
                FROM historial_caidas h
                LEFT JOIN mikrotiks m ON h.tipo_nodo = 'mikrotik' AND h.nodo_id = m.id
                LEFT JOIN equipos e ON h.tipo_nodo = 'equipo' AND h.nodo_id = e.id
                WHERE h.estado = 'en_curso'
                  AND (
                    (h.tipo_nodo = 'mikrotik' AND m.estado_actual = 1) OR
                    (h.tipo_nodo = 'equipo' AND e.estado = 1)
                  )
                GROUP BY h.nombre_nodo
                ORDER BY MIN(h.fecha_caida) ASC
            ");
            $stmt2->execute();
            $nuevas_caidas = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            // Buscar recuperaciones recientes (caídas resueltas en los últimos 2 minutos)
            $stmt3 = $con->prepare("
                SELECT id, nodo_id, tipo_nodo, nombre_nodo, fecha_recuperacion, duracion_minutos
                FROM historial_caidas 
                WHERE estado = 'resuelta' AND fecha_recuperacion >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
                ORDER BY fecha_recuperacion DESC
            ");
            $stmt3->execute();
            $recuperaciones = $stmt3->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'alertas' => $nuevas_alertas,
                'caidas' => $nuevas_caidas,
                'recuperaciones' => $recuperaciones
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
}
