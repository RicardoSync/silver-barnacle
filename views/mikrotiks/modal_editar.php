<?php
// modal_editar.php - Modal para editar un MikroTik existente
?>
<div class="modal fade" id="modalEditarMikrotik" tabindex="-1" aria-labelledby="modalEditarMikrotikLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title fw-bold" id="modalEditarMikrotikLabel"><i class="bi bi-pencil-square me-2"></i>Editar MikroTik</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditarMikrotik">
        <input type="hidden" id="e_id" name="id">
        <div class="modal-body">
          <div class="mb-3">
            <label for="e_nombre" class="form-label fw-semibold">Nombre del Router / Nodo <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="e_nombre" name="nombre" required>
          </div>
          
          <div class="row">
            <div class="col-md-8 mb-3">
              <label for="e_ip_address" class="form-label fw-semibold">Dirección IP <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="e_ip_address" name="ip_address" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="e_puerto_api" class="form-label fw-semibold">Puerto API</label>
              <input type="number" class="form-control" id="e_puerto_api" name="puerto_api" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="e_usuario" class="form-label fw-semibold">Usuario API <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="e_usuario" name="usuario" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="e_password" class="form-label fw-semibold">Contraseña API</label>
              <input type="password" class="form-control" id="e_password" name="password" placeholder="Dejar en blanco para mantener">
              <small class="text-muted" style="font-size: 0.75rem;">Opcional si no cambia</small>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="e_latitud" class="form-label fw-semibold">Latitud</label>
              <input type="text" class="form-control" id="e_latitud" name="latitud">
            </div>
            <div class="col-md-6 mb-3">
              <label for="e_longitud" class="form-label fw-semibold">Longitud</label>
              <input type="text" class="form-control" id="e_longitud" name="longitud">
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-info text-white fw-bold"><i class="bi bi-check-circle me-1"></i> Actualizar MikroTik</button>
        </div>
      </form>
    </div>
  </div>
</div>
