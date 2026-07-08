<?php
require_once __DIR__ . '/../DAO/ContactoAlertaDAO.php';
require_once __DIR__ . '/../DTO/ContactoAlertaDTO.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$controller = new ContactoAlertaController();

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

class ContactoAlertaController {
    private $dao;

    public function __construct() {
        $this->dao = new ContactoAlertaDAO();
    }

    public function listar() {
        $contactos = $this->dao->listar();
        echo json_encode(["data" => $contactos]);
    }

    public function obtener() {
        if (isset($_GET['id'])) {
            $contacto = $this->dao->obtenerPorId($_GET['id']);
            echo json_encode($contacto);
        }
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dto = new ContactoAlertaDTO();
            $dto->setNombre($_POST['nombre']);
            $dto->setTelefono($_POST['telefono']);
            
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // Actualizar
                $dto->setId($_POST['id']);
                $resultado = $this->dao->actualizar($dto);
                $mensaje = "Contacto actualizado correctamente";
            } else {
                // Insertar
                $resultado = $this->dao->insertar($dto);
                $mensaje = "Contacto registrado correctamente";
            }

            if ($resultado) {
                echo json_encode(["status" => "success", "message" => $mensaje]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error al guardar el contacto"]);
            }
        }
    }

    public function eliminar() {
        if (isset($_POST['id'])) {
            $resultado = $this->dao->eliminar($_POST['id']);
            if ($resultado) {
                echo json_encode(["status" => "success", "message" => "Contacto eliminado correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error al eliminar el contacto"]);
            }
        }
    }
}
?>
