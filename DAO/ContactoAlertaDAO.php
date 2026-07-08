<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../DTO/ContactoAlertaDTO.php';

class ContactoAlertaDAO {
    private $conexion;

    public function __construct() {
        $con = new Conexion();
        $this->conexion = $con->conectar();
    }

    public function listar() {
        $stmt = $this->conexion->prepare("SELECT * FROM contactos_alerta WHERE estado = 1 ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $stmt = $this->conexion->prepare("SELECT * FROM contactos_alerta WHERE id = :id AND estado = 1");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insertar(ContactoAlertaDTO $dto) {
        try {
            $stmt = $this->conexion->prepare("INSERT INTO contactos_alerta (nombre, telefono, estado) VALUES (:nombre, :telefono, 1)");
            
            $nombre = $dto->getNombre();
            $telefono = $dto->getTelefono();

            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":telefono", $telefono);

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizar(ContactoAlertaDTO $dto) {
        try {
            $stmt = $this->conexion->prepare("UPDATE contactos_alerta SET nombre = :nombre, telefono = :telefono WHERE id = :id");
            
            $id = $dto->getId();
            $nombre = $dto->getNombre();
            $telefono = $dto->getTelefono();

            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":telefono", $telefono);

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function eliminar($id) {
        try {
            $stmt = $this->conexion->prepare("UPDATE contactos_alerta SET estado = 0 WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
