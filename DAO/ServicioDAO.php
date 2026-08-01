<?php
require_once __DIR__ . '/../includes/config.php';

class ServicioDAO {
    private $conexion;

    public function __construct() {
        $con = new Conexion();
        $this->conexion = $con->conectar();
    }

    public function listarTodos() {
        $stmt = $this->conexion->prepare("SELECT * FROM servicios_monitoreo ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarActivos() {
        $stmt = $this->conexion->prepare("SELECT * FROM servicios_monitoreo WHERE estado = 1 ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $stmt = $this->conexion->prepare("SELECT * FROM servicios_monitoreo WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($nombre, $tipo, $target, $puerto = null, $umbral_ms = 300) {
        try {
            $stmt = $this->conexion->prepare("INSERT INTO servicios_monitoreo (nombre, tipo, target, puerto, umbral_ms, estado) VALUES (:nombre, :tipo, :target, :puerto, :umbral_ms, 1)");
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":tipo", $tipo);
            $stmt->bindParam(":target", $target);
            $stmt->bindParam(":puerto", $puerto, is_null($puerto) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindParam(":umbral_ms", $umbral_ms, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function actualizar($id, $nombre, $tipo, $target, $puerto = null, $umbral_ms = 300, $estado = 1) {
        try {
            $stmt = $this->conexion->prepare("UPDATE servicios_monitoreo SET nombre = :nombre, tipo = :tipo, target = :target, puerto = :puerto, umbral_ms = :umbral_ms, estado = :estado WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->bindParam(":tipo", $tipo);
            $stmt->bindParam(":target", $target);
            $stmt->bindParam(":puerto", $puerto, is_null($puerto) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindParam(":umbral_ms", $umbral_ms, PDO::PARAM_INT);
            $stmt->bindParam(":estado", $estado, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function eliminar($id) {
        try {
            $stmt = $this->conexion->prepare("DELETE FROM servicios_monitoreo WHERE id = :id");
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function registrarHistorico($servicio_id, $ms, $codigo_http, $ip_resuelta, $estado_check, $detalle = null) {
        try {
            $stmt = $this->conexion->prepare("INSERT INTO historico_servicios (servicio_id, ms, codigo_http, ip_resuelta, estado_check, detalle) VALUES (:servicio_id, :ms, :codigo_http, :ip_resuelta, :estado_check, :detalle)");
            $stmt->bindParam(":servicio_id", $servicio_id, PDO::PARAM_INT);
            $stmt->bindParam(":ms", $ms, PDO::PARAM_INT);
            $stmt->bindParam(":codigo_http", $codigo_http, is_null($codigo_http) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindParam(":ip_resuelta", $ip_resuelta);
            $stmt->bindParam(":estado_check", $estado_check);
            $stmt->bindParam(":detalle", $detalle);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function obtenerUltimoEstadoCompleto() {
        try {
            $sql = "
                SELECT s.*, 
                       h.ms as ultimo_ms, 
                       h.codigo_http, 
                       h.ip_resuelta, 
                       h.estado_check, 
                       h.fecha_registro as ultima_verificacion
                FROM servicios_monitoreo s
                LEFT JOIN historico_servicios h ON h.id = (
                    SELECT max(id) FROM historico_servicios WHERE servicio_id = s.id
                )
                ORDER BY s.id ASC
            ";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerHistoricoGrafica($servicio_id, $horas = 24) {
        try {
            $stmt = $this->conexion->prepare("
                SELECT ms, codigo_http, estado_check, DATE_FORMAT(fecha_registro, '%Y-%m-%dT%H:%i:%s') as fecha 
                FROM historico_servicios 
                WHERE servicio_id = :servicio_id 
                  AND fecha_registro >= DATE_SUB(NOW(), INTERVAL :horas HOUR) 
                ORDER BY fecha_registro ASC
            ");
            $stmt->bindParam(":servicio_id", $servicio_id, PDO::PARAM_INT);
            $stmt->bindParam(":horas", $horas, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obtenerResumenDashboard() {
        try {
            $sql = "
                SELECT 
                    COUNT(s.id) as total,
                    SUM(CASE WHEN h.estado_check = 'online' THEN 1 ELSE 0 END) as online_count,
                    SUM(CASE WHEN h.estado_check = 'lento' THEN 1 ELSE 0 END) as lento_count,
                    SUM(CASE WHEN h.estado_check = 'offline' OR h.estado_check IS NULL THEN 1 ELSE 0 END) as offline_count
                FROM servicios_monitoreo s
                LEFT JOIN historico_servicios h ON h.id = (
                    SELECT max(id) FROM historico_servicios WHERE servicio_id = s.id
                )
                WHERE s.estado = 1
            ";
            $stmt = $this->conexion->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['total' => 0, 'online_count' => 0, 'lento_count' => 0, 'offline_count' => 0];
        }
    }
}
?>
