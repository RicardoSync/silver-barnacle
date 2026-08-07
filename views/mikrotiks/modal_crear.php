<?php
// modal_crear.php - Modal para registrar un nuevo MikroTik
?>
<div class="modal fade" id="modalNuevoMikrotik" tabindex="-1" aria-labelledby="modalNuevoMikrotikLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold" id="modalNuevoMikrotikLabel"><i class="bi bi-router me-2"></i>Registrar Nuevo MikroTik</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formNuevoMikrotik">
        <div class="modal-body">
          <div class="mb-3">
            <label for="c_nombre" class="form-label fw-semibold">Nombre del Router / Nodo <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="c_nombre" name="nombre" placeholder="Ej: Router Principal NOC" required>
          </div>
          
          <div class="row">
            <div class="col-md-8 mb-3">
              <label for="c_ip_address" class="form-label fw-semibold">Dirección IP <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="c_ip_address" name="ip_address" placeholder="Ej: 192.168.1.1" required>
            </div>
            <div class="col-md-4 mb-3">
              <label for="c_puerto_api" class="form-label fw-semibold">Puerto API</label>
              <input type="number" class="form-control" id="c_puerto_api" name="puerto_api" value="8728" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="c_usuario" class="form-label fw-semibold">Usuario API <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="c_usuario" name="usuario" placeholder="admin" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="c_password" class="form-label fw-semibold">Contraseña API <span class="text-danger">*</span></label>
              <input type="password" class="form-control" id="c_password" name="password" placeholder="••••••••" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="c_latitud" class="form-label fw-semibold">Latitud (Coordenadas)</label>
              <input type="text" class="form-control" id="c_latitud" name="latitud" placeholder="19.432608">
            </div>
            <div class="col-md-6 mb-3">
              <label for="c_longitud" class="form-label fw-semibold">Longitud (Coordenadas)</label>
              <input type="text" class="form-control" id="c_longitud" name="longitud" placeholder="-99.133209">
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-save me-1"></i> Guardar MikroTik</button>
        </div>
      </form>
    </div>
  </div>
</div>
