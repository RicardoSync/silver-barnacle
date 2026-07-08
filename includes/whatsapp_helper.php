<?php

// mejora 1 de septiembre 2025.
function generarLinkReciboPublico($conn, $id_recibo) {
    // 1. Validar si los enlaces están activos globalmente
    $sql = "SELECT url_sistema, api_secret, enlaces_publicos_activos FROM whatsapp_config WHERE id = 1 LIMIT 1";
    $res = $conn->query($sql);
    $conf = $res->fetch_assoc();
    
    // Si la configuración dice que están desactivados, retorno null de inmediato
    if (!$conf || $conf['enlaces_publicos_activos'] == 0 || empty($conf['url_sistema'])) {
        return null;
    }

    $baseUrl = rtrim($conf['url_sistema'], '/') . '/';
    return $baseUrl . "controllers/GenerarTicket.php?id=" . $id_recibo . "&auth_token=" . $conf['api_secret'];
}
// mejora 24 de marzo 2026.
function enviarNotificacionWhatsApp($conn, $telefono, $slug, $variables = [], $urlPdf = null) {
    
    // 1. OBTENER CONFIGURACIÓN DESDE LA BASE DE DATOS
    // Ya no usamos variables fijas, leemos de la tabla whatsapp_config
    $sqlConfig = "SELECT * FROM whatsapp_config WHERE id = 1 LIMIT 1";
    $resConfig = $conn->query($sqlConfig);
    $config = $resConfig->fetch_assoc();

    // Validaciones
    if (!$config || $config['activo'] == 0) {
        return ["success" => false, "error" => "WhatsApp desactivado en BD"];
    }
    
    // Asignamos variables desde la BD
    $wahaApiKey = $config['waha_api_key']; 
    $wahaUrl    = $config['waha_url'];

    // 2. OBTENER PLANTILLA
    $stmt = $conn->prepare("SELECT mensaje FROM whatsapp_plantillas WHERE slug = ? LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $plantilla = $stmt->get_result()->fetch_assoc();

    if (!$plantilla) {
        return ["success" => false, "error" => "No existe plantilla: $slug"];
    }

    // 3. REEMPLAZAR VARIABLES
    $mensajeFinal = $plantilla['mensaje'];
    foreach ($variables as $clave => $valor) {
        $mensajeFinal = str_replace($clave, $valor, $mensajeFinal);
    }
    
    if ($urlPdf) {
        $mensajeFinal .= "\n\n📄 Descarga tu comprobante aquí:\n" . $urlPdf;
    }

    // 4. LIMPIEZA DE NÚMERO
    $telefono = preg_replace('/[^0-9]/', '', $telefono); 
    
    if (strlen($telefono) == 10) {
        $telefono = "521" . $telefono;
    } elseif (strlen($telefono) == 12 && substr($telefono, 0, 2) == "52") {
         $numero = substr($telefono, 2);
         $telefono = "521" . $numero;
    }

    $chatId = $telefono . "@c.us"; 

    // 5. ENVIAR A WAHA
    $payload = [
        "chatId" => $chatId,
        "text" => $mensajeFinal,
        "session" => "default" 
    ];

    $ch = curl_init($wahaUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
    
    $headers = [
        'Content-Type: application/json',
        'X-Api-Key: ' . $wahaApiKey
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $respuesta = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode == 201 || $httpCode == 200) {
        return ["success" => true, "msg" => "Enviado por WAHA"];
    } else {
        return ["success" => false, "error" => "Error WAHA ($httpCode): " . $respuesta . " " . $curlError];
    }
}

// mejora 8 de marzo 2026.
function enviarNotificacionCustomWhatsApp($conn, $telefono, $mensaje) {
    // 1. OBTENER CONFIGURACIÓN DESDE LA BASE DE DATOS
    $sqlConfig = "SELECT * FROM whatsapp_config WHERE id = 1 LIMIT 1";
    if ($conn instanceof PDO) {
        $resConfig = $conn->query($sqlConfig);
        $config = $resConfig->fetch(PDO::FETCH_ASSOC);
    } else {
        $resConfig = $conn->query($sqlConfig);
        $config = $resConfig->fetch_assoc();
    }

    if (!$config || $config['activo'] == 0) {
        return ["success" => false, "error" => "WhatsApp desactivado en BD"];
    }
    
    $wahaApiKey = $config['waha_api_key']; 
    $wahaUrl    = $config['waha_url'];

    // 2. LIMPIEZA DE NÚMERO
    $telefono = preg_replace('/[^0-9]/', '', $telefono); 
    if (strlen($telefono) == 10) {
        $telefono = "521" . $telefono;
    } elseif (strlen($telefono) == 12 && substr($telefono, 0, 2) == "52") {
         $numero = substr($telefono, 2);
         $telefono = "521" . $numero;
    }
    $chatId = $telefono . "@c.us"; 

    // 3. ENVIAR A WAHA
    $payload = [
        "chatId" => $chatId,
        "text" => $mensaje,
        "session" => "default" 
    ];

    $ch = curl_init($wahaUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
    
    $headers = [
        'Content-Type: application/json',
        'X-Api-Key: ' . $wahaApiKey
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $respuesta = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode == 201 || $httpCode == 200) {
        return ["success" => true, "msg" => "Enviado por WAHA"];
    } else {
        return ["success" => false, "error" => "Error WAHA ($httpCode): " . $respuesta . " " . $curlError];
    }
}
?>