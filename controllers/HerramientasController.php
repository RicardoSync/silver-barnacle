<?php
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action !== 'ping_stream' && $action !== 'traceroute_stream') {
    header('Content-Type: application/json');
}

require_once __DIR__ . '/../includes/config.php';

switch ($action) {
    case 'ping_stream':
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', 1);
        }
        @ini_set('zlib.output_compression', 0);
        @ini_set('implicit_flush', 1);
        
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $target = isset($_GET['target']) ? trim($_GET['target']) : '';
        if (empty($target) || (!filter_var($target, FILTER_VALIDATE_IP) && !preg_match('/^([a-zA-Z0-9\.\-]+)$/i', $target))) {
            echo "data: " . json_encode(["line" => "Error: Destino inválido"]) . "\n\n";
            exit;
        }

        $target_escaped = escapeshellarg($target);
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        $cmd = $isWindows ? "ping -n 8 -w 1000 {$target_escaped}" : "ping -c 8 -W 1 {$target_escaped} 2>&1";
        
        $handle = popen($cmd, 'r');
        if ($handle) {
            while (!feof($handle)) {
                $line = fgets($handle);
                if ($line === false) break;
                $line_utf8 = mb_convert_encoding($line, 'UTF-8', 'ISO-8859-1, UTF-8');
                echo "data: " . json_encode(["line" => rtrim($line_utf8)]) . "\n\n";
                @ob_flush();
                flush();
            }
            pclose($handle);
        } else {
            echo "data: " . json_encode(["line" => "Error al ejecutar ping."]) . "\n\n";
        }
        break;

    case 'traceroute_stream':
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', 1);
        }
        @ini_set('zlib.output_compression', 0);
        @ini_set('implicit_flush', 1);
        
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $target = isset($_GET['target']) ? trim($_GET['target']) : '';
        if (empty($target) || (!filter_var($target, FILTER_VALIDATE_IP) && !preg_match('/^([a-zA-Z0-9\.\-]+)$/i', $target))) {
            echo "data: " . json_encode(["line" => "Error: Destino inválido"]) . "\n\n";
            exit;
        }

        $target_escaped = escapeshellarg($target);
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        $cmd = $isWindows ? "tracert -d -h 15 {$target_escaped}" : "traceroute -n -m 15 {$target_escaped} 2>&1";
        
        $handle = popen($cmd, 'r');
        if ($handle) {
            while (!feof($handle)) {
                $line = fgets($handle);
                if ($line === false) break;
                $line_utf8 = mb_convert_encoding($line, 'UTF-8', 'ISO-8859-1, UTF-8');
                echo "data: " . json_encode(["line" => rtrim($line_utf8)]) . "\n\n";
                @ob_flush();
                flush();
            }
            pclose($handle);
        } else {
            echo "data: " . json_encode(["line" => "Error al ejecutar traceroute."]) . "\n\n";
        }
        break;

    case 'traceroute':
        $target = isset($_GET['target']) ? trim($_GET['target']) : '';
        if (empty($target)) {
            echo json_encode(array("status" => "error", "message" => "Ingrese un destino válido"));
            exit;
        }

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
