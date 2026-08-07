<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Carga modular de Plantillas de Alerta
include __DIR__ . '/plantillas_alerta/lista.php';
include __DIR__ . '/plantillas_alerta/modal_crear.php';
include __DIR__ . '/plantillas_alerta/modal_editar.php';
?>
