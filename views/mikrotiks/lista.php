<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!-- Header de la página -->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-sm-6">
        <h3 class="mb-0 fw-bold">Gestión de MikroTiks</h3>
        <p class="text-muted small mb-0">Inventario y estado en tiempo real de los dispositivos MikroTik de la red</p>
      </div>
      <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
        <button class="btn btn-primary shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNuevoMikrotik">
          <i class="bi bi-plus-lg me-1"></i> Registrar MikroTik
        </button>
      </div>
    </div>
  </div>
</div>

<div class="app-content mt-3">
  <div class="container-fluid">
    <div class="card card-outline card-primary shadow-sm">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-list-task me-2"></i>Lista de Equipos Registrados</h3>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="tablaMikrotiks" class="table table-hover align-middle w-100">
            <thead class="table-light">
              <tr>
                <th style="width: 50px;">ID</th>
                <th>Nombre del Router</th>
                <th>Dirección IP</th>
                <th style="width: 100px;">Puerto API</th>
                <th style="width: 100px;">Estado</th>
                <th style="width: 160px;">Conexión API (Live)</th>
                <th style="width: 150px;" class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <!-- Se puebla dinámicamente mediante DataTables y AJAX -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Inclusion de Modales -->
<?php include __DIR__ . '/modal_crear.php'; ?>
<?php include __DIR__ . '/modal_editar.php'; ?>
