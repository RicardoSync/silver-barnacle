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
        $horas = isset($_GET['horas']) ? floatval($_GET['horas']) : (isset($_GET['dias']) ? floatval($_GET['dias']) * 24 : 24);
        $controller->obtenerGrafica($horas);
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
        $horas = isset($_GET['horas']) ? floatval($_GET['horas']) : 24;
        $datos = $this->dao->listarPorTiempo($horas);
        echo json_encode(["data" => $datos]);
    }

    public function obtenerKPIs() {
        $horas = isset($_GET['horas']) ? floatval($_GET['horas']) : 24;
        $kpis = $this->dao->obtenerKPIs($horas);
        echo json_encode($kpis);
    }

    public function obtenerGrafica($horas) {
        $datosGrafica = $this->dao->obtenerDatosGrafica($horas);
        
        $labels = [];
        $data = [];
        $nodos = [];
        $tipos = [];
        $estados = [];

        foreach ($datosGrafica as $row) {
            $labels[] = str_replace(' ', 'T', $row['fecha']); // ISO format for Chart.js
            $data[] = (int)$row['valor'];
            $nodos[] = $row['nombre_nodo'];
            $tipos[] = $row['tipo_nodo'];
            $estados[] = $row['estado'];
        }

        echo json_encode([
            "labels" => $labels,
            "data" => $data,
            "nodos" => $nodos,
            "tipos" => $tipos,
            "estados" => $estados
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
