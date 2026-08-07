<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../DAO/MikrotikDAO.php';
require_once __DIR__ . '/../DAO/EquipoDAO.php';

$mikrotiks = (new MikrotikDAO())->listarActivos();
$equipos = (new EquipoDAO())->listarActivos();
?>

<!-- Header de la vista Analíticas -->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-md-6 col-12">
        <h3 class="mb-0 fw-bold d-flex align-items-center">
          <i class="bi bi-graph-up-arrow text-primary me-2"></i> Estadísticas y Analíticas
        </h3>
        <p class="text-muted small mb-0">Monitoreo histórico de latencias, tráfico de red y rendimiento del sistema.</p>
      </div>
      <div class="col-md-6 col-12 text-md-end mt-2 mt-md-0">
        <button id="btnRefreshCharts" class="btn btn-primary btn-sm shadow-sm">
          <i class="bi bi-arrow-clockwise me-1"></i> Actualizar Datos
        </button>
      </div>
    </div>
  </div>
</div>

<div class="app-content mt-3">
  <div class="container-fluid">
    
    <!-- Filtros Principales -->
    <div class="card card-outline card-primary shadow-sm mb-4">
      <div class="card-body py-3">
        <div class="row g-3 align-items-center">
          <!-- Seleccionar MikroTik -->
          <div class="col-md-4 col-12">
            <label for="selectAnaliticasMikrotik" class="form-label fw-bold text-secondary mb-1">
              <i class="bi bi-router text-primary me-1"></i> Seleccionar MikroTik
            </label>
            <select id="selectAnaliticasMikrotik" class="form-select select2">
              <option value="">-- Buscar / Seleccionar MikroTik --</option>
              <?php foreach ($mikrotiks as $m): ?>
                <option value="<?php echo $m['id']; ?>">
                  <?php echo htmlspecialchars($m['nombre']) . " (" . htmlspecialchars($m['ip_address']) . ")"; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Seleccionar Equipo -->
          <div class="col-md-4 col-12">
            <label for="selectAnaliticasEquipo" class="form-label fw-bold text-secondary mb-1">
              <i class="bi bi-hdd-network text-info me-1"></i> Seleccionar Equipo
            </label>
            <select id="selectAnaliticasEquipo" class="form-select select2">
              <option value="">-- Buscar / Seleccionar Equipo --</option>
              <?php foreach ($equipos as $eq): ?>
                <option value="<?php echo $eq['id']; ?>">
                  <?php echo htmlspecialchars($eq['nombre']) . " (" . htmlspecialchars($eq['ip_address']) . ")"; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Rango de Tiempo -->
          <div class="col-md-4 col-12">
            <label for="selectAnaliticasHoras" class="form-label fw-bold text-secondary mb-1">
              <i class="bi bi-clock-history me-1"></i> Rango Temporal
            </label>
            <select id="selectAnaliticasHoras" class="form-select">
              <option value="0.16667">Últimos 10 Minutos</option>
              <option value="0.5">Últimos 30 Minutos</option>
              <option value="1">Última 1 Hora</option>
              <option value="2">Últimas 2 Horas</option>
              <option value="4" selected>Últimas 4 Horas</option>
              <option value="8">Últimas 8 Horas</option>
              <option value="24">Últimas 24 Horas (1 Día)</option>
              <option value="168">Últimos 7 Días</option>
            </select>
          </div>
        </div>

        <!-- Filtro Dinámico de Interfaces (Sólo MikroTik) -->
        <div id="contenedorFiltroInterfacesWrapper" class="mt-3 pt-3 border-top" style="display: none;">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="fw-bold small text-muted"><i class="bi bi-funnel me-1"></i> Filtrar Interfaces:</span>
            <div id="contenedorFiltroInterfaces" class="d-flex gap-1 flex-wrap"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Contenedor Principal de Gráficas KPI y Estadísticas -->
    <div id="analiticasChartsContainer" style="display: none;">
      
      <!-- KPIs para Equipos (Ping, Uptime, Caídas, Min/Max, Pérdida) -->
      <div id="contenedorKpiEquipoAnaliticas" class="row mb-4" style="display: none;">
        <div class="col-md-2 col-sm-4 col-6 mb-2">
          <div class="info-box shadow-sm border-0">
            <span class="info-box-icon text-bg-success rounded-circle"><i class="bi bi-speedometer2"></i></span>
            <div class="info-box-content">
              <span class="info-box-text text-muted small">Ping Prom.</span>
              <span class="info-box-number fs-5 fw-bold text-success" id="stat-an-ping-avg">0 ms</span>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-4 col-6 mb-2">
          <div class="info-box shadow-sm border-0">
            <span class="info-box-icon text-bg-info rounded-circle text-white"><i class="bi bi-arrows-expand"></i></span>
            <div class="info-box-content">
              <span class="info-box-text text-muted small">Mín / Máx</span>
              <span class="info-box-number fs-6 fw-bold text-info" id="stat-an-ping-minmax">Min: 0 / Max: 0</span>
            </div>
          </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
          <div class="info-box shadow-sm border-0">
            <span class="info-box-icon text-bg-primary rounded-circle"><i class="bi bi-check-circle"></i></span>
            <div class="info-box-content">
              <span class="info-box-text text-muted small">Uptime %</span>
              <span class="info-box-number fs-5 fw-bold text-primary" id="stat-an-uptime">0%</span>
            </div>
          </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
          <div class="info-box shadow-sm border-0">
            <span class="info-box-icon text-bg-danger rounded-circle"><i class="bi bi-exclamation-triangle"></i></span>
            <div class="info-box-content">
              <span class="info-box-text text-muted small">Pérdida Paquetes</span>
              <span class="info-box-number fs-5 fw-bold text-danger" id="stat-an-loss">0%</span>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-4 col-12 mb-2">
          <div class="info-box shadow-sm border-0">
            <span class="info-box-icon text-bg-warning rounded-circle"><i class="bi bi-clock-history"></i></span>
            <div class="info-box-content">
              <span class="info-box-text text-muted small">Tiempo Inactivo</span>
              <span class="info-box-number fs-6 fw-bold text-warning" id="stat-an-offline-time">Caído: 0 min</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráfica de Ping para Equipos -->
      <div id="contenedorChartPingEquipo" class="card card-outline card-success shadow-sm mb-4" style="display: none;">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
          <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-activity text-success me-2"></i>Historial de Ping y Latencia de Equipo</h3>
          <button class="btn btn-xs btn-outline-secondary" onclick="abrirModalGraficaAnaliticas('pingEquipo', 'Historial de Ping de Equipo')">
            <i class="bi bi-arrows-fullscreen me-1"></i> Ampliar
          </button>
        </div>
        <div class="card-body">
          <div style="height: 320px;">
            <canvas id="chartPingEquipo"></canvas>
          </div>
        </div>
      </div>

      <!-- Gráficas de Tráfico por Interfaces de MikroTik -->
      <div id="contenedorInterfaces"></div>

      <!-- Gráfica Ping MikroTik (Servidor vs Google) -->
      <div id="contenedorChartPingMikrotik" class="card card-outline card-danger shadow-sm mb-4" style="display: none;">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
          <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-diagram-2 text-danger me-2"></i>Latencia (Ping Servidor vs Google)</h3>
          <button class="btn btn-xs btn-outline-secondary" onclick="abrirModalGraficaAnaliticas('ping', 'Latencia MikroTik (Servidor vs Google)')">
            <i class="bi bi-arrows-fullscreen me-1"></i> Ampliar
          </button>
        </div>
        <div class="card-body">
          <div style="height: 280px;">
            <canvas id="chartPing"></canvas>
          </div>
        </div>
      </div>

      <!-- Gráfica Uso de Recursos MikroTik (CPU / RAM) -->
      <div id="contenedorChartRecursos" class="card card-outline card-info shadow-sm mb-4" style="display: none;">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
          <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-cpu text-info me-2"></i>Rendimiento de Recursos (CPU y RAM)</h3>
          <button class="btn btn-xs btn-outline-secondary" onclick="abrirModalGraficaAnaliticas('recursos', 'Recursos MikroTik (CPU y RAM)')">
            <i class="bi bi-arrows-fullscreen me-1"></i> Ampliar
          </button>
        </div>
        <div class="card-body">
          <div style="height: 280px;">
            <canvas id="chartRecursos"></canvas>
          </div>
        </div>
      </div>

      <!-- Top Nodos Caídos -->
      <div id="contenedorChartCaidas" class="card card-outline card-warning shadow-sm mb-4" style="display: none;">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
          <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-bar-chart-steps text-warning me-2"></i>Top 10 Nodos con Mayor Cantidad de Caídas</h3>
          <button class="btn btn-xs btn-outline-secondary" onclick="abrirModalGraficaAnaliticas('caidas', 'Top 10 Nodos con Mayor Cantidad de Caídas')">
            <i class="bi bi-arrows-fullscreen me-1"></i> Ampliar
          </button>
        </div>
        <div class="card-body">
          <div style="height: 260px;">
            <canvas id="chartCaidas"></canvas>
          </div>
        </div>
      </div>

    </div>

  </div>
</div>

<!-- Modal Zoom Pantalla Completa para Analíticas -->
<div class="modal fade" id="modalAnaliticasFullScreen" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-dark text-white py-2">
        <h5 class="modal-title fs-6 fw-bold" id="modalAnaliticasTitle">
          <i class="bi bi-arrows-fullscreen me-2"></i> Ampliación de Gráfica
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary" onclick="zoomAnaliticasChart(1.2)" title="Acercar"><i class="bi bi-zoom-in"></i> Zoom +</button>
            <button class="btn btn-outline-secondary" onclick="zoomAnaliticasChart(0.8)" title="Alejar"><i class="bi bi-zoom-out"></i> Zoom -</button>
            <button class="btn btn-outline-secondary" onclick="resetAnaliticasChartZoom()" title="Restablecer"><i class="bi bi-arrow-counterclockwise"></i> Restablecer</button>
          </div>
          <button class="btn btn-sm btn-success" onclick="descargarGraficaAnaliticas()">
            <i class="bi bi-download me-1"></i> Descargar PNG
          </button>
        </div>
        <div style="height: 450px; position: relative;">
          <canvas id="canvasAnaliticasFullScreen"></canvas>
        </div>
      </div>
      <div class="modal-footer py-2 bg-light">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
