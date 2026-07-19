<?php
require_once __DIR__ . '/../DAO/HistorialCaidaDAO.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$controller = new HistorialCaidaController();

switch ($action) {
    case 'listar':
        $controller->listar();
        break;
    case 'kpis':
        $controller->obtenerKPIs();
        break;
    case 'grafica':
        $dias = isset($_GET['dias']) ? (int)$_GET['dias'] : 7;
        $controller->obtenerGrafica($dias);
        break;
    case 'activas':
        $controller->obtenerActivas();
        break;
}

class HistorialCaidaController {
    private $dao;

    public function __construct() {
        $this->dao = new HistorialCaidaDAO();
    }

    public function listar() {
        $datos = $this->dao->listarTodo();
        echo json_encode(["data" => $datos]);
    }

    public function obtenerKPIs() {
        $kpis = $this->dao->obtenerKPIs();
        echo json_encode($kpis);
    }

    public function obtenerGrafica($dias) {
        $datosGrafica = $this->dao->obtenerDatosGrafica($dias);
        
        $labels = [];
        $data = [];
        $nodos = [];

        foreach ($datosGrafica as $row) {
            $labels[] = str_replace(' ', 'T', $row['fecha']); // Formato ISO para ChartJS time scale
            $data[] = (int)$row['valor'];
            $nodos[] = $row['nombre_nodo'];
        }

        echo json_encode([
            "labels" => $labels,
            "data" => $data,
            "nodos" => $nodos
        ]);
    }

    public function obtenerActivas() {
        require_once __DIR__ . '/../includes/config.php';
        $con = (new Conexion())->conectar();
        $stmt = $con->query("SELECT * FROM historial_caidas WHERE estado = 'en_curso' ORDER BY fecha_caida DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
?>
