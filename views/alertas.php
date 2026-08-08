<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-0 fw-bold text-dark"><i class="bi bi-bell-fill text-primary me-2"></i>Centro de Notificaciones</h3>
            <p class="text-muted mb-0">Historial completo de alertas generadas por los equipos monitorizados.</p>
        </div>
        <button class="btn btn-primary shadow-sm rounded-pill px-4" onclick="marcarTodasLeidas(true)">
            <i class="bi bi-check2-all me-1"></i> Marcar todas como leídas
        </button>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table id="tablaAlertas" class="table table-hover align-middle w-100">
                <thead class="table-light text-secondary">
                    <tr>
                        <th class="fw-semibold">Fecha y Hora</th>
                        <th class="fw-semibold">Tipo</th>
                        <th class="fw-semibold">Equipo / Nodo</th>
                        <th class="fw-semibold" style="width: 40%">Mensaje</th>
                        <th class="fw-semibold">Estado</th>
                        <th class="text-end fw-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Llenado por DataTables mediante ajax en alertas.js -->
                </tbody>
            </table>
        </div>
    </div>
</div>
