<!-- Modal Editar Equipo -->
<div class="modal fade" id="modalEditarEquipo" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <form id="formEditarEquipo" class="modal-content border-0 shadow">
            <input type="hidden" name="id" id="edit-equipo-id">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Editar Equipo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre / Identificador</label>
                    <input type="text" class="form-control" name="nombre" id="edit-equipo-nombre" required placeholder="Ej. Ubiquiti AP 120">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Dirección IP</label>
                    <input type="text" class="form-control" name="ip_address" id="edit-equipo-ip" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Usuario (Opcional)</label>
                        <input type="text" class="form-control" name="usuario" id="edit-equipo-usuario">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Nueva Contraseña</label>
                        <input type="password" class="form-control" name="password" id="edit-equipo-password" placeholder="***">
                        <div class="form-text text-muted" style="font-size: 0.75rem;"><i class="bi bi-info-circle"></i> Déjalo en blanco si no deseas cambiarla.</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Comunidad SNMP</label>
                    <input type="text" class="form-control" name="comunidad_snmp" id="edit-equipo-comunidad">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Contacto SNMP</label>
                    <input type="text" class="form-control" name="contacto_snmp" id="edit-equipo-contacto">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-dark"><i class="bi bi-arrow-repeat"></i> Actualizar Equipo</button>
            </div>
        </form>
    </div>
</div>
