<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'traceroute':
        $target = isset($_GET['target']) ? trim($_GET['target']) : '';
        if (empty($target)) {
            echo json_encode(array("status" => "error", "message" => "Ingrese un destino válido"));
            exit;
        }

        // Validar IP o Dominio
        if (!filter_var($target, FILTER_VALIDATE_IP) && !preg_match('/^([a-zA-Z0-9\.\-]+)$/i', $target)) {
            echo json_encode(array("status" => "error", "message" => "Destino inválido"));
            exit;
        }

        $target_escaped = escapeshellarg($target);
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        $cmd = $isWindows ? "tracert -d -h 20 {$target_escaped}" : "traceroute -n -m 20 {$target_escaped} 2>&1";
        $output = shell_exec($cmd);

        $hops = [];
        if ($output) {
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                if ($isWindows) {
                    if (preg_match('/^\s*(\d+)\s+([\<\d\.\sms\*]+)\s+([\d\.\*a-zA-Z]+)/i', $line, $matches)) {
                        $hopNum = intval($matches[1]);
                        preg_match_all('/([\d\.]+)\s*ms/i', $line, $msMatches);
                        $msVals = array_map('floatval', $msMatches[1] ?? []);
                        $avgMs = count($msVals) > 0 ? round(array_sum($msVals) / count($msVals), 1) : null;
                        
                        preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/i', $line, $ipMatches);
                        $ip = $ipMatches[1] ?? '*';
                        $host = ($ip !== '*') ? @gethostbyaddr($ip) : '*';

                        $hops[] = [
                            'hop' => $hopNum,
                            'ip' => $ip,
                            'hostname' => $host,
                            'ms' => $avgMs,
                            'raw' => $line
                        ];
                    }
                } else {
                    if (preg_match('/^\s*(\d+)\s+([\d\.\*]+)\s+(.+)$/i', $line, $matches)) {
                        $hopNum = intval($matches[1]);
                        $ip = $matches[2];
                        $rest = $matches[3];

                        preg_match_all('/([\d\.]+)\s*ms/i', $rest, $msMatches);
                        $msVals = array_map('floatval', $msMatches[1] ?? []);
                        $avgMs = count($msVals) > 0 ? round(array_sum($msVals) / count($msVals), 1) : null;

                        $host = ($ip !== '*' && filter_var($ip, FILTER_VALIDATE_IP)) ? @gethostbyaddr($ip) : '*';

                        $hops[] = [
                            'hop' => $hopNum,
                            'ip' => $ip,
                            'hostname' => $host,
                            'ms' => $avgMs,
                            'raw' => $line
                        ];
                    }
                }
            }
        }

        echo json_encode(array(
            "status" => "success",
            "target" => $target,
            "hops" => $hops
        ));
        break;

    default:
        echo json_encode(array("status" => "error", "message" => "Acción no válida"));
        break;
}
?>
