<?php
require_once __DIR__ . '/../includes/config.php';

class HistorialCaidaDAO {
    private $conexion;

    public function __construct() {
        $con = new Conexion();
        $this->conexion = $con->conectar();
    }

    public function listarTodo() {
        $stmt = $this->conexion->prepare("
            SELECT id, nodo_id, tipo_nodo, nombre_nodo, fecha_caida, fecha_recuperacion, duracion_minutos, estado 
            FROM historial_caidas 
            ORDER BY fecha_caida DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerKPIs() {
        // Caídas Activas ('en_curso')
        $stmtActivas = $this->conexion->query("SELECT COUNT(*) FROM historial_caidas WHERE estado = 'en_curso'");
        $activas = $stmtActivas->fetchColumn();

        // Caídas Hoy (Últimas 24 horas)
        $stmtHoy = $this->conexion->query("SELECT COUNT(*) FROM historial_caidas WHERE fecha_caida >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $hoy = $stmtHoy->fetchColumn();

        // Tiempo Promedio este mes
        $stmtPromedio = $this->conexion->query("
            SELECT AVG(duracion_minutos) 
            FROM historial_caidas 
            WHERE estado = 'resuelta' 
              AND MONTH(fecha_caida) = MONTH(NOW()) 
              AND YEAR(fecha_caida) = YEAR(NOW())
        ");
        $promedio = $stmtPromedio->fetchColumn();

        return [
            "activas" => (int)$activas,
            "hoy" => (int)$hoy,
            "promedio_minutos" => $promedio ? round($promedio, 1) : 0
        ];
    }

    public function obtenerDatosGrafica($dias = 7) {
        $stmt = $this->conexion->prepare("
            SELECT fecha_caida as fecha, duracion_minutos as valor, nombre_nodo 
            FROM historial_caidas 
            WHERE fecha_caida >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY fecha_caida ASC
        ");
        $stmt->execute([$dias]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
