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
    case 'enviar_prueba':
        $controller->enviarPrueba();
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

    public function enviarPrueba() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $telefono = $_POST['telefono'] ?? '';
            if (empty($telefono)) {
                echo json_encode(["status" => "error", "message" => "El número de teléfono es requerido"]);
                return;
            }
            
            require_once __DIR__ . '/../includes/config.php';
            require_once __DIR__ . '/../includes/whatsapp_helper.php';
            
            try {
                $con = (new Conexion())->conectar();
                $mensaje = "¡Hola! Este es un mensaje de prueba desde tu sistema de Monitoreo WISP (MiWISPro/Elissa). Si recibes esto, tu API de WAHA está funcionando correctamente. ✅";
                $res = enviarNotificacionCustomWhatsApp($con, $telefono, $mensaje);
                
                if ($res['success']) {
                    echo json_encode(["status" => "success", "message" => "Mensaje enviado exitosamente a $telefono"]);
                } else {
                    echo json_encode(["status" => "error", "message" => $res['error']]);
                }
            } catch (\Exception $e) {
                echo json_encode(["status" => "error", "message" => "Excepción: " . $e->getMessage()]);
            }
        }
    }
}
?>
