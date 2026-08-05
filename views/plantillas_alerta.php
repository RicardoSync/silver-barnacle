<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-chat-text-fill text-primary me-2"></i> Plantillas de Alertas</h2>
    <div>
        <button class="btn btn-primary shadow-sm" onclick="openModalNuevaPlantilla()">
            <i class="bi bi-plus-circle me-1"></i> Nueva Plantilla
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaPlantillas" class="table table-hover table-striped w-100 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre de Plantilla</th>
                        <th>Minutos</th>
                        <th>Mensaje de WhatsApp</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nuevo/Editar Plantilla -->
<div class="modal fade" id="modalPlantilla" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <form id="formPlantilla" class="modal-content border-0 shadow">
            <input type="hidden" name="id" id="plantilla_id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalPlantillaTitle"><i class="bi bi-chat-right-text-fill"></i> Registrar Plantilla</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre de la Plantilla</label>
                    <input type="text" class="form-control" name="nombre" id="plantilla_nombre" required placeholder="Ej. Alerta Simple (3 minutos)">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Tiempo de Espera (Minutos)</label>
                    <input type="number" class="form-control" name="minutos" id="plantilla_minutos" min="1" required placeholder="Ej. 5">
                    <div class="form-text">Cantidad de minutos que debe permanecer caído el equipo para enviar esta alerta.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Mensaje de WhatsApp</label>
                    <textarea class="form-control font-monospace" name="mensaje" id="plantilla_mensaje" rows="5" required placeholder="El equipo %nombre% está caído..."></textarea>
                    <div class="form-text bg-light p-2 rounded border border-light-subtle mt-2">
                        <i class="bi bi-info-circle-fill text-primary"></i> <strong>Variables disponibles:</strong>
                        <ul class="mb-0 mt-1">
                            <li><code>%nombre%</code> - Reemplaza por el nombre del nodo caído (ej: Nodo Central).</li>
                            <li><code>%tipo%</code> - Reemplaza por el tipo de nodo (ej: mikrotik o equipo).</li>
                            <li><code>%minutos%</code> - Reemplaza por los minutos transcurridos configurados (ej: 5).</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>
