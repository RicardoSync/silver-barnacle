<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../DTO/SpeedtestDTO.php';

class SpeedtestDAO {
    private $conexion;

    public function __construct() {
        $con = new Conexion();
        $this->conexion = $con->conectar();
        $this->initTabla();
    }

    private function initTabla() {
        $sql = "CREATE TABLE IF NOT EXISTS speedtest_historial (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            tipo ENUM('servidor_internet', 'cliente_servidor') NOT NULL DEFAULT 'servidor_internet',
            ping_ms INT NOT NULL DEFAULT 0,
            jitter_ms INT NOT NULL DEFAULT 0,
            download_mbps DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            upload_mbps DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            ip_origen VARCHAR(45) NULL,
            servidor_destino VARCHAR(255) NULL,
            usuario_nombre VARCHAR(100) NULL,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_fecha (fecha_registro),
            INDEX idx_tipo (tipo)
        ) ENGINE=InnoDB;";
        $this->conexion->exec($sql);
    }

    public function guardarSpeedtest($tipo, $pingMs, $jitterMs, $downloadMbps, $uploadMbps, $ipOrigen = null, $servidorDestino = null, $usuarioNombre = null) {
        $stmt = $this->conexion->prepare("
            INSERT INTO speedtest_historial 
            (tipo, ping_ms, jitter_ms, download_mbps, upload_mbps, ip_origen, servidor_destino, usuario_nombre) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $tipo,
            intval($pingMs),
            intval($jitterMs),
            floatval($downloadMbps),
            floatval($uploadMbps),
            $ipOrigen,
            $servidorDestino,
            $usuarioNombre
        ]);
    }

    public function listarHistorial($limite = 50, $tipo = null) {
        $sql = "SELECT * FROM speedtest_historial";
        $params = [];
        
        if (!empty($tipo)) {
            $sql .= " WHERE tipo = ?";
            $params[] = $tipo;
        }

        $sql .= " ORDER BY fecha_registro DESC LIMIT " . intval($limite);
        
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEstadisticas() {
        $stmtServidor = $this->conexion->query("
            SELECT 
                COUNT(*) as total,
                AVG(download_mbps) as avg_download,
                AVG(upload_mbps) as avg_upload,
                AVG(ping_ms) as avg_ping,
                MAX(download_mbps) as max_download,
                MAX(upload_mbps) as max_upload
            FROM speedtest_historial 
            WHERE tipo = 'servidor_internet'
        ");
        $statsServidor = $stmtServidor->fetch(PDO::FETCH_ASSOC);

        $stmtCliente = $this->conexion->query("
            SELECT 
                COUNT(*) as total,
                AVG(download_mbps) as avg_download,
                AVG(upload_mbps) as avg_upload,
                AVG(ping_ms) as avg_ping,
                MAX(download_mbps) as max_download,
                MAX(upload_mbps) as max_upload
            FROM speedtest_historial 
            WHERE tipo = 'cliente_servidor'
        ");
        $statsCliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

        // Obtener historial para gráfica de tendencia (últimas 30 mediciones)
        $stmtGrafica = $this->conexion->query("
            SELECT id, tipo, download_mbps, upload_mbps, ping_ms, fecha_registro
            FROM speedtest_historial
            ORDER BY fecha_registro DESC
            LIMIT 30
        ");
        $grafica = array_reverse($stmtGrafica->fetchAll(PDO::FETCH_ASSOC));

        return [
            "servidor" => [
                "total" => intval($statsServidor['total'] ?? 0),
                "avg_download" => round(floatval($statsServidor['avg_download'] ?? 0), 2),
                "avg_upload" => round(floatval($statsServidor['avg_upload'] ?? 0), 2),
                "avg_ping" => round(floatval($statsServidor['avg_ping'] ?? 0), 1),
                "max_download" => round(floatval($statsServidor['max_download'] ?? 0), 2),
                "max_upload" => round(floatval($statsServidor['max_upload'] ?? 0), 2)
            ],
            "cliente" => [
                "total" => intval($statsCliente['total'] ?? 0),
                "avg_download" => round(floatval($statsCliente['avg_download'] ?? 0), 2),
                "avg_upload" => round(floatval($statsCliente['avg_upload'] ?? 0), 2),
                "avg_ping" => round(floatval($statsCliente['avg_ping'] ?? 0), 1),
                "max_download" => round(floatval($statsCliente['max_download'] ?? 0), 2),
                "max_upload" => round(floatval($statsCliente['max_upload'] ?? 0), 2)
            ],
            "grafica" => $grafica
        ];
    }

    public function eliminarRegistro($id) {
        $stmt = $this->conexion->prepare("DELETE FROM speedtest_historial WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function limpiarHistorial() {
        $stmt = $this->conexion->prepare("DELETE FROM speedtest_historial");
        return $stmt->execute();
    }
}
?>
