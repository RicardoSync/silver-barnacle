<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-activity text-secondary me-2"></i> Monitor de Caídas</h2>
    <div>
        <select id="selectDiasGrafica" class="form-select form-select-sm d-inline-block w-auto">
            <option value="7">Últimos 7 días</option>
            <option value="15">Últimos 15 días</option>
            <option value="30">Últimos 30 días</option>
        </select>
    </div>
</div>

<!-- Tarjetas KPI -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-muted text-uppercase fw-bold mb-1">Caídas Activas</h6>
                <p class="fs-4 fw-bold mb-0 text-danger" id="kpiActivas">--</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-muted text-uppercase fw-bold mb-1">Caídas Hoy (24h)</h6>
                <p class="fs-4 fw-bold mb-0 text-warning" id="kpiHoy">--</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-muted text-uppercase fw-bold mb-1">Promedio de Caída</h6>
                <p class="fs-4 fw-bold mb-0 text-info"><span id="kpiPromedio">--</span> <small class="fs-6">min</small></p>
            </div>
        </div>
    </div>
</div>

<!-- Gráfica -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bar-chart-fill text-primary"></i> Tendencia de Caídas</span>
                <button class="btn btn-sm btn-outline-secondary" onclick="openHistorialChartFullScreen()"><i class="bi bi-arrows-fullscreen"></i> Ampliar</button>
            </div>
            <div class="card-body">
                <canvas id="graficaCaidas" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Registros -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold"><i class="bi bi-table"></i> Registro Histórico</div>
            <div class="card-body table-responsive">
                <table id="tablaHistorial" class="table table-hover table-striped w-100">
                    <thead class="table-dark">
                        <tr>
                            <th>Nodo</th>
                            <th>Tipo</th>
                            <th>Inicio (Caída)</th>
                            <th>Recuperación</th>
                            <th>Duración</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chart Full Screen -->
<div class="modal fade" id="modalChartFullScreen" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="fs-chart-title">Tendencia de Caídas - Pantalla Completa</h5>
        <div class="ms-auto me-3">
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-light" onclick="zoomHistorialFullScreenChart('10m')">10 Min</button>
                <button class="btn btn-outline-light" onclick="zoomHistorialFullScreenChart('1h')">1 Hora</button>
                <button class="btn btn-outline-light" onclick="zoomHistorialFullScreenChart('3h')">3 Horas</button>
                <button class="btn btn-outline-light" onclick="zoomHistorialFullScreenChart('24h')">24 Horas</button>
                <button class="btn btn-outline-light" onclick="zoomHistorialFullScreenChart('1w')">1 Semana</button>
                <button class="btn btn-warning" onclick="resetHistorialFullScreenChartZoom()">Reset</button>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="closeHistorialChartFullScreen()"></button>
      </div>
      <div class="modal-body bg-light p-4">
        <div class="card shadow-sm h-100">
            <div class="card-body h-100 d-flex flex-column">
                <canvas id="chartFullScreenCanvas" style="flex-grow: 1;"></canvas>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>
