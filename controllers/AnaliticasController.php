<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../DAO/AnaliticasDAO.php';

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

$dao = new AnaliticasDAO();

switch ($action) {
    case 'getInterfaces':
        $mikrotik_id = isset($_GET['mikrotik_id']) ? intval($_GET['mikrotik_id']) : 0;
        if ($mikrotik_id > 0) {
            echo json_encode($dao->getInterfacesUnicas($mikrotik_id));
        } else {
            echo json_encode([]);
        }
        break;

    case 'getTrafico':
        $mikrotik_id = isset($_GET['mikrotik_id']) ? intval($_GET['mikrotik_id']) : 0;
        $interface = isset($_GET['interface']) && !empty($_GET['interface']) ? $_GET['interface'] : null;
        $horas = isset($_GET['horas']) ? intval($_GET['horas']) : 4;
        
        if ($mikrotik_id > 0) {
            echo json_encode($dao->getTrafico($mikrotik_id, $interface, $horas));
        } else {
            echo json_encode([]);
        }
        break;

    case 'getPing':
        $mikrotik_id = isset($_GET['mikrotik_id']) ? intval($_GET['mikrotik_id']) : 0;
        $horas = isset($_GET['horas']) ? intval($_GET['horas']) : 4;
        
        if ($mikrotik_id > 0) {
            echo json_encode($dao->getPing($mikrotik_id, $horas));
        } else {
            echo json_encode([]);
        }
        break;

    case 'getPingEquipo':
        $equipo_id = isset($_GET['equipo_id']) ? intval($_GET['equipo_id']) : 0;
        $horas = isset($_GET['horas']) ? intval($_GET['horas']) : 4;
        
        if ($equipo_id > 0) {
            echo json_encode($dao->getPingEquipo($equipo_id, $horas));
        } else {
            echo json_encode([]);
        }
        break;

    case 'getRecursos':
        $mikrotik_id = isset($_GET['mikrotik_id']) ? intval($_GET['mikrotik_id']) : 0;
        $horas = isset($_GET['horas']) ? intval($_GET['horas']) : 4;
        
        if ($mikrotik_id > 0) {
            echo json_encode($dao->getRecursos($mikrotik_id, $horas));
        } else {
            echo json_encode([]);
        }
        break;

    case 'getTopCaidas':
        $horas = isset($_GET['horas']) ? intval($_GET['horas']) : 4;
        echo json_encode($dao->getTopCaidas($horas));
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}
?>
