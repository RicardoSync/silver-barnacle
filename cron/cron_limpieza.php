<?php
require_once __DIR__ . '/../includes/config.php';

try {
    $con = (new Conexion())->conectar();
    
    $tablas = [
        'historial_caidas' => 'fecha_caida',
        'historico_pings_equipos' => 'fecha_registro',
        'historico_trafico' => 'fecha_registro',
        'historico_pings' => 'fecha_registro',
        'historico_recursos' => 'fecha_registro'
    ];
    
    foreach ($tablas as $tabla => $campo_fecha) {
        $sql = "DELETE FROM $tabla WHERE $campo_fecha < NOW() - INTERVAL 7 DAY";
        $con->exec($sql);
    }
    
    echo "Cron Limpieza completado: registros mayores a 7 días eliminados.\n";
} catch (Exception $e) {
    echo "Error en Cron Limpieza: " . $e->getMessage() . "\n";
}
