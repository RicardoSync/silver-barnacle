<?php
session_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-1 fw-bold">
            <i class="bi bi-speedometer2 text-primary me-2"></i>Speedtest y Diagnóstico de Red
        </h2>
        <p class="text-muted small mb-0">Historial y estadísticas de ancho de banda en tiempo real.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary fw-bold shadow-sm px-4 py-2" onclick="abrirModalSpeedtest()">
            <i class="bi bi-play-circle-fill me-2"></i>Nueva Prueba de Velocidad
        </button>
    </div>
</div>

<!-- Tarjetas de Resumen KPI (Full Width) -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-4 border-primary">
            <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 11px;">Promedio Descarga Servidor</small>
            <span class="fs-4 fw-bold text-dark mt-1 d-block" id="kpiAvgDownloadServidor">0.00 Mbps</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-4 border-success">
            <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 11px;">Promedio Subida Servidor</small>
            <span class="fs-4 fw-bold text-dark mt-1 d-block" id="kpiAvgUploadServidor">0.00 Mbps</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-4 border-info">
            <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 11px;">Promedio Enlace Cliente</small>
            <span class="fs-4 fw-bold text-dark mt-1 d-block" id="kpiAvgDownloadCliente">0.00 Mbps</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-4 border-warning">
            <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 11px;">Latencia Promedio</small>
            <span class="fs-4 fw-bold text-dark mt-1 d-block" id="kpiAvgPing">0 ms</span>
        </div>
    </div>
</div>

<!-- Gráfica de Tendencia de Ancho de Banda (Full Width) -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-bold py-3 border-bottom-0 d-flex justify-content-between align-items-center">
        <span><i class="bi bi-graph-up-arrow text-primary me-2"></i>Evolución de Rendimiento de Ancho de Banda</span>
        <small class="text-muted">Últimas 30 mediciones</small>
    </div>
    <div class="card-body pt-0">
        <div style="height: 250px;">
            <canvas id="chartSpeedtestHistorial"></canvas>
        </div>
    </div>
</div>

<!-- Tabla DataTables del Historial (Full Width) -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center border-bottom-0">
        <span><i class="bi bi-table text-primary me-2"></i>Registro Histórico de Pruebas</span>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="limpiarHistorialSpeedtest()">
            <i class="bi bi-trash me-1"></i> Limpiar Todo
        </button>
    </div>
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="tablaSpeedtestHistorial">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Ping / Jitter</th>
                        <th>Descarga (Mbps)</th>
                        <th>Subida (Mbps)</th>
                        <th>Servidor / Destino</th>
                        <th>Usuario</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL EMERGENTE DE SPEEDTEST -->
<div class="modal fade" id="modalSpeedtest" tabindex="-1" aria-labelledby="modalSpeedtestLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalSpeedtestLabel">
                    <i class="bi bi-speedometer2 text-primary me-2"></i>Prueba de Velocidad
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body text-center p-4">
                
                <!-- Selector de Modo -->
                <div class="d-flex justify-content-center mb-3">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm active fw-semibold" id="btnModeServer" onclick="setSpeedtestMode('servidor')">
                            <i class="bi bi-server me-1"></i> Servidor ➔ Internet
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm fw-semibold" id="btnModeClient" onclick="setSpeedtestMode('cliente')">
                            <i class="bi bi-laptop me-1"></i> Cliente ➔ Servidor
                        </button>
                    </div>
                </div>

                <!-- Insignia de Fase Activa -->
                <div class="mb-2">
                    <span id="modalPhaseBadge" class="badge bg-secondary px-3 py-2 fs-7 fw-semibold">
                        ⏱️ PREPARADO
                    </span>
                </div>
                <small id="modalServerTargetText" class="text-muted d-block mb-3">Haga clic en Iniciar Prueba para comenzar</small>

                <!-- Velocímetro Canvas -->
                <div class="position-relative d-inline-block mx-auto my-2" style="width: 320px; height: 210px;">
                    <canvas id="gaugeCanvasModal" width="320" height="210"></canvas>
                    <div class="position-absolute start-50 translate-middle-x" style="bottom: 15px;">
                        <div class="display-5 fw-bold text-dark" id="currentSpeedVal">0.00</div>
                        <div class="text-muted fw-bold text-uppercase fs-7" id="currentPhaseLabel">Mbps</div>
                    </div>
                </div>

                <!-- Barra de Progreso -->
                <div class="progress mb-4 mx-auto" style="height: 4px; max-width: 450px; background-color: #e9ecef;">
                    <div id="speedProgressBar" class="progress-bar bg-primary" role="progressbar" style="width: 0%; transition: width 0.3s ease;"></div>
                </div>

                <!-- Tarjetas de Resultados -->
                <div class="row g-2 text-center mb-4 mx-auto" style="max-width: 550px;">
                    <div class="col-3">
                        <div class="p-2 bg-light rounded-3 border">
                            <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 10px;">Descarga</small>
                            <span class="fs-5 fw-bold text-success" id="valDownload">0.00</span>
                            <small class="text-muted d-block" style="font-size: 10px;">Mbps</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-2 bg-light rounded-3 border">
                            <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 10px;">Subida</small>
                            <span class="fs-5 fw-bold text-primary" id="valUpload">0.00</span>
                            <small class="text-muted d-block" style="font-size: 10px;">Mbps</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-2 bg-light rounded-3 border">
                            <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 10px;">Ping</small>
                            <span class="fs-5 fw-bold text-dark" id="valPing">--</span>
                            <small class="text-muted d-block" style="font-size: 10px;">ms</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-2 bg-light rounded-3 border">
                            <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 10px;">Jitter</small>
                            <span class="fs-5 fw-bold text-dark" id="valJitter">--</span>
                            <small class="text-muted d-block" style="font-size: 10px;">ms</small>
                        </div>
                    </div>
                </div>

                <!-- Botón de Inicio -->
                <div class="d-grid gap-2 col-md-6 mx-auto">
                    <button type="button" class="btn btn-primary btn-lg fw-bold py-2 shadow-sm" id="btnStartSpeedtest" onclick="ejecutarSpeedtest()">
                        <i class="bi bi-play-fill me-1"></i> Iniciar Prueba
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
