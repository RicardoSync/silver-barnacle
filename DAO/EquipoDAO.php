<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../DTO/EquipoDTO.php';

class EquipoDAO {
    private $conexion;

    public function __construct() {
        $con = new Conexion();
        $this->conexion = $con->conectar();
    }

    public function listarActivos() {
        $stmt = $this->conexion->prepare("SELECT * FROM equipos WHERE estado = 1 ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $stmt = $this->conexion->prepare("SELECT * FROM equipos WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insertar(EquipoDTO $dto) {
        try {
            $stmt = $this->conexion->prepare("INSERT INTO equipos (nombre, usuario, password, ip_address, comunidad_snmp, contacto_snmp, estado) VALUES (:nombre, :usuario, :password, :ip_address, :comunidad_snmp, :contacto_snmp, 1)");
            
            $nombre = $dto->getNombre();
            $usuario = $dto->getUsuario();
            $password = $dto->getPassword();
            $ip = $dto->getIpAddress();
            $comunidad = $dto->getComunidadSnmp();
            $contacto = $dto->getContactoSnmp();

            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":usuario", $usuario);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":ip_address", $ip);
            $stmt->bindParam(":comunidad_snmp", $comunidad);
            $stmt->bindParam(":contacto_snmp", $contacto);

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizar(EquipoDTO $dto) {
        try {
            if (empty($dto->getPassword())) {
                $stmt = $this->conexion->prepare("UPDATE equipos SET nombre = :nombre, usuario = :usuario, ip_address = :ip_address, comunidad_snmp = :comunidad_snmp, contacto_snmp = :contacto_snmp WHERE id = :id");
            } else {
                $stmt = $this->conexion->prepare("UPDATE equipos SET nombre = :nombre, usuario = :usuario, password = :password, ip_address = :ip_address, comunidad_snmp = :comunidad_snmp, contacto_snmp = :contacto_snmp WHERE id = :id");
                $pass = $dto->getPassword();
                $stmt->bindParam(":password", $pass);
            }

            $id = $dto->getId();
            $nombre = $dto->getNombre();
            $usuario = $dto->getUsuario();
            $ip = $dto->getIpAddress();
            $comunidad = $dto->getComunidadSnmp();
            $contacto = $dto->getContactoSnmp();

            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":usuario", $usuario);
            $stmt->bindParam(":ip_address", $ip);
            $stmt->bindParam(":comunidad_snmp", $comunidad);
            $stmt->bindParam(":contacto_snmp", $contacto);

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function borradoLogico($id) {
        try {
            $stmt = $this->conexion->prepare("UPDATE equipos SET estado = 0 WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function obtenerHistoricoPings($id) {
        try {
            $stmt = $this->conexion->prepare("
                SELECT ms, DATE_FORMAT(fecha_registro, '%Y-%m-%dT%H:%i:%s') as hora_completa 
                FROM historico_pings_equipos 
                WHERE equipo_id = :id 
                  AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                ORDER BY fecha_registro ASC
            ");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>
