<?php
require_once __DIR__ . '/../DAO/PlantillaAlertaDAO.php';
require_once __DIR__ . '/../DTO/PlantillaAlertaDTO.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$controller = new PlantillaAlertaController();

switch ($action) {
    case 'listar':
        $controller->listar();
        break;
    case 'obtener':
        $controller->obtener();
        break;
    case 'guardar':
        $controller->guardar();
        break;
    case 'eliminar':
        $controller->eliminar();
        break;
}

class PlantillaAlertaController {
    private $dao;

    public function __construct() {
        $this->dao = new PlantillaAlertaDAO();
    }

    public function listar() {
        $plantillas = $this->dao->listar();
        echo json_encode(["data" => $plantillas]);
    }

    public function obtener() {
        if (isset($_GET['id'])) {
            $plantilla = $this->dao->obtenerPorId($_GET['id']);
            echo json_encode($plantilla);
        }
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dto = new PlantillaAlertaDTO();
            $dto->setNombre($_POST['nombre']);
            $dto->setMinutos(intval($_POST['minutos']));
            $dto->setMensaje($_POST['mensaje']);
            
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // Actualizar
                $dto->setId($_POST['id']);
                $resultado = $this->dao->actualizar($dto);
                $mensaje = "Plantilla actualizada correctamente";
            } else {
                // Insertar
                $resultado = $this->dao->insertar($dto);
                $mensaje = "Plantilla registrada correctamente";
            }

            if ($resultado) {
                echo json_encode(["status" => "success", "message" => $mensaje]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error al guardar la plantilla"]);
            }
        }
    }

    public function eliminar() {
        if (isset($_POST['id'])) {
            $resultado = $this->dao->eliminar($_POST['id']);
            if ($resultado) {
                echo json_encode(["status" => "success", "message" => "Plantilla eliminada correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error al eliminar la plantilla"]);
            }
        }
    }
}
?>
