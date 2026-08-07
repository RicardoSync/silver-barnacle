<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!-- Header de Sección -->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-md-6 col-12">
        <h3 class="mb-0 fw-bold d-flex align-items-center">
          <i class="bi bi-globe text-primary me-2"></i> Monitoreo DNS y Servicios
        </h3>
        <p class="text-muted small mb-0">Supervisión continua de servidores DNS, sitios web HTTP(S) y puertos TCP estratégicos.</p>
      </div>
      <div class="col-md-6 col-12 text-md-end mt-2 mt-md-0">
        <button class="btn btn-sm btn-success shadow-sm" onclick="abrirModalCrearServicio()">
          <i class="bi bi-plus-circle me-1"></i> Nuevo Servicio
        </button>
      </div>
    </div>
  </div>
</div>

<div class="app-content mt-3">
  <div class="container-fluid">

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
      
      <div class="col-lg-3 col-6">
        <div class="card card-outline card-primary shadow-sm h-100">
          <div class="card-body p-3 d-flex align-items-center">
            <div class="flex-shrink-0 bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3">
              <i class="bi bi-globe fs-3"></i>
            </div>
            <div>
              <span class="text-muted small fw-bold">TOTAL MONITOREADOS</span>
              <h3 class="mb-0 fw-bold text-dark" id="kpi-total-servicios">0</h3>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-6">
        <div class="card card-outline card-success shadow-sm h-100">
          <div class="card-body p-3 d-flex align-items-center">
            <div class="flex-shrink-0 bg-success bg-opacity-10 text-success p-3 rounded-3 me-3">
              <i class="bi bi-check-circle-fill fs-3"></i>
            </div>
            <div>
              <span class="text-muted small fw-bold">ONLINE / OK</span>
              <h3 class="mb-0 fw-bold text-success" id="kpi-online-servicios">0</h3>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-6">
        <div class="card card-outline card-warning shadow-sm h-100">
          <div class="card-body p-3 d-flex align-items-center">
            <div class="flex-shrink-0 bg-warning bg-opacity-10 text-warning p-3 rounded-3 me-3">
              <i class="bi bi-exclamation-triangle-fill fs-3"></i>
            </div>
            <div>
              <span class="text-muted small fw-bold">DEGRADADO / LENTO</span>
              <h3 class="mb-0 fw-bold text-warning" id="kpi-lento-servicios">0</h3>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-6">
        <div class="card card-outline card-danger shadow-sm h-100">
          <div class="card-body p-3 d-flex align-items-center">
            <div class="flex-shrink-0 bg-danger bg-opacity-10 text-danger p-3 rounded-3 me-3">
              <i class="bi bi-x-circle-fill fs-3"></i>
            </div>
            <div>
              <span class="text-muted small fw-bold">OFFLINE / CAÍDOS</span>
              <h3 class="mb-0 fw-bold text-danger" id="kpi-offline-servicios">0</h3>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- DataTables Table -->
    <div class="card card-outline card-primary shadow-sm">
      <div class="card-header py-2 d-flex align-items-center justify-content-between">
        <h3 class="card-title fs-6 fw-bold mb-0">
          <i class="bi bi-list-task me-1"></i> Lista de Servicios DNS y Servidores
        </h3>
        <button class="btn btn-xs btn-outline-secondary" onclick="cargarServicios()">
          <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
        </button>
      </div>
      <div class="card-body p-3">
        <div class="table-responsive">
          <table id="tablaServicios" class="table table-hover table-striped align-middle w-100">
            <thead class="table-dark">
              <tr>
                <th style="width: 50px;">ID</th>
                <th>Nombre Servicio</th>
                <th>Tipo</th>
                <th>Objetivo / Target</th>
                <th>Latencia</th>
                <th>Respuesta / IP</th>
                <th>Estado</th>
                <th>Último Check</th>
                <th class="text-end" style="width: 120px;">Acciones</th>
              </tr>
            </thead>
            <tbody id="tbody-servicios">
              <!-- Cargado vía AJAX con servicios.js -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
