<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Carga modular de la lista y los modales CRUD
include __DIR__ . '/servicios/lista.php';
include __DIR__ . '/servicios/modal_crear.php';
include __DIR__ . '/servicios/modal_grafica.php';
?>
