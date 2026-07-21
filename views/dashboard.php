<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-speedometer2 text-secondary me-2"></i> Global NOC Dashboard</h2>
    <div>
        <button class="btn btn-sm btn-outline-secondary me-2" onclick="toggleFullScreen()" title="Pantalla Completa"><i class="bi bi-arrows-fullscreen"></i></button>
        <span class="badge bg-secondary me-2" id="dashboard-last-update">Actualizando...</span>
    </div>
</div>

<!-- KPIs Superiores -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-secondary">
            <div class="card-body">
                <h6 class="text-muted text-uppercase fw-bold mb-1">Total Equipos</h6>
                <h3 class="fw-bold mb-0 text-dark" id="kpi-total">--</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-success">
            <div class="card-body">
                <h6 class="text-muted text-uppercase fw-bold mb-1">En Línea</h6>
                <h3 class="fw-bold mb-0 text-success" id="kpi-online">--</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-danger">
            <div class="card-body">
                <h6 class="text-muted text-uppercase fw-bold mb-1">Caídos</h6>
                <h3 class="fw-bold mb-0 text-danger" id="kpi-offline">--</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 border-start border-4 border-warning">
            <div class="card-body">
                <h6 class="text-muted text-uppercase fw-bold mb-1">Alertas (CPU/Ping)</h6>
                <h3 class="fw-bold mb-0 text-warning" id="kpi-alertas">--</h3>
            </div>
        </div>
    </div>
</div>

<!-- Grid de Nodos -->
<div class="noc-grid-container" id="noc-grid">
    <!-- Nodos cargados dinámicamente -->
    <div class="col-12 text-center text-muted mt-5" style="grid-column: 1 / -1;">
        <div class="spinner-border" role="status"></div>
        <p class="mt-2">Cargando estado de la red...</p>
    </div>
</div>
