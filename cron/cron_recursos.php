<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../DAO/MikrotikDAO.php';
require_once __DIR__ . '/../vendor/autoload.php';

use RouterOS\Client;

$dao = new MikrotikDAO();
$mikrotiks = $dao->listarActivos();
$con = (new Conexion())->conectar();

foreach ($mikrotiks as $m) {
    try {
        $client = new Client(['host' => $m['ip_address'], 'user' => $m['usuario'], 'pass' => $m['password'], 'port' => intval($m['puerto_api']), 'timeout' => 5]);
        
        // Recursos Básicos
        $resQuery = new \RouterOS\Query('/system/resource/print');
        $res = $client->query($resQuery)->read();
        
        if (isset($res[0])) {
            $cpu = isset($res[0]['cpu-load']) ? intval($res[0]['cpu-load']) : 0;
            $ram_total = isset($res[0]['total-memory']) ? intval($res[0]['total-memory']) : 0;
            $ram_libre = isset($res[0]['free-memory']) ? intval($res[0]['free-memory']) : 0;
            $hdd_total = isset($res[0]['total-hdd-space']) ? intval($res[0]['total-hdd-space']) : 0;
            $hdd_libre = isset($res[0]['free-hdd-space']) ? intval($res[0]['free-hdd-space']) : 0;
            $uptime = isset($res[0]['uptime']) ? $res[0]['uptime'] : '';
            $version = isset($res[0]['version']) ? $res[0]['version'] : '';

            $stmt = $con->prepare("INSERT INTO historico_recursos (mikrotik_id, cpu_uso, ram_total, ram_libre, disco_total, disco_libre, uptime, version_ros) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$m['id'], $cpu, $ram_total, $ram_libre, $hdd_total, $hdd_libre, $uptime, $version]);
        }

        // Interfaces Traffic
        $intQuery = new \RouterOS\Query('/interface/print');
        $interfaces = $client->query($intQuery)->read();
        
        if (is_array($interfaces)) {
            foreach ($interfaces as $intf) {
                // Filtrar interfaces: evitamos las "dinámicas" para no saturar BD
                // Y solo guardamos de las que estén corriendo
                if (isset($intf['name']) && (!isset($intf['dynamic']) || $intf['dynamic'] !== 'true') && (isset($intf['running']) && $intf['running'] === 'true')) {
                    $name = $intf['name'];
                    
                    $trafQuery = (new \RouterOS\Query('/interface/monitor-traffic'))
                        ->equal('interface', $name)
                        ->equal('once', '');
                    $traf = $client->query($trafQuery)->read();
                    
                    if (isset($traf[0])) {
                        $rx = isset($traf[0]['rx-bits-per-second']) ? intval($traf[0]['rx-bits-per-second']) : 0;
                        $tx = isset($traf[0]['tx-bits-per-second']) ? intval($traf[0]['tx-bits-per-second']) : 0;
                        
                        $stmt = $con->prepare("INSERT INTO historico_trafico (mikrotik_id, interface, rx_bits, tx_bits) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$m['id'], $name, $rx, $tx]);
                    }
                }
            }
        }
        
    } catch (\Exception $e) { }
}
echo "Cron Recursos completado.\n";
