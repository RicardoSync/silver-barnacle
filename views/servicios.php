<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-globe text-secondary me-2"></i> Monitoreo de DNS y Servicios</h2>
    <button class="btn btn-primary shadow-sm" onclick="abrirModalCrearServicio()">
        <i class="bi bi-plus-circle-fill me-1"></i> Nuevo Servicio
    </button>
</div>

<!-- Tarjetas KPI -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted text-uppercase fw-bold mb-1">Total Monitoreados</h6>
                <p class="fs-4 fw-bold mb-0 text-dark" id="kpi-total-servicios">0</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted text-uppercase fw-bold mb-1">Servicios Online</h6>
                <p class="fs-4 fw-bold mb-0 text-success" id="kpi-online-servicios">0</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted text-uppercase fw-bold mb-1">Alta Latencia</h6>
                <p class="fs-4 fw-bold mb-0 text-warning" id="kpi-lento-servicios">0</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted text-uppercase fw-bold mb-1">Servicios Caídos</h6>
                <p class="fs-4 fw-bold mb-0 text-danger" id="kpi-offline-servicios">0</p>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Registros -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table text-primary me-2"></i> Lista de Servicios Configurados</span>
        <button class="btn btn-sm btn-outline-secondary" onclick="cargarServicios()">
            <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaServicios" class="table table-hover table-striped w-100 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Objetivo / Target</th>
                        <th>Latencia (ms)</th>
                        <th>Detalle / IP</th>
                        <th>Estado</th>
                        <th>Última Prueba</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-servicios">
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">Cargando servicios...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nuevo / Editar Servicio -->
<div class="modal fade" id="modalServicio" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <form id="formServicio" class="modal-content border-0 shadow">
            <input type="hidden" id="servicio_id" name="id">
            <div class="modal-header bg-primary text-white" id="modalServicioHeader">
                <h5 class="modal-title" id="modalServicioTitulo"><i class="bi bi-plus-circle me-1"></i> Nuevo Servicio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre del Servicio</label>
                    <input type="text" class="form-control" id="servicio_nombre" name="nombre" placeholder="Ej. Google DNS o Portal Web" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tipo de Monitoreo</label>
                        <select class="form-select" id="servicio_tipo" name="tipo" onchange="onTipoServicioChange()" required>
                            <option value="dns">Resolución DNS</option>
                            <option value="http">Servicio HTTP / HTTPS</option>
                            <option value="puerto">Puerto TCP</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Umbral Latencia (ms)</label>
                        <input type="number" class="form-control" id="servicio_umbral" name="umbral_ms" value="300" min="10" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold">Objetivo (Target / Host / URL)</label>
                        <input type="text" class="form-control" id="servicio_target" name="target" placeholder="Ej. google.com o https://ejemplo.com" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Puerto TCP</label>
                        <input type="number" class="form-control" id="servicio_puerto" name="puerto" placeholder="80, 53, 443" disabled>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Estado</label>
                    <select class="form-select" id="servicio_estado" name="estado">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Guardar Servicio</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Gráfica Histórica -->
<div class="modal fade" id="modalGraficaServicio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalGraficaTitulo"><i class="bi bi-graph-up me-1"></i> Histórico de Latencia</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <div class="card shadow-sm">
                    <div class="card-body" style="height: 350px;">
                        <canvas id="chartServicioHistorico"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
