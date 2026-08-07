<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!-- Header de la vista Equipos -->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-md-6 col-12">
        <h3 class="mb-0 fw-bold d-flex align-items-center">
          <i class="bi bi-hdd-network text-primary me-2"></i> Gestión de Equipos de Red
        </h3>
        <p class="text-muted small mb-0">Administración, monitoreo y diagnóstico de antenas, switches y servidores.</p>
      </div>
      <div class="col-md-6 col-12 text-md-end mt-2 mt-md-0">
        <button class="btn btn-primary btn-sm shadow-sm" onclick="openModalNuevoEquipo()">
          <i class="bi bi-plus-lg me-1"></i> Registrar Equipo
        </button>
      </div>
    </div>
  </div>
</div>

<div class="app-content mt-3">
  <div class="container-fluid">
    
    <!-- Tabla Principal de Equipos -->
    <div class="card card-outline card-primary shadow-sm">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-list-task me-2"></i> Inventario de Equipos</h3>
        <span class="badge text-bg-light border text-muted">DataTables</span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="tablaEquipos" class="table table-hover align-middle w-100">
            <thead class="table-light">
              <tr>
                <th style="width: 50px;">ID</th>
                <th>Nombre</th>
                <th>Dirección IP</th>
                <th>Comunidad SNMP</th>
                <th>Contacto / Notas</th>
                <th>Estado Ping</th>
                <th class="text-end" style="width: 170px;">Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Modales -->
<?php include __DIR__ . '/modal_crear.php'; ?>
<?php include __DIR__ . '/modal_editar.php'; ?>
<?php include __DIR__ . '/modal_ping.php'; ?>
<?php include __DIR__ . '/modal_estadisticas.php'; ?>
