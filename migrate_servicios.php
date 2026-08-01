<?php
require_once __DIR__ . '/includes/config.php';

try {
    $con = (new Conexion())->conectar();
    echo "Conectado a la base de datos exitosamente.\n";

    // 1. Tabla de servicios a monitorear (DNS, HTTP/HTTPS, Puertos TCP)
    $sqlServicios = "CREATE TABLE IF NOT EXISTS servicios_monitoreo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        tipo ENUM('dns', 'http', 'puerto') NOT NULL,
        target VARCHAR(255) NOT NULL,
        puerto INT NULL,
        umbral_ms INT DEFAULT 300,
        estado BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;";
    
    $con->exec($sqlServicios);
    echo "Tabla 'servicios_monitoreo' verificada/creada.\n";

    // 2. Tabla de historial de chequeos de servicios
    $sqlHistorico = "CREATE TABLE IF NOT EXISTS historico_servicios (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        servicio_id INT NOT NULL,
        ms INT NOT NULL,
        codigo_http INT NULL,
        ip_resuelta VARCHAR(45) NULL,
        estado_check ENUM('online', 'lento', 'offline') NOT NULL,
        detalle TEXT NULL,
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (servicio_id) REFERENCES servicios_monitoreo(id) ON DELETE CASCADE,
        INDEX idx_servicio_fecha (servicio_id, fecha_registro)
    ) ENGINE=InnoDB;";
    
    $con->exec($sqlHistorico);
    echo "Tabla 'historico_servicios' verificada/creada.\n";

    // 3. Insertar registros por defecto si la tabla está vacía
    $checkCount = $con->query("SELECT COUNT(*) FROM servicios_monitoreo")->fetchColumn();
    if ($checkCount == 0) {
        $insertStmt = $con->prepare("INSERT INTO servicios_monitoreo (nombre, tipo, target, puerto, umbral_ms, estado) VALUES (?, ?, ?, ?, ?, 1)");
        
        $defaultServices = [
            ['Google DNS', 'dns', 'google.com', null, 200],
            ['Cloudflare DNS', 'dns', 'cloudflare.com', null, 200],
            ['OpenDNS', 'dns', 'opendns.com', null, 200],
            ['Google Web', 'http', 'https://www.google.com', null, 500],
            ['Facebook Web', 'http', 'https://www.facebook.com', null, 500],
            ['Netflix Web', 'http', 'https://www.netflix.com', null, 500],
            ['Puerto DNS Google (8.8.8.8:53)', 'puerto', '8.8.8.8', 53, 300],
            ['Puerto DNS Cloudflare (1.1.1.1:53)', 'puerto', '1.1.1.1', 53, 300]
        ];

        foreach ($defaultServices as $serv) {
            $insertStmt->execute($serv);
        }
        echo "Servicios iniciales insertados con éxito.\n";
    }

    echo "Migración de Servicios completada exitosamente.\n";

} catch (Exception $e) {
    echo "Error en la migración: " . $e->getMessage() . "\n";
}
?>
