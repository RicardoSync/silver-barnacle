<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!-- Header de la vista Monitor de Caídas -->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-md-6 col-12">
        <h3 class="mb-0 fw-bold d-flex align-items-center">
          <i class="bi bi-exclamation-octagon text-danger me-2"></i> Monitor de Caídas de Red
        </h3>
        <p class="text-muted small mb-0">Centro de diagnóstico de desconexiones, duración de caídas y análisis de estabilidad.</p>
      </div>
      <div class="col-md-6 col-12 text-md-end mt-2 mt-md-0">
        <button id="btnReloadCaidas" class="btn btn-primary btn-sm shadow-sm" onclick="initHistorialCaidasModule()">
          <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
        </button>
      </div>
    </div>
  </div>
</div>

<div class="app-content mt-3">
  <div class="container-fluid">
    
    <!-- Filtros de Tiempo -->
    <div class="card card-outline card-danger shadow-sm mb-4">
      <div class="card-body py-3">
        <div class="row align-items-center g-3">
          <div class="col-md-6 col-12">
            <label for="selectHorasHistorialCaidas" class="form-label fw-bold text-secondary mb-1">
              <i class="bi bi-clock-history text-danger me-1"></i> Rango de Tiempo para el Análisis
            </label>
            <select id="selectHorasHistorialCaidas" class="form-select" onchange="cargarTodoHistorialCaidas()">
              <option value="0.16667">Últimos 10 Minutos</option>
              <option value="0.5">Últimos 30 Minutos</option>
              <option value="1">Última 1 Hora</option>
              <option value="2">Últimas 2 Horas</option>
              <option value="4">Últimas 4 Horas</option>
              <option value="8">Últimas 8 Horas</option>
              <option value="24" selected>Últimas 24 Horas (1 Día)</option>
              <option value="168">Últimos 7 Días (1 Semana)</option>
            </select>
          </div>
          <div class="col-md-6 col-12 text-md-end">
            <span class="badge text-bg-light border text-muted p-2">
              <i class="bi bi-info-circle me-1"></i> Monitoreo automático cada 60 segundos
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Cards KPI -->
    <div class="row mb-4">
      <div class="col-md-3 col-sm-6 col-12 mb-2">
        <div class="info-box shadow-sm border-0">
          <span class="info-box-icon text-bg-danger rounded-circle"><i class="bi bi-broadcast"></i></span>
          <div class="info-box-content">
            <span class="info-box-text text-muted small">Caídas Activas</span>
            <span class="info-box-number fs-4 fw-bold text-danger" id="kpiActivas">0</span>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 col-12 mb-2">
        <div class="info-box shadow-sm border-0">
          <span class="info-box-icon text-bg-warning rounded-circle text-white"><i class="bi bi-exclamation-triangle"></i></span>
          <div class="info-box-content">
            <span class="info-box-text text-muted small">Eventos en Rango</span>
            <span class="info-box-number fs-4 fw-bold text-warning" id="kpiRango">0</span>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 col-12 mb-2">
        <div class="info-box shadow-sm border-0">
          <span class="info-box-icon text-bg-info rounded-circle text-white"><i class="bi bi-stopwatch"></i></span>
          <div class="info-box-content">
            <span class="info-box-text text-muted small">Duración Promedio</span>
            <span class="info-box-number fs-5 fw-bold text-info" id="kpiPromedio">0 min</span>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 col-12 mb-2">
        <div class="info-box shadow-sm border-0">
          <span class="info-box-icon text-bg-dark rounded-circle"><i class="bi bi-hourglass-split"></i></span>
          <div class="info-box-content">
            <span class="info-box-text text-muted small">Máxima Duración</span>
            <span class="info-box-number fs-5 fw-bold text-dark" id="kpiMax">0 min</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Gráficas Intuitivas -->
    <div class="row mb-4">
      
      <!-- Gráfica 1: Línea de Tiempo / Duración por Evento -->
      <div class="col-lg-8 col-12 mb-3">
        <div class="card card-outline card-danger shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title fs-6 fw-bold m-0">
              <i class="bi bi-bar-chart text-danger me-2"></i> Eventos de Caída y Duración (Minutos)
            </h3>
            <button class="btn btn-xs btn-outline-secondary" onclick="openHistorialChartFullScreen()">
              <i class="bi bi-arrows-fullscreen me-1"></i> Ampliar
            </button>
          </div>
          <div class="card-body">
            <div style="height: 300px; position: relative;">
              <canvas id="graficaCaidas"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráfica 2: Top Nodos Afectados -->
      <div class="col-lg-4 col-12 mb-3">
        <div class="card card-outline card-warning shadow-sm h-100">
          <div class="card-header py-2">
            <h3 class="card-title fs-6 fw-bold m-0">
              <i class="bi bi-pie-chart text-warning me-2"></i> Top Nodos con Mayor Impacto
            </h3>
          </div>
          <div class="card-body">
            <div style="height: 300px; position: relative;">
              <canvas id="graficaTopCaidasNodos"></canvas>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Tabla Detallada -->
    <div class="card card-outline card-secondary shadow-sm mb-4">
      <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title fs-6 fw-bold m-0">
          <i class="bi bi-table me-2"></i> Registro Histórico Detallado de Caídas
        </h3>
        <span class="badge text-bg-light border text-muted">DataTables</span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="tablaHistorial" class="table table-hover align-middle w-100">
            <thead class="table-light">
              <tr>
                <th>Dispositivo / Nodo</th>
                <th>Tipo</th>
                <th>Fecha de Caída</th>
                <th>Fecha de Recuperación</th>
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
</div>

<!-- Modal Fullscreen para Gráfica de Caídas -->
<div class="modal fade" id="modalChartFullScreen" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-dark text-white py-2">
        <h5 class="modal-title fs-6 fw-bold">
          <i class="bi bi-arrows-fullscreen me-2"></i> Línea de Tiempo de Caídas Ampliada
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="closeHistorialChartFullScreen()"></button>
      </div>
      <div class="modal-body p-3">
        <div style="height: 450px; position: relative;">
          <canvas id="chartFullScreenCanvas"></canvas>
        </div>
      </div>
      <div class="modal-footer py-2 bg-light">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="closeHistorialChartFullScreen()">Cerrar</button>
      </div>
    </div>
  </div>
</div>
