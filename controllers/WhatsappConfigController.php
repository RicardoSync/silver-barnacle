<?php
require_once __DIR__ . '/../DAO/WhatsappConfigDAO.php';
require_once __DIR__ . '/../DTO/WhatsappConfigDTO.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$controller = new WhatsappConfigController();

switch ($action) {
    case 'obtener':
        $controller->obtener();
        break;
    case 'guardar':
        $controller->guardar();
        break;
}

class WhatsappConfigController {
    private $dao;

    public function __construct() {
        $this->dao = new WhatsappConfigDAO();
    }

    public function obtener() {
        $config = $this->dao->obtener();
        if ($config) {
            echo json_encode(["status" => "success", "data" => $config]);
        } else {
            echo json_encode(["status" => "error", "message" => "No se pudo obtener la configuración"]);
        }
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dto = new WhatsappConfigDTO();
            $dto->setWahaApiKey($_POST['waha_api_key'] ?? '');
            $dto->setWahaUrl($_POST['waha_url'] ?? '');
            $dto->setUrlSistema($_POST['url_sistema'] ?? '');
            $dto->setApiSecret($_POST['api_secret'] ?? '');
            
            $enlacesPublicos = isset($_POST['enlaces_publicos_activos']) && $_POST['enlaces_publicos_activos'] == '1' ? 1 : 0;
            $dto->setEnlacesPublicosActivos($enlacesPublicos);

            $resultado = $this->dao->actualizar($dto);

            if ($resultado) {
                echo json_encode(["status" => "success", "message" => "Configuración actualizada correctamente"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Error al actualizar la configuración"]);
            }
        }
    }
}
?>
