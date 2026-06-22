<!-- Modal Nuevo MikroTik -->
<div class="modal fade" id="modalNuevoMikrotik" tabindex="-1" aria-labelledby="modalNuevoMikrotikLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalNuevoMikrotikLabel"><i class="bi bi-router"></i> Nuevo MikroTik</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formNuevoMikrotik">
          <div class="modal-body">
            <div class="mb-3">
                <label for="n_nombre" class="form-label">Nombre del Nodo</label>
                <input type="text" class="form-control" id="n_nombre" name="nombre" required>
            </div>
            <div class="row mb-3">
                <div class="col-md-8">
                    <label for="n_ip_address" class="form-label">Dirección IP</label>
                    <input type="text" class="form-control" id="n_ip_address" name="ip_address" required>
                </div>
                <div class="col-md-4">
                    <label for="n_puerto_api" class="form-label">Puerto API</label>
                    <input type="number" class="form-control" id="n_puerto_api" name="puerto_api" value="8728" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="n_usuario" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="n_usuario" name="usuario" required>
                </div>
                <div class="col-md-6">
                    <label for="n_password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="n_password" name="password" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="n_latitud" class="form-label">Latitud (Opcional)</label>
                    <input type="text" class="form-control" id="n_latitud" name="latitud">
                </div>
                <div class="col-md-6">
                    <label for="n_longitud" class="form-label">Longitud (Opcional)</label>
                    <input type="text" class="form-control" id="n_longitud" name="longitud">
                </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
          </div>
      </form>
    </div>
  </div>
</div>
