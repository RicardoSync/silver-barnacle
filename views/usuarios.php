<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Carga modular de Usuarios (Lista y Modales CRUD)
include __DIR__ . '/usuarios/lista.php';
include __DIR__ . '/usuarios/modal_crear.php';
include __DIR__ . '/usuarios/modal_editar.php';
?>
