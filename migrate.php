<?php
require_once __DIR__ . '/includes/config.php';

$con = new Conexion();
$db = $con->conectar();

$sql1 = "CREATE TABLE IF NOT EXISTS historico_pings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    mikrotik_id INT NOT NULL,
    tipo ENUM('google', 'servidor') NOT NULL, 
    ms INT NOT NULL,                          
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mikrotik_id) REFERENCES mikrotiks(id) ON DELETE CASCADE,
    INDEX idx_ping_fecha (mikrotik_id, tipo, fecha_registro)
) ENGINE=InnoDB;";

$sql2 = "CREATE TABLE IF NOT EXISTS historico_trafico (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    mikrotik_id INT NOT NULL,
    interface VARCHAR(100) NOT NULL,
    rx_bits BIGINT NOT NULL DEFAULT 0,
    tx_bits BIGINT NOT NULL DEFAULT 0,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mikrotik_id) REFERENCES mikrotiks(id) ON DELETE CASCADE,
    INDEX idx_trafico_fecha (mikrotik_id, interface, fecha_registro)
) ENGINE=InnoDB;";

$db->exec($sql1);
$db->exec($sql2);

echo "Tables created successfully.";
