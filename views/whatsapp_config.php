<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Carga modular de Configuración de WhatsApp (Lista/Form y Modal Test)
include __DIR__ . '/whatsapp_config/lista.php';
include __DIR__ . '/whatsapp_config/modal_editar.php';
?>
