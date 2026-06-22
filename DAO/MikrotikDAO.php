<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../DTO/MikrotikDTO.php';

class MikrotikDAO {
    private $conexion;

    public function __construct() {
        $con = new Conexion();
        $this->conexion = $con->conectar();
    }

    public function listarActivos() {
        $stmt = $this->conexion->prepare("SELECT * FROM mikrotiks WHERE estado_actual = 1 ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $stmt = $this->conexion->prepare("SELECT * FROM mikrotiks WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insertar(MikrotikDTO $dto) {
        try {
            $stmt = $this->conexion->prepare("INSERT INTO mikrotiks (nombre, ip_address, puerto_api, usuario, password, latitud, longitud, estado_actual) VALUES (:nombre, :ip_address, :puerto_api, :usuario, :password, :latitud, :longitud, 1)");
            
            $nombre = $dto->getNombre();
            $ip = $dto->getIpAddress();
            $puerto = $dto->getPuertoApi();
            $usuario = $dto->getUsuario();
            $password = $dto->getPassword();
            $lat = $dto->getLatitud();
            $lon = $dto->getLongitud();

            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":ip_address", $ip);
            $stmt->bindParam(":puerto_api", $puerto);
            $stmt->bindParam(":usuario", $usuario);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":latitud", $lat);
            $stmt->bindParam(":longitud", $lon);

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizar(MikrotikDTO $dto) {
        try {
            // Si la contraseña viene vacía, no la actualizamos
            if (empty($dto->getPassword())) {
                $stmt = $this->conexion->prepare("UPDATE mikrotiks SET nombre = :nombre, ip_address = :ip_address, puerto_api = :puerto_api, usuario = :usuario, latitud = :latitud, longitud = :longitud WHERE id = :id");
            } else {
                $stmt = $this->conexion->prepare("UPDATE mikrotiks SET nombre = :nombre, ip_address = :ip_address, puerto_api = :puerto_api, usuario = :usuario, password = :password, latitud = :latitud, longitud = :longitud WHERE id = :id");
                $pass = $dto->getPassword();
                $stmt->bindParam(":password", $pass);
            }

            $id = $dto->getId();
            $nombre = $dto->getNombre();
            $ip = $dto->getIpAddress();
            $puerto = $dto->getPuertoApi();
            $usuario = $dto->getUsuario();
            $lat = $dto->getLatitud();
            $lon = $dto->getLongitud();

            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":ip_address", $ip);
            $stmt->bindParam(":puerto_api", $puerto);
            $stmt->bindParam(":usuario", $usuario);
            $stmt->bindParam(":latitud", $lat);
            $stmt->bindParam(":longitud", $lon);

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function borradoLogico($id) {
        try {
            $stmt = $this->conexion->prepare("UPDATE mikrotiks SET estado_actual = 0 WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
