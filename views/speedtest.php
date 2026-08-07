<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
?>
<div class="app-content-header mb-3">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-sm-6">
        <h3 class="mb-0 fw-bold d-flex align-items-center">
          <i class="bi bi-speedometer2 text-primary me-2 fs-3"></i>
          <span>Speedtest de Red</span>
        </h3>
        <small class="text-muted">Medición de Ancho de Banda (Servidor ↔ Internet y Cliente ↔ Servidor)</small>
      </div>
      <div class="col-sm-6 text-end">
        <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" onclick="cargarHistorialSpeedtest()">
          <i class="bi bi-arrow-clockwise me-1"></i> Actualizar Datos
        </button>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    
    <!-- FILA 1: KPIs / Estadísticas Rápidas -->
    <div class="row mb-4">
      <div class="col-lg-3 col-sm-6 mb-3 mb-lg-0">
        <div class="info-box bg-body border shadow-sm rounded-3">
          <span class="info-box-icon text-bg-primary rounded-circle" style="width: 48px; height: 48px; flex-shrink: 0; align-self: center;">
            <i class="bi bi-cloud-arrow-down-fill"></i>
          </span>
          <div class="info-box-content">
            <span class="info-box-text text-muted small fw-semibold">Promedio Descarga Servidor</span>
            <span class="info-box-number fs-4 fw-bold text-primary" id="kpiAvgDownloadServidor">0.00 Mbps</span>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-sm-6 mb-3 mb-lg-0">
        <div class="info-box bg-body border shadow-sm rounded-3">
          <span class="info-box-icon text-bg-success rounded-circle" style="width: 48px; height: 48px; flex-shrink: 0; align-self: center;">
            <i class="bi bi-cloud-arrow-up-fill"></i>
          </span>
          <div class="info-box-content">
            <span class="info-box-text text-muted small fw-semibold">Promedio Subida Servidor</span>
            <span class="info-box-number fs-4 fw-bold text-success" id="kpiAvgUploadServidor">0.00 Mbps</span>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-sm-6 mb-3 mb-sm-0">
        <div class="info-box bg-body border shadow-sm rounded-3">
          <span class="info-box-icon text-bg-info text-white rounded-circle" style="width: 48px; height: 48px; flex-shrink: 0; align-self: center;">
            <i class="bi bi-laptop-fill"></i>
          </span>
          <div class="info-box-content">
            <span class="info-box-text text-muted small fw-semibold">Promedio Descarga Cliente</span>
            <span class="info-box-number fs-4 fw-bold text-info" id="kpiAvgDownloadCliente">0.00 Mbps</span>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-sm-6">
        <div class="info-box bg-body border shadow-sm rounded-3">
          <span class="info-box-icon text-bg-warning text-white rounded-circle" style="width: 48px; height: 48px; flex-shrink: 0; align-self: center;">
            <i class="bi bi-stopwatch-fill"></i>
          </span>
          <div class="info-box-content">
            <span class="info-box-text text-muted small fw-semibold">Latencia Promedio WAN</span>
            <span class="info-box-number fs-4 fw-bold text-warning" id="kpiAvgPing">0 ms</span>
          </div>
        </div>
      </div>
    </div>

    <!-- FILA 2: VELOCÍMETRO INTERACTIVO Y TEST -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card card-outline card-primary shadow-sm rounded-3">
          <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
              <span class="fw-bold text-dark fs-5"><i class="bi bi-speedometer text-primary me-2"></i>Medidor de Ancho de Banda</span>
              <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1" id="modalPhaseBadge">⚡ LISTO PARA MEDIR</span>
            </div>

            <!-- Selector de Modo de Test -->
            <div class="btn-group btn-group-sm shadow-sm" role="group">
              <button type="button" class="btn btn-outline-primary active px-3" id="btnModeServer" onclick="setSpeedtestMode('servidor')">
                <i class="bi bi-globe me-1"></i> Servidor ➔ Internet
              </button>
              <button type="button" class="btn btn-outline-primary px-3" id="btnModeClient" onclick="setSpeedtestMode('cliente')">
                <i class="bi bi-laptop me-1"></i> Cliente ➔ Servidor
              </button>
            </div>
          </div>

          <div class="card-body p-4 text-center">
            <p class="text-muted small mb-3 fw-semibold" id="modalServerTargetText">Prueba de Servidor PHP hacia Internet CDN (Cloudflare / Edge)</p>

            <!-- Barra de Progreso General del Test -->
            <div class="progress mb-4 mx-auto" style="height: 6px; max-width: 600px;">
              <div id="speedProgressBar" class="progress-bar bg-gradient bg-primary progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;"></div>
            </div>

            <!-- Canvas Velocímetro Gauges -->
            <div class="position-relative d-inline-block mb-2">
              <canvas id="gaugeCanvasModal" width="340" height="190" style="max-width: 100%; height: auto;"></canvas>
              
              <div class="position-absolute top-100 start-50 translate-middle text-center w-100" style="margin-top: -65px;">
                <div class="display-5 fw-extrabold text-dark font-monospace" id="currentSpeedVal">0.00</div>
                <div class="text-uppercase fw-bold text-muted small" id="currentPhaseLabel">Mbps</div>
              </div>
            </div>

            <!-- Resultados en Vivo del Test Actual -->
            <div class="row justify-content-center text-center mt-4 g-3 max-width-600 mx-auto">
              <div class="col-6 col-md-3">
                <div class="p-2 border rounded bg-light">
                  <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Ping</small>
                  <span class="fs-5 fw-bold text-warning" id="valPing">--</span> <small class="text-muted">ms</small>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="p-2 border rounded bg-light">
                  <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Jitter</small>
                  <span class="fs-5 fw-bold text-secondary" id="valJitter">--</span> <small class="text-muted">ms</small>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="p-2 border rounded bg-light">
                  <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Descarga ⬇️</small>
                  <span class="fs-5 fw-bold text-success" id="valDownload">0.00</span> <small class="text-muted">Mbps</small>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="p-2 border rounded bg-light">
                  <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Subida ⬆️</small>
                  <span class="fs-5 fw-bold text-primary" id="valUpload">0.00</span> <small class="text-muted">Mbps</small>
                </div>
              </div>
            </div>

            <!-- Botón de Inicio de Test -->
            <div class="mt-4">
              <button id="btnStartSpeedtest" class="btn btn-primary btn-lg shadow-sm px-5 py-2 fw-bold rounded-pill" onclick="ejecutarSpeedtest()">
                <i class="bi bi-play-circle-fill me-2 fs-5"></i> Iniciar Prueba de Velocidad
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FILA 3: GRÁFICA DE TENDENCIA (ÚLTIMOS 30 REGISTROS) -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card card-outline card-info shadow-sm rounded-3">
          <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title fs-6 fw-bold m-0 text-dark">
              <i class="bi bi-graph-up-arrow text-info me-2"></i>
              Tendencia de Velocidad (Últimos 30 Registros)
            </h3>
            <span class="badge bg-info bg-opacity-10 text-info border">Descarga vs Subida</span>
          </div>
          <div class="card-body p-3" style="height: 260px; position: relative;">
            <canvas id="chartSpeedtestHistorial"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- FILA 4: TABLA DE HISTORIAL CON DATATABLES -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card card-outline card-secondary shadow-sm rounded-3">
          <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title fs-6 fw-bold m-0 text-dark">
              <i class="bi bi-table text-secondary me-2"></i>
              Historial de Pruebas Registradas
            </h3>
            <div>
              <button class="btn btn-sm btn-outline-danger shadow-sm" onclick="limpiarHistorialSpeedtest()" title="Limpiar todo el historial">
                <i class="bi bi-trash me-1"></i> Limpiar Historial
              </button>
            </div>
          </div>
          <div class="card-body p-3 table-responsive">
            <table id="tablaSpeedtestHistorial" class="table table-striped table-hover align-middle w-100" style="font-size: 13px;">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Fecha y Hora</th>
                  <th>Tipo de Prueba</th>
                  <th>Ping (Jitter)</th>
                  <th>Descarga</th>
                  <th>Subida</th>
                  <th>Origen ➔ Destino</th>
                  <th>Usuario</th>
                  <th class="text-end pe-3">Acción</th>
                </tr>
              </thead>
              <tbody>
                <!-- Se puebla dinámicamente vía DataTables y JS -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
