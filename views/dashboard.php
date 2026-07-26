<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-speedometer2 text-secondary me-2"></i> Inicio</h2>
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary btn-sm shadow-sm" onclick="toggleFullScreen()" title="Pantalla Completa">
            <i class="bi bi-arrows-fullscreen me-1"></i> Pantalla Completa
        </button>
        <span class="badge bg-secondary px-3 py-2" id="dashboard-last-update">Actualizando...</span>
    </div>
</div>

<!-- Tarjetas de Resumen KPI -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0" onclick="setDashboardFilter('all')" style="cursor: pointer;">
            <div class="card-body text-center py-3">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Total Dispositivos</small>
                <h3 class="fw-bold mb-0 text-dark" id="kpi-total">--</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0" onclick="setDashboardFilter('online')" style="cursor: pointer;">
            <div class="card-body text-center py-3">
                <small class="text-success text-uppercase fw-bold" style="font-size: 11px;">En Línea</small>
                <h3 class="fw-bold mb-0 text-success" id="kpi-online">--</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0" onclick="setDashboardFilter('offline')" style="cursor: pointer;">
            <div class="card-body text-center py-3">
                <small class="text-danger text-uppercase fw-bold" style="font-size: 11px;">Caídos</small>
                <h3 class="fw-bold mb-0 text-danger" id="kpi-offline">--</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0" onclick="setDashboardFilter('alerta')" style="cursor: pointer;">
            <div class="card-body text-center py-3">
                <small class="text-warning text-uppercase fw-bold" style="font-size: 11px;">Alertas</small>
                <h3 class="fw-bold mb-0 text-warning" id="kpi-alertas">--</h3>
            </div>
        </div>
    </div>
</div>

<!-- Buscador y Filtros -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-2 px-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <div class="flex-grow-1" style="max-width: 400px;">
            <input type="text" id="wisp-search-input" class="form-control form-control-sm" placeholder="Buscar por nombre o IP..." oninput="onDashboardSearchChange(this.value)">
        </div>
        <div class="btn-group btn-group-sm" role="group" id="wisp-filter-buttons">
            <button type="button" class="btn btn-outline-secondary active" onclick="setDashboardFilter('all')" data-filter="all">Todos</button>
            <button type="button" class="btn btn-outline-danger" onclick="setDashboardFilter('offline')" data-filter="offline">Caídos</button>
            <button type="button" class="btn btn-outline-warning" onclick="setDashboardFilter('alerta')" data-filter="alerta">Alertas</button>
            <button type="button" class="btn btn-outline-success" onclick="setDashboardFilter('online')" data-filter="online">Online</button>
            <button type="button" class="btn btn-outline-primary" onclick="setDashboardFilter('mikrotik')" data-filter="mikrotik">MikroTiks</button>
            <button type="button" class="btn btn-outline-secondary" onclick="setDashboardFilter('equipo')" data-filter="equipo">Equipos</button>
        </div>
    </div>
</div>

<!-- Grid de Tarjetas de Monitoreo WISP -->
<div class="wisp-nodes-grid" id="noc-grid">
    <div class="col-12 text-center text-muted py-5">
        <div class="spinner-border text-primary" role="status" style="width: 2rem; height: 2rem;"></div>
        <p class="mt-2 text-muted">Cargando monitoreo...</p>
    </div>
</div>
