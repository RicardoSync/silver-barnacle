<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../DTO/PlantillaAlertaDTO.php';

class PlantillaAlertaDAO {
    private $conexion;

    public function __construct() {
        $con = new Conexion();
        $this->conexion = $con->conectar();
    }

    public function listar() {
        $stmt = $this->conexion->prepare("SELECT * FROM plantillas_alerta WHERE estado = 1 ORDER BY minutos ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $stmt = $this->conexion->prepare("SELECT * FROM plantillas_alerta WHERE id = :id AND estado = 1");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insertar(PlantillaAlertaDTO $dto) {
        try {
            $stmt = $this->conexion->prepare("INSERT INTO plantillas_alerta (nombre, minutos, mensaje, estado) VALUES (:nombre, :minutos, :mensaje, 1)");
            
            $nombre = $dto->getNombre();
            $minutos = $dto->getMinutos();
            $mensaje = $dto->getMensaje();

            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":minutos", $minutos, PDO::PARAM_INT);
            $stmt->bindParam(":mensaje", $mensaje);

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizar(PlantillaAlertaDTO $dto) {
        try {
            $stmt = $this->conexion->prepare("UPDATE plantillas_alerta SET nombre = :nombre, minutos = :minutos, mensaje = :mensaje WHERE id = :id");
            
            $id = $dto->getId();
            $nombre = $dto->getNombre();
            $minutos = $dto->getMinutos();
            $mensaje = $dto->getMensaje();

            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":minutos", $minutos, PDO::PARAM_INT);
            $stmt->bindParam(":mensaje", $mensaje);

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function eliminar($id) {
        try {
            $stmt = $this->conexion->prepare("UPDATE plantillas_alerta SET estado = 0 WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
