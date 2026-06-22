<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-router text-secondary me-2"></i> Gestión de MikroTiks</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoMikrotik"><i class="bi bi-plus-lg"></i> Agregar MikroTik</button>
</div>

<?php
// Include the sub-views
include 'mikrotik/lista.php';
include 'mikrotik/modal_nuevo.php';
include 'mikrotik/modal_editar.php';
?>
