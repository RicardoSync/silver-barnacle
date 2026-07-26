<?php
require_once __DIR__ . '/includes/config.php';

try {
    $conexion = (new Conexion())->conectar();
    
    $sql1 = "CREATE TABLE IF NOT EXISTS topologia_nodos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        tipo VARCHAR(50) DEFAULT 'ap',
        pos_x INT DEFAULT 150,
        pos_y INT DEFAULT 150,
        equipo_ref_id INT NULL,
        tipo_ref ENUM('mikrotik', 'equipo', 'custom') DEFAULT 'custom',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;";
    
    $conexion->exec($sql1);
    echo "Tabla 'topologia_nodos' verificada/creada correctamente.\n";
    
    $sql2 = "CREATE TABLE IF NOT EXISTS topologia_enlaces (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nodo_origen_id INT NOT NULL,
        nodo_destino_id INT NOT NULL,
        tipo_enlace VARCHAR(50) DEFAULT 'inalambrico',
        etiqueta VARCHAR(100) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (nodo_origen_id) REFERENCES topologia_nodos(id) ON DELETE CASCADE,
        FOREIGN KEY (nodo_destino_id) REFERENCES topologia_nodos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;";
    
    $conexion->exec($sql2);
    echo "Tabla 'topologia_enlaces' verificada/creada correctamente.\n";
    
} catch (Exception $e) {
    echo "Error en migración: " . $e->getMessage() . "\n";
}
