<div class="px-3 pb-3 pt-2 bg-light min-height-88vh rounded-3">
    <div class="row g-3 mb-3">
        <!-- Card 1: Total Nodos -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block small mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Dispositivos Totales</span>
                        <h3 class="fw-bold mb-0 text-dark" id="kpi-total-count">0</h3>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-hdd-network fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 2: UP (Online) -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block small mb-1" style="font-size: 11px; letter-spacing: 0.5px;">En Línea (UP)</span>
                        <h3 class="fw-bold mb-0 text-success" id="kpi-available-count">0</h3>
                    </div>
                    <div class="text-success">
                        <i class="bi bi-check-circle fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 3: DOWN (Offline) -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 h-100" id="kpi-offline-card-wrapper">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block small mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Caídos (DOWN)</span>
                        <h3 class="fw-bold mb-0 text-danger" id="kpi-unavailable-count">0</h3>
                    </div>
                    <div class="text-danger" id="kpi-offline-icon-box">
                        <i class="bi bi-x-circle fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Card 4: Alertas -->
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card shadow-sm border-0 h-100" id="kpi-warning-card-wrapper">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block small mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Alertas Activas</span>
                        <h3 class="fw-bold mb-0 text-warning" id="kpi-warning-count">0</h3>
                    </div>
                    <div class="text-warning" id="kpi-warning-icon-box">
                        <i class="bi bi-exclamation-triangle fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <!-- Gráfica de Latencia Realtime -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-activity text-danger me-2"></i>Latencia de Red (Ping)
                        </h6>
                        <small class="text-muted">Tiempo de respuesta a Google y Cloudflare en tiempo real</small>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" id="live-ping-google-val">-- ms</span>
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25" id="live-ping-cloudflare-val">-- ms</span>
                    </div>
                </div>
                <div class="card-body">
                    <div style="height: 290px; width: 100%;">
                        <canvas id="chart-ping-realtime"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recursos del Servidor & Top MikroTiks -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-cpu text-primary me-2"></i>Recursos y Carga de Red
                    </h6>
                    <small class="text-muted">Estado del servidor local y nodos principales</small>
                </div>
                <div class="card-body">
                    <!-- Recursos Locales (Servidor) -->
                    <div class="pb-3 mb-3 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-center mb-1 small">
                            <span class="text-dark fw-semibold">CPU del Servidor Local</span>
                            <span class="text-muted fw-bold" id="gauge-cpu-val">0%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" id="progress-cpu-local" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <div class="pb-3 mb-3 border-bottom border-light">
                        <div class="d-flex justify-content-between align-items-center mb-1 small">
                            <span class="text-dark fw-semibold">RAM del Servidor Local</span>
                            <span class="text-muted fw-bold" id="gauge-ram-val">0%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" id="progress-ram-local" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Top 3 MikroTiks por uso de CPU -->
                    <div>
                        <small class="text-muted text-uppercase fw-bold d-block mb-3" style="font-size: 10px; letter-spacing: 0.5px;">Carga de CPU en MikroTiks</small>
                        <div id="dashboard-top-cpu-container">
                            <div class="text-center py-3 text-muted small">Cargando telemetría de MikroTiks...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fila 3: Incidentes Activos & Servicios Críticos -->
    <div class="row g-3">
        <!-- Incidentes y Dispositivos Caídos -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-exclamation-triangle text-danger me-2"></i>Incidentes y Caídas Activas
                        </h6>
                        <small class="text-muted">Equipos desconectados o con fallas en este momento</small>
                    </div>
                    <span class="badge bg-success" id="problem-count-badge">Red Operativa</span>
                </div>
                <div class="card-body p-0 mt-3" style="max-height: 290px; min-height: 220px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-muted text-uppercase">
                                <th class="ps-3" style="font-size: 10px; font-weight: 700;">Dispositivo</th>
                                <th style="font-size: 10px; font-weight: 700;">IP Address</th>
                                <th style="font-size: 10px; font-weight: 700;">Estado / Gravedad</th>
                                <th style="font-size: 10px; font-weight: 700;">Latencia</th>
                                <th class="text-end pe-3" style="font-size: 10px; font-weight: 700;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="problems-tbody">
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-shield-check text-success fs-2 d-block mb-2"></i>
                                    <span class="fw-semibold">No hay incidentes activos en la red.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Servicios Críticos / DNS -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-globe text-primary me-2"></i>Servicios DNS y Web Críticos
                        </h6>
                        <small class="text-muted">Estado y latencia de dominios y puertos externos</small>
                    </div>
                    <a href="#" data-view="servicios" onclick="document.querySelector('[data-view=\'servicios\']').click();" class="text-decoration-none small text-primary fw-semibold">
                        Administrar <i class="bi bg-transparent bi-chevron-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush" id="dashboard-services-container" style="max-height: 250px; overflow-y: auto;">
                        <div class="text-center py-4 text-muted small">Cargando estado de servicios...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>



