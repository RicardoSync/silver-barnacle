<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../DAO/MikrotikDAO.php';
require_once __DIR__ . '/../vendor/autoload.php';

use RouterOS\Client;

$dao = new MikrotikDAO();
$mikrotiks = $dao->listarActivos();
$con = (new Conexion())->conectar();

foreach ($mikrotiks as $m) {
    // 1. Ping Servidor -> Mikrotik
    $ip = escapeshellarg($m['ip_address']);
    // -c 1 = 1 packet, -W 2 = 2s timeout
    $output = shell_exec("ping -c 1 -W 2 $ip 2>&1");
    $ms_servidor = 0;
    if (preg_match('/time=([\d\.]+)\s*ms/', $output, $matches)) {
        $ms_servidor = intval(round(floatval($matches[1])));
    }
    
    $stmt = $con->prepare("INSERT INTO historico_pings (mikrotik_id, tipo, ms) VALUES (?, 'servidor', ?)");
    $stmt->execute([$m['id'], $ms_servidor]);

    // 2. Ping Mikrotik -> Google via API
    $ms_google = 0;
    try {
        $client = new Client(['host' => $m['ip_address'], 'user' => $m['usuario'], 'pass' => $m['password'], 'port' => intval($m['puerto_api']), 'timeout' => 3]);
        $query = (new \RouterOS\Query('/ping'))
            ->equal('address', '8.8.8.8')
            ->equal('count', '1');
        $response = $client->query($query)->read();
        if(isset($response[0]['time'])) {
            $time_str = $response[0]['time'];
            if (strpos($time_str, 's') !== false && strpos($time_str, 'ms') !== false) {
                $parts = explode('s', $time_str);
                $ms_google = (intval($parts[0]) * 1000) + intval(str_replace('ms', '', $parts[1]));
            } elseif (strpos($time_str, 'ms') !== false) {
                $ms_google = intval(str_replace('ms', '', $time_str));
            } elseif (strpos($time_str, 's') !== false) {
                $ms_google = intval(str_replace('s', '', $time_str)) * 1000;
            }
        }
    } catch (\Exception $e) { }

    $stmt = $con->prepare("INSERT INTO historico_pings (mikrotik_id, tipo, ms) VALUES (?, 'google', ?)");
    $stmt->execute([$m['id'], $ms_google]);
}
echo "Cron Pings completado.\n";
