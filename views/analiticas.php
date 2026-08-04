<?php
require_once __DIR__ . '/../DAO/MikrotikDAO.php';
require_once __DIR__ . '/../DAO/EquipoDAO.php';

$mikrotikDAO = new MikrotikDAO();
$mikrotiks = $mikrotikDAO->listarActivos();

$equipoDAO = new EquipoDAO();
$equipos = $equipoDAO->listarActivos();
?>
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2 class="h4 mb-0"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i> Analíticas de Red</h2>
        <div>
            <button class="btn btn-outline-secondary btn-sm me-2" id="btnRefreshCharts">
                <i class="bi bi-arrow-clockwise"></i> Refrescar
            </button>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="selectAnaliticasMikrotik" class="form-label text-muted small text-uppercase fw-bold">Seleccionar Nodo (MikroTik)</label>
                <select id="selectAnaliticasMikrotik" class="form-select">
                    <option value="">-- Seleccione un MikroTik --</option>
                    <?php foreach ($mikrotiks as $m): 
                        $nombreMikrotik = !empty($m['alias']) ? $m['alias'] : (!empty($m['nombre']) ? $m['nombre'] : 'MikroTik #' . $m['id']);
                    ?>
                        <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($nombreMikrotik . ' (' . $m['ip_address'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label for="selectAnaliticasEquipo" class="form-label text-muted small text-uppercase fw-bold">O seleccionar Equipo (Ping)</label>
                <select id="selectAnaliticasEquipo" class="form-select">
                    <option value="">-- Seleccione un Equipo --</option>
                    <?php foreach ($equipos as $e): ?>
                        <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['nombre'] . ' (' . $e['ip_address'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="selectAnaliticasHoras" class="form-label text-muted small text-uppercase fw-bold">Rango de Tiempo</label>
                <select id="selectAnaliticasHoras" class="form-select">
                    <option value="4" selected>Últimas 4 horas</option>
                    <option value="24">Últimas 24 horas</option>
                    <option value="72">Últimos 3 días</option>
                    <option value="168">Últimos 7 días</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="row g-4" id="analiticasChartsContainer" style="display: none;">
    <!-- Barra de Filtro de Interfaces -->
    <div class="col-12" id="contenedorFiltroInterfacesWrapper" style="display: none;">
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-body py-2 px-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-funnel-fill text-primary"></i>
                    <span class="fw-bold small text-uppercase text-muted me-1">Filtrar Interfaces:</span>
                    <div id="contenedorFiltroInterfaces" class="d-flex flex-wrap gap-1">
                        <!-- Píldoras de interfaces inyectadas dinámicamente -->
                    </div>
                </div>
                <small class="text-muted" style="font-size: 11px;">Haz clic en una interfaz para mostrar u ocultar su gráfica.</small>
            </div>
        </div>
    </div>

    <!-- Contenedor Dinámico para Interfaces de Tráfico -->
    <div class="col-12" id="contenedorInterfaces">
        <!-- Aquí se inyectarán las gráficas de tráfico independientes -->
    </div>

    <!-- Ping Mikrotik -->
    <div class="col-12 col-xl-6" id="contenedorChartPingMikrotik">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title fs-6 fw-bold mb-0">Latencia (Ping)</h5>
                    <small class="text-muted">Tiempo de respuesta hacia el servidor y Google</small>
                </div>
                <button class="btn btn-xs btn-outline-secondary" onclick="abrirModalGraficaAnaliticas('ping', 'Latencia MikroTik')"><i class="bi bi-arrows-fullscreen me-1"></i> Ampliar</button>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="chartPing"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recursos -->
    <div class="col-12 col-xl-6" id="contenedorChartRecursos">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title fs-6 fw-bold mb-0">Consumo de Recursos</h5>
                    <small class="text-muted">Uso de CPU y Memoria RAM</small>
                </div>
                <button class="btn btn-xs btn-outline-secondary" onclick="abrirModalGraficaAnaliticas('recursos', 'Consumo de Recursos')"><i class="bi bi-arrows-fullscreen me-1"></i> Ampliar</button>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="chartRecursos"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Caídas -->
    <div class="col-12 col-xl-6" id="contenedorChartCaidas">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title fs-6 fw-bold mb-0">Top Nodos con más Caídas</h5>
                    <small class="text-muted">Según el rango de tiempo seleccionado (Global)</small>
                </div>
                <button class="btn btn-xs btn-outline-secondary" onclick="abrirModalGraficaAnaliticas('caidas', 'Top Nodos con más Caídas')"><i class="bi bi-arrows-fullscreen me-1"></i> Ampliar</button>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="chartCaidas"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas KPI de Resumen para Equipo Seleccionado -->
    <div class="col-12" id="contenedorKpiEquipoAnaliticas" style="display: none;">
        <div class="row g-3 mb-2">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center p-3 h-100 bg-white">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Promedio de Ping</small>
                    <div class="fs-4 fw-bold text-primary mt-1" id="stat-an-ping-avg">-- ms</div>
                    <small class="text-muted" style="font-size: 10px;" id="stat-an-ping-minmax">Min: -- / Max: --</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center p-3 h-100 bg-white">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Disponibilidad (SLA)</small>
                    <div class="fs-4 fw-bold text-success mt-1" id="stat-an-uptime">-- %</div>
                    <small class="text-muted" style="font-size: 10px;">Rango Elegido</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center p-3 h-100 bg-white">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Tiempo En Línea</small>
                    <div class="fs-5 fw-bold text-dark mt-1" id="stat-an-online-time">--</div>
                    <small class="text-danger fw-semibold" style="font-size: 10px;" id="stat-an-offline-time">Caído: --</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center p-3 h-100 bg-white">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Pérdida de Paquetes</small>
                    <div class="fs-4 fw-bold text-secondary mt-1" id="stat-an-loss">-- %</div>
                    <small class="text-muted" style="font-size: 10px;" id="stat-an-probes">Muestras: --</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Ping Equipo -->
    <div class="col-12 col-xl-12" id="contenedorChartPingEquipo" style="display: none;">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title fs-6 fw-bold mb-0">Latencia del Equipo Seleccionado (Ping)</h5>
                    <small class="text-muted">Tiempo de respuesta a la IP del equipo</small>
                </div>
                <button class="btn btn-xs btn-outline-secondary" onclick="abrirModalGraficaAnaliticas('pingEquipo', 'Latencia de Equipo')"><i class="bi bi-arrows-fullscreen me-1"></i> Ampliar</button>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="chartPingEquipo"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Gráfica Ampliada (Zoom & Scroll) -->
<div class="modal fade" id="modalAnaliticasFullScreen" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white py-2">
        <h5 class="modal-title fs-6 fw-bold mb-0" id="modalAnaliticasTitle"><i class="bi bi-arrows-fullscreen me-2"></i> Gráfica Ampliada</h5>
        <div class="ms-auto me-3 d-flex align-items-center gap-2">
            <span class="small text-muted me-2" style="font-size: 11px;">Controles: Rueda / Arrastre</span>
            <button class="btn btn-sm btn-outline-light" onclick="panAnaliticasChart(100)" title="Mover Izquierda"><i class="bi bi-arrow-left"></i></button>
            <button class="btn btn-sm btn-outline-light" onclick="panAnaliticasChart(-100)" title="Mover Derecha"><i class="bi bi-arrow-right"></i></button>
            <button class="btn btn-sm btn-outline-light" onclick="zoomAnaliticasChart(1.2)" title="Acercar"><i class="bi bi-zoom-in"></i></button>
            <button class="btn btn-sm btn-outline-light" onclick="zoomAnaliticasChart(0.8)" title="Alejar"><i class="bi bi-zoom-out"></i></button>
            <button class="btn btn-sm btn-warning" onclick="resetAnaliticasChartZoom()" title="Resetear Vista"><i class="bi bi-arrow-counterclockwise"></i></button>
            <button class="btn btn-sm btn-info text-white" onclick="descargarGraficaAnaliticas()" title="Descargar Imagen"><i class="bi bi-download"></i></button>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body bg-light p-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body h-100 d-flex flex-column p-3">
                <div style="flex-grow: 1; position: relative; height: 100%;">
                    <canvas id="canvasAnaliticasFullScreen"></canvas>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>
