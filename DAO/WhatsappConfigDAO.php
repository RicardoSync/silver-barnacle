<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../DTO/WhatsappConfigDTO.php';

class WhatsappConfigDAO {
    private $conexion;

    public function __construct() {
        $con = new Conexion();
        $this->conexion = $con->conectar();
    }

    public function obtener() {
        $stmt = $this->conexion->prepare("SELECT * FROM whatsapp_config WHERE id = 1");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizar(WhatsappConfigDTO $dto) {
        try {
            $stmt = $this->conexion->prepare("
                UPDATE whatsapp_config 
                SET waha_api_key = :waha_api_key, 
                    waha_url = :waha_url, 
                    url_sistema = :url_sistema, 
                    api_secret = :api_secret, 
                    enlaces_publicos_activos = :enlaces_publicos_activos 
                WHERE id = 1
            ");
            
            $apiKey = $dto->getWahaApiKey();
            $url = $dto->getWahaUrl();
            $urlSistema = $dto->getUrlSistema();
            $apiSecret = $dto->getApiSecret();
            $enlacesPublicos = $dto->getEnlacesPublicosActivos();

            $stmt->bindParam(":waha_api_key", $apiKey);
            $stmt->bindParam(":waha_url", $url);
            $stmt->bindParam(":url_sistema", $urlSistema);
            $stmt->bindParam(":api_secret", $apiSecret);
            $stmt->bindParam(":enlaces_publicos_activos", $enlacesPublicos, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
