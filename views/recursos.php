<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-motherboard text-secondary me-2"></i> Inventario de Hardware</h2>
    <div>
        <button class="btn btn-outline-success" onclick="refreshRecursos()"><i class="bi bi-arrow-repeat"></i> Refrescar</button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaRecursos" class="table table-hover table-striped w-100 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Equipo</th>
                        <th>IP</th>
                        <th>RouterOS</th>
                        <th>Uptime</th>
                        <th>CPU</th>
                        <th>Memoria RAM</th>
                        <th>Almacenamiento</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
