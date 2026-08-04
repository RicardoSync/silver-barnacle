<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-6">
        <h3 class="fw-bold mb-1 text-dark">
            <i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>NOC Multigráfica
        </h3>
        <p class="text-muted small mb-0">Monitoreo de latencia en tiempo real para 2, 4, 6 u 8 dispositivos seleccionados.</p>
    </div>
    <div class="col-12 col-md-6 text-md-end mt-3 mt-md-0 d-flex flex-wrap justify-content-md-end gap-2 align-items-center">
        <!-- Selector de Cantidad de Gráficas -->
        <div class="d-flex align-items-center bg-white p-1 px-2 rounded border shadow-sm">
            <label for="select-grid-count" class="small me-2 text-muted fw-bold text-nowrap mb-0">Gráficas:</label>
            <select class="form-select form-select-sm border-0 bg-light fw-bold" id="select-grid-count" style="width: 110px;">
                <option value="2">2 Vistas</option>
                <option value="4" selected>4 Vistas</option>
                <option value="6">6 Vistas</option>
                <option value="8">8 Vistas</option>
            </select>
        </div>

        <!-- Selector de Intervalo -->
        <div class="d-flex align-items-center bg-white p-1 px-2 rounded border shadow-sm">
            <label for="select-interval-multi" class="small me-2 text-muted fw-bold text-nowrap mb-0">Intervalo:</label>
            <select class="form-select form-select-sm border-0 bg-light fw-bold" id="select-interval-multi" style="width: 110px;">
                <option value="1000">Cada 1s</option>
                <option value="2000" selected>Cada 2s</option>
                <option value="3000">Cada 3s</option>
                <option value="5000">Cada 5s</option>
                <option value="10000">Cada 10s</option>
            </select>
        </div>

        <!-- Botones de Acción -->
        <button class="btn btn-sm btn-success shadow-sm" id="btn-toggle-multi" onclick="toggleMonitoreoMulti()">
            <i class="bi bi-pause-fill me-1" id="icon-toggle-multi"></i> <span id="text-toggle-multi">Pausar</span>
        </button>
        <button class="btn btn-sm btn-primary shadow-sm" onclick="ejecutarBatchPingAhora()">
            <i class="bi bi-arrow-repeat me-1"></i> Ping Ahora
        </button>
        <button class="btn btn-sm btn-outline-secondary shadow-sm" onclick="guardarConfiguracionMulti()" title="Guardar IPs seleccionadas">
            <i class="bi bi-floppy-fill me-1"></i> Guardar
        </button>
    </div>
</div>

<!-- Banner Informativo -->
<div class="alert alert-light border shadow-sm d-flex align-items-center justify-content-between py-2 px-3 mb-4 rounded-3">
    <div class="d-flex align-items-center">
        <i class="bi bi-info-circle-fill text-primary fs-5 me-2"></i>
        <small class="text-dark">
            Selecciona los equipos importantes (ONUs, APs, MikroTiks o Google) en cada gráfica. La vista monitoreará en tiempo real el tiempo de respuesta (ms).
        </small>
    </div>
    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" id="lbl-status-batch">
        <i class="bi bi-activity text-success me-1"></i> Monitoreando activo
    </span>
</div>

<!-- Grid de Gráficas Multi-Ping -->
<div class="row g-3" id="grid-ping-multi">
    <!-- Generado dinámicamente por ping_multi.js -->
</div>
