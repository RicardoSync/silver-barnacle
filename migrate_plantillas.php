<?php
require_once __DIR__ . '/includes/config.php';

try {
    $con = (new Conexion())->conectar();
    echo "Conectado a la base de datos exitosamente.\n";

    // 1. Tabla de plantillas de alerta
    $sqlPlantillas = "CREATE TABLE IF NOT EXISTS plantillas_alerta (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        minutos INT NOT NULL,
        mensaje TEXT NOT NULL,
        estado TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;";
    
    $con->exec($sqlPlantillas);
    echo "Tabla 'plantillas_alerta' creada/verificada.\n";

    // 2. Tabla historial de notificaciones enviadas por caída
    $sqlNotificaciones = "CREATE TABLE IF NOT EXISTS historial_notificaciones_caidas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        historial_caida_id INT NOT NULL,
        plantilla_alerta_id INT NOT NULL,
        fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (historial_caida_id) REFERENCES historial_caidas(id) ON DELETE CASCADE,
        FOREIGN KEY (plantilla_alerta_id) REFERENCES plantillas_alerta(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;";

    $con->exec($sqlNotificaciones);
    echo "Tabla 'historial_notificaciones_caidas' creada/verificada.\n";

    // 3. Insertar plantillas de alerta por defecto si la tabla está vacía
    $checkCount = $con->query("SELECT COUNT(*) FROM plantillas_alerta")->fetchColumn();
    if ($checkCount == 0) {
        $insertStmt = $con->prepare("INSERT INTO plantillas_alerta (nombre, minutos, mensaje, estado) VALUES (?, ?, ?, 1)");
        
        $defaultTemplates = [
            [
                'Alerta Simple (3 minutos)', 
                3, 
                "🚨 *ALERTA DE CAÍDA*\n\nEl nodo *%nombre%* (%tipo%) se encuentra CAÍDO. Ya superó los %minutos% minutos sin respuesta."
            ],
            [
                'Alerta Intermedia (30 minutos)', 
                30, 
                "🆘 *ALERTA CRÍTICA*\n\nEl nodo *%nombre%* (%tipo%) lleva más de %minutos% minutos CAÍDO. ¡Se requiere atención inmediata!"
            ],
            [
                'Alerta de Emergencia (2 horas)', 
                120, 
                "🔥 *EMERGENCIA DE RED*\n\nEl nodo *%nombre%* (%tipo%) lleva fuera de línea por %minutos% minutos. Acción urgente requerida."
            ]
        ];

        foreach ($defaultTemplates as $tpl) {
            $insertStmt->execute($tpl);
        }
        echo "Plantillas de alerta iniciales sembradas con éxito.\n";
    }

    echo "Migración de plantillas completada con éxito.\n";

} catch (Exception $e) {
    echo "Error en la migración: " . $e->getMessage() . "\n";
}
?>
