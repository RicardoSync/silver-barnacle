<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../DAO/EquipoDAO.php';

$dao = new EquipoDAO();
$equipos = $dao->listarActivos();
$con = (new Conexion())->conectar();

if (empty($equipos)) {
    echo "No hay equipos activos para monitorear.\n";
    exit;
}

$jobs = [];
$temp_dir = sys_get_temp_dir();

// Lanzar pings en paralelo en segundo plano (Linux &)
foreach ($equipos as $eq) {
    $ip = escapeshellarg($eq['ip_address']);
    $id = intval($eq['id']);
    $temp_file = $temp_dir . "/ping_eq_{$id}.txt";
    
    // -c 1 (1 paquete), -W 2 (timeout de 2 segundos)
    shell_exec("ping -c 1 -W 2 $ip > " . escapeshellarg($temp_file) . " 2>&1 &");
    $jobs[$id] = $temp_file;
}

// Esperar el timeout máximo de ping (2 segundos) más un pequeño margen
usleep(2200000); // 2.2 segundos

// Procesar resultados
foreach ($jobs as $id => $temp_file) {
    $ms = 0;
    if (file_exists($temp_file)) {
        $output = file_get_contents($temp_file);
        // Eliminar archivo temporal
        unlink($temp_file);
        
        if (preg_match('/time=([\d\.]+)\s*ms/', $output, $matches)) {
            $ms = intval(round(floatval($matches[1])));
        }
    }
    
    // Guardar en base de datos
    $stmt = $con->prepare("INSERT INTO historico_pings_equipos (equipo_id, ms) VALUES (?, ?)");
    $stmt->execute([$id, $ms]);
}

echo "Cron Equipos Pings completado. Procesados: " . count($equipos) . " equipos.\n";
?>
