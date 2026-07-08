<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-telephone-fill text-primary me-2"></i> Números de Alerta</h2>
    <button class="btn btn-primary shadow-sm" onclick="openModalNuevoContacto()">
        <i class="bi bi-plus-circle me-1"></i> Nuevo Contacto
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaContactos" class="table table-hover table-striped w-100 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nuevo/Editar Contacto -->
<div class="modal fade" id="modalContacto" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <form id="formContacto" class="modal-content border-0 shadow">
            <input type="hidden" name="id" id="contacto_id">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalContactoTitle"><i class="bi bi-person-lines-fill"></i> Registrar Contacto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre</label>
                    <input type="text" class="form-control" name="nombre" id="contacto_nombre" required placeholder="Ej. Ricardo Escobedo">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Teléfono (con código de país)</label>
                    <input type="text" class="form-control" name="telefono" id="contacto_telefono" required placeholder="Ej. 5215563018444">
                    <div class="form-text">Sin símbolos ni espacios, solo números (ej. 521...).</div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>
