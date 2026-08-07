<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
?>
<div class="app-content-header mb-3">
  <div class="container-fluid">
    <div class="row align-items-center g-2">
      <div class="col-12 col-md-5">
        <h3 class="mb-0 fw-bold d-flex align-items-center">
          <i class="bi bi-grid-3x3-gap-fill text-primary me-2 fs-3"></i>
          <span>NOC Multigráfica</span>
        </h3>
        <small class="text-muted">Monitoreo dinámico de Latencia Ping y Tráfico MikroTik en Tiempo Real</small>
      </div>

      <div class="col-12 col-md-7 text-md-end d-flex flex-wrap align-items-center justify-content-md-end gap-2">
        <button class="btn btn-sm btn-primary shadow-sm" onclick="agregarNuevaGrafica()" title="Agregar nueva gráfica al panel">
          <i class="bi bi-plus-circle-fill me-1"></i> Agregar Gráfica
        </button>

        <div class="d-flex align-items-center gap-1">
          <label for="select-columns-count" class="small text-muted fw-semibold mb-0">Disposición:</label>
          <select id="select-columns-count" class="form-select form-select-sm shadow-sm" style="width: auto;">
            <option value="6" selected>2 por fila</option>
            <option value="4">3 por fila</option>
            <option value="3">4 por fila</option>
            <option value="12">1 por fila</option>
          </select>
        </div>

        <div class="d-flex align-items-center gap-1">
          <label for="select-interval-multi" class="small text-muted fw-semibold mb-0">Intervalo:</label>
          <select id="select-interval-multi" class="form-select form-select-sm shadow-sm" style="width: auto;">
            <option value="1000">1 seg</option>
            <option value="2000" selected>2 seg</option>
            <option value="3000">3 seg</option>
            <option value="5000">5 seg</option>
          </select>
        </div>

        <button id="btn-toggle-multi" class="btn btn-sm btn-success shadow-sm" onclick="toggleMonitoreoMulti()">
          <i id="icon-toggle-multi" class="bi bi-pause-fill me-1"></i>
          <span id="text-toggle-multi">Pausar</span>
        </button>

        <button class="btn btn-sm btn-outline-secondary shadow-sm" onclick="restablecerGraficasMulti()" title="Restablecer disposición por defecto">
          <i class="bi bi-arrow-counterclockwise me-1"></i> Restablecer
        </button>

        <span id="lbl-status-batch" class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1">
          <i class="bi bi-activity text-success me-1"></i> Activo (2s)
        </span>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    <div class="row g-3" id="grid-ping-multi">
      <!-- Las tarjetas de gráficas se generan dinámicamente desde JS -->
    </div>
  </div>
</div>
