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

    public function listarPorTiempo($horas = 24) {
        $minutos = round(floatval($horas) * 60);
        if ($minutos <= 0) $minutos = 1440;

        $stmt = $this->conexion->prepare("
            SELECT id, nodo_id, tipo_nodo, nombre_nodo, fecha_caida, fecha_recuperacion, duracion_minutos, estado 
            FROM historial_caidas 
            WHERE fecha_caida >= DATE_SUB(NOW(), INTERVAL :minutos MINUTE) OR estado = 'en_curso'
            ORDER BY fecha_caida DESC
        ");
        $stmt->bindParam(':minutos', $minutos, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerKPIs($horas = 24) {
        $minutos = round(floatval($horas) * 60);
        if ($minutos <= 0) $minutos = 1440;

        // Caídas Activas ('en_curso')
        $stmtActivas = $this->conexion->query("SELECT COUNT(*) FROM historial_caidas WHERE estado = 'en_curso'");
        $activas = $stmtActivas->fetchColumn();

        // Caídas en el rango de tiempo seleccionado
        $stmtRango = $this->conexion->prepare("SELECT COUNT(*) FROM historial_caidas WHERE fecha_caida >= DATE_SUB(NOW(), INTERVAL :minutos MINUTE)");
        $stmtRango->bindParam(':minutos', $minutos, PDO::PARAM_INT);
        $stmtRango->execute();
        $rangoCount = $stmtRango->fetchColumn();

        // Tiempo Promedio en el rango seleccionado
        $stmtPromedio = $this->conexion->prepare("
            SELECT AVG(duracion_minutos) 
            FROM historial_caidas 
            WHERE fecha_caida >= DATE_SUB(NOW(), INTERVAL :minutos MINUTE)
        ");
        $stmtPromedio->bindParam(':minutos', $minutos, PDO::PARAM_INT);
        $stmtPromedio->execute();
        $promedio = $stmtPromedio->fetchColumn();

        // Máxima duración en minutos en el rango
        $stmtMax = $this->conexion->prepare("
            SELECT MAX(duracion_minutos) 
            FROM historial_caidas 
            WHERE fecha_caida >= DATE_SUB(NOW(), INTERVAL :minutos MINUTE)
        ");
        $stmtMax->bindParam(':minutos', $minutos, PDO::PARAM_INT);
        $stmtMax->execute();
        $maxDuracion = $stmtMax->fetchColumn();

        return [
            "activas" => (int)$activas,
            "rango" => (int)$rangoCount,
            "promedio_minutos" => $promedio ? round($promedio, 1) : 0,
            "max_minutos" => $maxDuracion ? (int)$maxDuracion : 0
        ];
    }

    public function obtenerDatosGrafica($horas = 24) {
        $minutos = round(floatval($horas) * 60);
        if ($minutos <= 0) $minutos = 1440;

        $stmt = $this->conexion->prepare("
            SELECT id, fecha_caida as fecha, duracion_minutos as valor, nombre_nodo, tipo_nodo, estado 
            FROM historial_caidas 
            WHERE fecha_caida >= DATE_SUB(NOW(), INTERVAL :minutos MINUTE) OR estado = 'en_curso'
            ORDER BY fecha_caida ASC
        ");
        $stmt->bindParam(':minutos', $minutos, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
