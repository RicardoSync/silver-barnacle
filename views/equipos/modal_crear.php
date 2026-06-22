<!-- Modal Nuevo Equipo -->
<div class="modal fade" id="modalNuevoEquipo" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <form id="formNuevoEquipo" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Registrar Equipo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre / Identificador</label>
                    <input type="text" class="form-control" name="nombre" required placeholder="Ej. Ubiquiti AP 120">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Dirección IP</label>
                    <input type="text" class="form-control" name="ip_address" required placeholder="192.168.1.10">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Usuario (Opcional)</label>
                        <input type="text" class="form-control" name="usuario" placeholder="admin">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Contraseña (Opcional)</label>
                        <input type="password" class="form-control" name="password">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Comunidad SNMP</label>
                    <input type="text" class="form-control" name="comunidad_snmp" placeholder="public">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Contacto SNMP (Lugar/Responsable)</label>
                    <input type="text" class="form-control" name="contacto_snmp" placeholder="Site Principal">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Equipo</button>
            </div>
        </form>
    </div>
</div>
