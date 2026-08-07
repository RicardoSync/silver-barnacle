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
        <h3 class="mb-0 fw-bold"><i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard de Monitoreo</h3>
      </div>
      <div class="col-sm-6 text-end">
        <span class="badge text-bg-light border shadow-sm px-3 py-2 text-dark font-monospace" id="dashboard-last-update">
          <i class="bi bi-arrow-repeat spin me-1"></i> Cargando datos...
        </span>
        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="loadDashboardData()" title="Actualizar ahora">
          <i class="bi bi-arrow-clockwise"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    <!-- FILA 1: Pings en Tiempo Real (Google 8.8.8.8 vs Cloudflare 1.1.1.1) -->
    <div class="row mb-4">
      <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
        <div class="info-box bg-body border shadow-sm">
          <span class="info-box-icon text-bg-danger rounded-circle" style="width: 52px; height: 52px; flex-shrink: 0; align-self: center;"><i class="bi bi-google"></i></span>
          <div class="info-box-content">
            <span class="info-box-text text-muted">Google DNS (8.8.8.8)</span>
            <span class="info-box-number fs-4 fw-bold text-danger" id="live-ping-google-val">-- ms</span>
            <div class="progress" style="height: 3px;">
              <div class="progress-bar bg-danger" style="width: 100%"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
        <div class="info-box bg-body border shadow-sm">
          <span class="info-box-icon text-bg-warning text-white rounded-circle" style="width: 52px; height: 52px; flex-shrink: 0; align-self: center;"><i class="bi bi-cloud-fill"></i></span>
          <div class="info-box-content">
            <span class="info-box-text text-muted">Cloudflare DNS (1.1.1.1)</span>
            <span class="info-box-number fs-4 fw-bold text-warning" id="live-ping-cloudflare-val">-- ms</span>
            <div class="progress" style="height: 3px;">
              <div class="progress-bar bg-warning" style="width: 100%"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-12">
        <div class="card card-outline card-primary shadow-sm h-100 mb-0">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-activity text-primary me-2"></i>Latencia en Tiempo Real</h3>
            <div class="card-tools">
              <span class="badge text-bg-success">En vivo</span>
            </div>
          </div>
          <div class="card-body p-2" style="height: 140px; position: relative;">
            <canvas id="chart-ping-realtime"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- FILA 2: Recursos de Red & Carga CPU MikroTiks (Sección b) -->
    <div class="row mb-4">
      <!-- Servidor Local CPU & RAM -->
      <div class="col-lg-6 col-12 mb-3 mb-lg-0">
        <div class="card card-outline card-info shadow-sm h-100">
          <div class="card-header py-2">
            <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-hdd-stack text-info me-2"></i>Recursos de Servidor Local</h3>
          </div>
          <div class="card-body">
            <div class="row align-items-center mb-3">
              <div class="col-4">
                <span class="fw-semibold text-muted d-block"><i class="bi bi-cpu me-1"></i> CPU Servidor</span>
                <span class="fs-3 fw-bold text-dark" id="gauge-cpu-val">--%</span>
              </div>
              <div class="col-8">
                <div class="progress" style="height: 12px;">
                  <div id="progress-cpu-local" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                </div>
              </div>
            </div>
            <hr class="my-2">
            <div class="row align-items-center mt-3">
              <div class="col-4">
                <span class="fw-semibold text-muted d-block"><i class="bi bi-memory me-1"></i> RAM Servidor</span>
                <span class="fs-3 fw-bold text-dark" id="gauge-ram-val">--%</span>
              </div>
              <div class="col-8">
                <div class="progress" style="height: 12px;">
                  <div id="progress-ram-local" class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Carga de CPU de MikroTiks (Mayor a Menor) -->
      <div class="col-lg-6 col-12">
        <div class="card card-outline card-secondary shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-router text-secondary me-2"></i>Carga CPU MikroTiks (Ranking Mayor a Menor)</h3>
            <span class="badge text-bg-light border text-muted">Ordenado por uso</span>
          </div>
          <div class="card-body p-3" id="dashboard-top-cpu-container" style="max-height: 220px; overflow-y: auto;">
            <div class="text-center py-3 text-muted">
              <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
              <span class="ms-2">Cargando MikroTiks...</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FILA 2.5: Tráfico de Red del Servidor Local (Físicas y Digitales) -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card card-outline card-primary shadow-sm">
          <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="card-title fs-6 fw-bold m-0 d-flex align-items-center">
              <i class="bi bi-ethernet text-primary me-2 fs-5"></i>
              Tráfico de Interfaces de Red (Servidor Local)
            </h3>
            <div class="d-flex align-items-center gap-2">
              <span class="small fw-semibold text-muted">Interfaz:</span>
              <select id="select-server-iface" class="form-select form-select-sm" style="width: auto; min-width: 140px;" onchange="onServerIfaceChange()">
                <option value="total">Todas (Total)</option>
              </select>
              <span class="badge text-bg-success px-2 py-1" id="server-traffic-rx-val"><i class="bi bi-download me-1"></i>RX: 0 Kbps</span>
              <span class="badge text-bg-info px-2 py-1 text-white" id="server-traffic-tx-val"><i class="bi bi-upload me-1"></i>TX: 0 Kbps</span>
              <span class="badge text-bg-light border text-muted">2s AJAX</span>
            </div>
          </div>
          <div class="card-body p-3" style="height: 200px; position: relative;">
            <canvas id="chart-server-traffic-realtime"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- FILA 3: Incidencias / Nodos Caídos (Sección c) & Servicios DNS/Web (Sección d) -->
    <div class="row mb-4">
      <!-- Incidencias o Nodos Caídos / Equipos -->
      <div class="col-lg-7 col-12 mb-3 mb-lg-0">
        <div class="card card-outline card-danger shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-exclamation-octagon text-danger me-2"></i>Incidencias y Equipos Caídos</h3>
            <span id="problem-count-badge" class="badge bg-success">Red Operativa</span>
          </div>
          <div class="card-body p-0 table-responsive" style="max-height: 280px;">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Equipo / Nodo</th>
                  <th>Dirección IP</th>
                  <th>Estado</th>
                  <th>Latencia</th>
                  <th class="text-end pe-3">Acción</th>
                </tr>
              </thead>
              <tbody id="problems-tbody">
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
                    <span class="ms-2">Verificando incidentes...</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Servicios y Estado de DNS y Web -->
      <div class="col-lg-5 col-12">
        <div class="card card-outline card-success shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-globe text-success me-2"></i>Estado de Servicios (DNS / Web / Puertos)</h3>
            <span class="badge text-bg-light border text-muted">Monitoreo HTTP & DNS</span>
          </div>
          <div class="card-body p-3" id="dashboard-services-container" style="max-height: 280px; overflow-y: auto;">
            <div class="text-center py-4 text-muted">
              <div class="spinner-border spinner-border-sm text-success" role="status"></div>
              <span class="ms-2">Cargando estado de servicios...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
