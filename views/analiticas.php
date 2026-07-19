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
                    <?php foreach ($mikrotiks as $m): ?>
                        <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['alias'] . ' (' . $m['ip_address'] . ')'); ?></option>
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
    <!-- Contenedor Dinámico para Interfaces de Tráfico -->
    <div class="col-12" id="contenedorInterfaces">
        <!-- Aquí se inyectarán las gráficas de tráfico independientes -->
    </div>

    <!-- Ping Mikrotik -->
    <div class="col-12 col-xl-6" id="contenedorChartPingMikrotik">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                <h5 class="card-title fs-6 fw-bold mb-0">Latencia (Ping)</h5>
                <small class="text-muted">Tiempo de respuesta hacia el servidor y Google</small>
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
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                <h5 class="card-title fs-6 fw-bold mb-0">Consumo de Recursos</h5>
                <small class="text-muted">Uso de CPU y Memoria RAM</small>
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
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                <h5 class="card-title fs-6 fw-bold mb-0">Top Nodos con más Caídas</h5>
                <small class="text-muted">Según el rango de tiempo seleccionado (Global)</small>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="chartCaidas"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Ping Equipo -->
    <div class="col-12 col-xl-12" id="contenedorChartPingEquipo" style="display: none;">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                <h5 class="card-title fs-6 fw-bold mb-0">Latencia del Equipo Seleccionado (Ping)</h5>
                <small class="text-muted">Tiempo de respuesta a la IP del equipo</small>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="chartPingEquipo"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
