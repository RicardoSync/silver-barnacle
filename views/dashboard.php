<!-- Dashboard NOC Control Center - Estilo Light -->
<div class="noc-dashboard-container p-3 rounded-4" style="background-color: #f8f9fa; color: #212529; min-height: 88vh;">

    <!-- Top Header Bar -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <h5 class="fw-bold text-dark mb-0 me-2">
                <i class="bi bi-speedometer2 text-primary me-2"></i>Monitoreo de Red NOC
            </h5>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                <i class="bi bi-circle-fill me-1" style="font-size: 8px !important;"></i> En Vivo
            </span>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Reloj Digital Header -->
            <div class="text-end bg-white px-3 py-1 rounded-3 border border-secondary border-opacity-25 shadow-sm d-flex align-items-center gap-2">
                <span class="fw-bold text-primary font-monospace" id="noc-digital-clock" style="font-size: 13px;">--:--:--</span>
                <span class="text-muted border-start ps-2" style="font-size: 11px;" id="noc-date-string">...</span>
            </div>

            <button class="btn btn-outline-secondary btn-sm shadow-sm" onclick="toggleFullScreen()" title="Pantalla Completa">
                <i class="bi bi-arrows-fullscreen"></i>
            </button>
        </div>
    </div>

    <!-- Fila Superior: Gráfica Tráfico WAN 1.2s + Resumen Host Availability + Gauge CPU -->
    <div class="row g-3 mb-3 align-items-stretch">

        <!-- Widget 1: Gráficas de Latencia (Ping a Google y Cloudflare) -->
        <div class="col-12 col-xl-6">
            <div class="card bg-white border border-secondary border-opacity-25 shadow-sm h-100 rounded-3">
                <div class="card-header bg-transparent border-bottom border-secondary border-opacity-25 py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark small text-uppercase">
                        <i class="bi bi-activity text-danger me-2"></i>Latencia (Ping) en Tiempo Real
                    </span>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill" style="font-size: 10px;">
                        <i class="bi bi-arrow-repeat spin me-1"></i> Live
                    </span>
                </div>
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <!-- Google Ping Chart (Grande) -->
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Ping a Google (8.8.8.8)</small>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" id="live-ping-google-val">-- ms</span>
                        </div>
                        <div style="height: 120px; width: 100%;">
                            <canvas id="chart-ping-google"></canvas>
                        </div>
                    </div>
                    
                    <hr class="border-secondary border-opacity-25 my-2">
                    
                    <!-- Cloudflare Ping Chart (Pequeña) -->
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Ping a Cloudflare (1.1.1.1)</small>
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25" id="live-ping-cloudflare-val">-- ms</span>
                        </div>
                        <div style="height: 70px; width: 100%;">
                            <canvas id="chart-ping-cloudflare"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Widget 2: Resumen de Host Availability -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card bg-white border border-secondary border-opacity-25 shadow-sm h-100 rounded-3">
                <div class="card-header bg-transparent border-bottom border-secondary border-opacity-25 py-2">
                    <span class="fw-bold text-dark small text-uppercase">
                        <i class="bi bi-hdd-stack me-2 text-primary"></i>Estado Servidores & Hosts
                    </span>
                </div>
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <!-- Banner de Disponibilidad -->
                    <div class="row g-1 text-center mb-2">
                        <div class="col-6">
                            <div class="p-2 rounded-2 bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                <div class="fs-3 fw-bold" id="kpi-available-count">0</div>
                                <div class="small fw-semibold text-uppercase" style="font-size: 9px;">Disponibles (UP)</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded-2 bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                <div class="fs-3 fw-bold" id="kpi-unavailable-count">0</div>
                                <div class="small fw-semibold text-uppercase" style="font-size: 9px;">Caídos (DOWN)</div>
                            </div>
                        </div>
                        <div class="col-6 mt-1">
                            <div class="p-2 rounded-2 bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">
                                <div class="fs-4 fw-bold" id="kpi-warning-count">0</div>
                                <div class="small fw-semibold text-uppercase" style="font-size: 9px;">Alertas</div>
                            </div>
                        </div>
                        <div class="col-6 mt-1">
                            <div class="p-2 rounded-2 bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">
                                <div class="fs-4 fw-bold" id="kpi-total-count">0</div>
                                <div class="small fw-semibold text-uppercase" style="font-size: 9px;">Total Registrados</div>
                            </div>
                        </div>
                    </div>

                    <!-- Estado Global de Salud -->
                    <div class="p-2 rounded-2 bg-light text-center border border-secondary border-opacity-25" id="global-health-banner">
                        <small class="text-muted text-uppercase" style="font-size: 10px;">Salud Global de Red</small>
                        <div class="fw-bold fs-6 text-success mt-1" id="global-health-status">OPTIMO</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Widget 3: Gauge de Uso CPU & RAM del Servidor -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card bg-white border border-secondary border-opacity-25 shadow-sm h-100 rounded-3">
                <div class="card-header bg-transparent border-bottom border-secondary border-opacity-25 py-2">
                    <span class="fw-bold text-dark small text-uppercase">
                        <i class="bi bi-server text-warning me-2"></i>Recursos del Servidor
                    </span>
                </div>
                <div class="card-body p-3 text-center d-flex flex-column justify-content-between align-items-center">
                    
                    <div class="d-flex justify-content-around w-100 mb-2">
                        <!-- CPU Dial -->
                        <div class="d-flex flex-column align-items-center">
                            <div class="speedometer-wrapper position-relative my-1" style="width: 100px; height: 55px;">
                                <svg viewBox="0 0 100 55" class="speedometer-svg">
                                    <path d="M 10 50 A 40 40 0 0 1 90 50" fill="none" stroke="#e2e8f0" stroke-width="8" stroke-linecap="round" />
                                    <path id="gauge-cpu-path" d="M 10 50 A 40 40 0 0 1 90 50" fill="none" stroke="#10b981" stroke-width="8" stroke-dasharray="125.6" stroke-dashoffset="125.6" stroke-linecap="round" style="transition: stroke-dashoffset 0.8s ease;" />
                                    <g id="gauge-cpu-needle-group" style="transition: transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1); transform-origin: 50px 50px;">
                                        <polygon points="50,12 46.5,50 53.5,50" fill="#1e293b" />
                                        <circle cx="50" cy="50" r="5" fill="#3b82f6" stroke="#ffffff" stroke-width="1.5" />
                                    </g>
                                </svg>
                                <div class="speedometer-val text-dark fw-bold" id="gauge-cpu-val" style="font-size: 13px;">0%</div>
                            </div>
                            <small class="text-muted text-uppercase fw-bold mt-1" style="font-size: 10px;">CPU Local</small>
                        </div>
                        
                        <!-- RAM Dial -->
                        <div class="d-flex flex-column align-items-center">
                            <div class="speedometer-wrapper position-relative my-1" style="width: 100px; height: 55px;">
                                <svg viewBox="0 0 100 55" class="speedometer-svg">
                                    <path d="M 10 50 A 40 40 0 0 1 90 50" fill="none" stroke="#e2e8f0" stroke-width="8" stroke-linecap="round" />
                                    <path id="gauge-ram-path" d="M 10 50 A 40 40 0 0 1 90 50" fill="none" stroke="#3b82f6" stroke-width="8" stroke-dasharray="125.6" stroke-dashoffset="125.6" stroke-linecap="round" style="transition: stroke-dashoffset 0.8s ease;" />
                                    <g id="gauge-ram-needle-group" style="transition: transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1); transform-origin: 50px 50px;">
                                        <polygon points="50,12 46.5,50 53.5,50" fill="#1e293b" />
                                        <circle cx="50" cy="50" r="5" fill="#10b981" stroke="#ffffff" stroke-width="1.5" />
                                    </g>
                                </svg>
                                <div class="speedometer-val text-dark fw-bold" id="gauge-ram-val" style="font-size: 13px;">0%</div>
                            </div>
                            <small class="text-muted text-uppercase fw-bold mt-1" style="font-size: 10px;">RAM Local</small>
                        </div>
                    </div>

                    <div class="w-100 bg-light p-2 rounded-2 mt-2 border border-secondary border-opacity-25 text-start" style="font-size: 11px;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Servidor Activo:</span>
                            <span class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i>En línea</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Fila Intermedia: Salud de Navegación, DNS y Servicios Web -->
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card bg-white border border-secondary border-opacity-25 shadow-sm rounded-3">
                <div class="card-header bg-transparent border-bottom border-secondary border-opacity-25 py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark small text-uppercase">
                        <i class="bi bi-globe text-primary me-2"></i>Salud de Navegación, DNS & Servicios Web
                    </span>
                    <a href="#" data-view="servicios" onclick="document.querySelector('[data-view=\'servicios\']').click();" class="text-decoration-none small text-primary fw-semibold">
                        Ver Módulo Completo <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 align-items-center" id="dashboard-services-container">
                        <div class="col-12 text-center py-2 text-muted small">Cargando estado de servicios web...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fila Inferior: Panel Problemas Activos (Izquierda) + Matriz Colmena Conectividad (Derecha) -->
    <div class="row g-3 align-items-stretch">

        <!-- Widget 4 (Izquierda): Tabla de Problemas Activos / Nodos Caídos -->
        <div class="col-12 col-xl-6">
            <div class="card bg-white border border-secondary border-opacity-25 shadow-sm h-100 rounded-3">
                <div class="card-header bg-transparent border-bottom border-secondary border-opacity-25 py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark small text-uppercase">
                        <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Problemas Activos (Dispositivos Caídos / Alertas)
                    </span>
                    <span class="badge bg-danger text-white font-monospace" id="problem-count-badge">0 Problemas</span>
                </div>
                <div class="card-body p-0 table-responsive" style="max-height: 380px; min-height: 320px;">
                    <table class="table table-light table-hover mb-0 align-middle" style="font-size: 12px; background: transparent;">
                        <thead>
                            <tr class="border-bottom border-secondary border-opacity-25 text-muted text-uppercase" style="font-size: 10px;">
                                <th>Dispositivo</th>
                                <th>IP</th>
                                <th>Severidad / Estado</th>
                                <th>Ping</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="problems-tbody">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-1"></i>
                                    No hay incidentes activos en la red.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Widget 5 (Derecha): Recursos por MikroTik (Diales CPU y RAM desde BD) -->
        <div class="col-12 col-xl-6">
            <div class="card bg-white border border-secondary border-opacity-25 shadow-sm h-100 rounded-3">
                <div class="card-header bg-transparent border-bottom border-secondary border-opacity-25 py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark small text-uppercase">
                        <i class="bi bi-cpu text-primary me-2"></i>Recursos por MikroTik (Monitoreo BD)
                    </span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill" style="font-size: 10px;">
                        <i class="bi bi-arrow-repeat me-1"></i> AJAX (1 min)
                    </span>
                </div>
                <div class="card-body p-3" style="max-height: 380px; min-height: 320px; overflow-y: auto;">
                    <div class="row g-3" id="mikrotik-dials-container">
                        <div class="col-12 text-center py-4 text-muted small">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                            Cargando medidores de MikroTiks...
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>



