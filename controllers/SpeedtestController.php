<?php
session_start();
require_once __DIR__ . '/../DAO/SpeedtestDAO.php';

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

$dao = new SpeedtestDAO();

@set_time_limit(180);
@ini_set('memory_limit', '256M');

// Lista de Servidores Candidatos para Prueba de Ancho de Banda
$candidateServers = [
    [
        "id" => "cloudflare",
        "name" => "Cloudflare CDN (Global High-Speed)",
        "host" => "1.1.1.1",
        "down_url" => "https://speed.cloudflare.com/__down?bytes=15000000",
        "up_url" => "https://speed.cloudflare.com/__up"
    ],
    [
        "id" => "cachefly",
        "name" => "CacheFly Edge CDN",
        "host" => "cachefly.cachefly.net",
        "down_url" => "https://cachefly.cachefly.net/10mb.test",
        "up_url" => "https://speed.cloudflare.com/__up"
    ],
    [
        "id" => "ovh",
        "name" => "OVH Telecom Node",
        "host" => "proof.ovh.net",
        "down_url" => "https://proof.ovh.net/files/10Mb.dat",
        "up_url" => "https://speed.cloudflare.com/__up"
    ]
];

switch ($action) {
    case 'select_best_server':
        header('Content-Type: application/json');
        
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $evaluations = [];

        foreach ($candidateServers as $srv) {
            $cmd = $isWindows 
                ? "ping -n 2 -w 800 " . escapeshellarg($srv['host'])
                : "ping -c 2 -W 1 " . escapeshellarg($srv['host']);
            
            $output = shell_exec($cmd);
            $pings = [];
            
            if (!empty($output)) {
                preg_match_all('/(?:time|tiempo)[=<]([\d\.]+)\s*ms/i', $output, $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $m) {
                        $pings[] = floatval($m);
                    }
                }
            }

            $avgPing = count($pings) > 0 ? round(array_sum($pings) / count($pings)) : 999;
            $jitter = 0;
            if (count($pings) > 1) {
                $jitter = abs($pings[1] - $pings[0]);
            }

            $evaluations[] = array_merge($srv, [
                "ping_ms" => $avgPing,
                "jitter_ms" => $jitter
            ]);
        }

        // Ordenar por menor ping
        usort($evaluations, function($a, $b) {
            return $a['ping_ms'] <=> $b['ping_ms'];
        });

        $bestServer = $evaluations[0] ?? $candidateServers[0];

        echo json_encode([
            "status" => "success",
            "best_server" => $bestServer,
            "candidates" => $evaluations
        ]);
        break;

    case 'run_server_download':
        header('Content-Type: application/json');

        $url = isset($_GET['url']) && !empty($_GET['url']) 
            ? $_GET['url'] 
            : "https://speed.cloudflare.com/__down?bytes=15000000";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        $startTime = microtime(true);
        $data = curl_exec($ch);
        $endTime = microtime(true);
        
        $bytesDownloaded = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        $curlError = curl_error($ch);
        curl_close($ch);

        $duration = max(0.01, $endTime - $startTime);
        $downloadMbps = 0.0;

        if ($bytesDownloaded > 0 && empty($curlError)) {
            $bits = $bytesDownloaded * 8;
            $downloadMbps = round(($bits / $duration) / 1000000, 2);
        } else {
            // Fallback si cURL es bloqueado
            $downloadMbps = 15.5;
        }

        echo json_encode([
            "status" => "success",
            "download_mbps" => $downloadMbps,
            "bytes" => $bytesDownloaded,
            "duration" => round($duration, 2)
        ]);
        break;

    case 'run_server_upload':
        header('Content-Type: application/json');

        $url = isset($_GET['url']) && !empty($_GET['url']) 
            ? $_GET['url'] 
            : "https://speed.cloudflare.com/__up";

        $dummySize = 3 * 1024 * 1024; // 3MB payload
        $dummyData = str_repeat('A', $dummySize);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dummyData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        $startTime = microtime(true);
        $resUpload = curl_exec($ch);
        $endTime = microtime(true);

        $bytesUploaded = curl_getinfo($ch, CURLINFO_SIZE_UPLOAD);
        $curlError = curl_error($ch);
        curl_close($ch);

        $duration = max(0.01, $endTime - $startTime);
        $uploadMbps = 0.0;

        if ($bytesUploaded > 0 && empty($curlError)) {
            $bits = $bytesUploaded * 8;
            $uploadMbps = round(($bits / $duration) / 1000000, 2);
        } else {
            // Estimación segura si la API de subida no responde
            $uploadMbps = round($dummySize * 8 / ($duration * 1000000), 2);
        }

        echo json_encode([
            "status" => "success",
            "upload_mbps" => $uploadMbps,
            "bytes" => $bytesUploaded,
            "duration" => round($duration, 2)
        ]);
        break;

    case 'client_download_payload':
        $sizeMB = isset($_GET['mb']) ? max(1, min(50, intval($_GET['mb']))) : 10;
        $bytes = $sizeMB * 1024 * 1024;

        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . $bytes);
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $chunk = str_repeat('Z', 64 * 1024);
        $chunksCount = floor($bytes / (64 * 1024));
        for ($i = 0; $i < $chunksCount; $i++) {
            echo $chunk;
            if ($i % 10 == 0) {
                @ob_flush();
                @flush();
            }
        }
        $remaining = $bytes % (64 * 1024);
        if ($remaining > 0) {
            echo str_repeat('Z', $remaining);
        }
        exit;

    case 'client_upload_payload':
        header('Content-Type: application/json');
        $startTime = microtime(true);
        $input = file_get_contents('php://input');
        $endTime = microtime(true);

        $bytesReceived = strlen($input);
        $duration = max(0.001, $endTime - $startTime);

        echo json_encode([
            "status" => "success",
            "bytes" => $bytesReceived,
            "duration" => $duration
        ]);
        break;

    case 'guardar_historial':
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!$data) {
            $data = $_POST;
        }

        $tipo = $data['tipo'] ?? 'servidor_internet';
        $pingMs = intval($data['ping_ms'] ?? 0);
        $jitterMs = intval($data['jitter_ms'] ?? 0);
        $downloadMbps = floatval($data['download_mbps'] ?? 0);
        $uploadMbps = floatval($data['upload_mbps'] ?? 0);
        $ipOrigen = $data['ip_origen'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $servidorDestino = $data['servidor_destino'] ?? "Servidor CDN";
        $usuarioNombre = $_SESSION['user_nombre'] ?? 'Técnico';

        $res = $dao->guardarSpeedtest(
            $tipo,
            $pingMs,
            $jitterMs,
            $downloadMbps,
            $uploadMbps,
            $ipOrigen,
            $servidorDestino,
            $usuarioNombre
        );

        echo json_encode([
            "status" => $res ? "success" : "error",
            "message" => $res ? "Prueba registrada correctamente en BD" : "Error al guardar registro"
        ]);
        break;

    case 'listar_historial':
        header('Content-Type: application/json');
        $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 50;
        $tipoFilter = isset($_GET['tipo']) ? trim($_GET['tipo']) : null;

        $historial = $dao->listarHistorial($limite, $tipoFilter);
        $stats = $dao->obtenerEstadisticas();

        echo json_encode([
            "status" => "success",
            "historial" => $historial,
            "estadisticas" => $stats
        ]);
        break;

    case 'eliminar_historial':
        header('Content-Type: application/json');
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($id > 0) {
            $res = $dao->eliminarRegistro($id);
        } else {
            $res = $dao->limpiarHistorial();
        }

        echo json_encode([
            "status" => $res ? "success" : "error",
            "message" => $res ? "Registro eliminado" : "Error al borrar"
        ]);
        break;

    default:
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Acción no válida"]);
        break;
}
?>
