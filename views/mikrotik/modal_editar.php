<!-- Modal Editar MikroTik -->
<div class="modal fade" id="modalEditarMikrotik" tabindex="-1" aria-labelledby="modalEditarMikrotikLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title" id="modalEditarMikrotikLabel"><i class="bi bi-pencil-square"></i> Editar MikroTik</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditarMikrotik">
          <div class="modal-body">
            <input type="hidden" id="e_id" name="id">
            <div class="mb-3">
                <label for="e_nombre" class="form-label">Nombre del Nodo</label>
                <input type="text" class="form-control" id="e_nombre" name="nombre" required>
            </div>
            <div class="row mb-3">
                <div class="col-md-8">
                    <label for="e_ip_address" class="form-label">Dirección IP</label>
                    <input type="text" class="form-control" id="e_ip_address" name="ip_address" required>
                </div>
                <div class="col-md-4">
                    <label for="e_puerto_api" class="form-label">Puerto API</label>
                    <input type="number" class="form-control" id="e_puerto_api" name="puerto_api" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="e_usuario" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="e_usuario" name="usuario" required>
                </div>
                <div class="col-md-6">
                    <label for="e_password" class="form-label">Contraseña <small class="text-muted">(Dejar en blanco para no cambiar)</small></label>
                    <input type="password" class="form-control" id="e_password" name="password">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="e_latitud" class="form-label">Latitud (Opcional)</label>
                    <input type="text" class="form-control" id="e_latitud" name="latitud">
                </div>
                <div class="col-md-6">
                    <label for="e_longitud" class="form-label">Longitud (Opcional)</label>
                    <input type="text" class="form-control" id="e_longitud" name="longitud">
                </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Actualizar</button>
          </div>
      </form>
    </div>
  </div>
</div>
