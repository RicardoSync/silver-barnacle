<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-bell text-secondary me-2"></i> Historial de Alertas</h2>
    <button class="btn btn-outline-secondary shadow-sm" onclick="marcarTodasLeidas(true)">
        <i class="bi bi-check-all me-1"></i> Marcar todas leídas
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaAlertas" class="table table-hover table-striped w-100 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Tipo</th>
                        <th>Router</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
