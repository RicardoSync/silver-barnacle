<!-- Modal Nuevo Usuario -->
<div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-labelledby="modalNuevoUsuarioLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title fs-6 fw-bold" id="modalNuevoUsuarioLabel">
          <i class="bi bi-person-plus-fill me-2"></i>Registrar Nuevo Usuario
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formNuevoUsuario">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label for="new-nombre" class="form-label fw-semibold small text-muted">Nombre Completo <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
              <input type="text" class="form-control" id="new-nombre" name="nombre" placeholder="Ej. Ricardo Escobedo" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="new-correo" class="form-label fw-semibold small text-muted">Correo Electrónico <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
              <input type="email" class="form-control" id="new-correo" name="correo" placeholder="correo@ejemplo.com" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="new-password" class="form-label fw-semibold small text-muted">Contraseña <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control" id="new-password" name="password" placeholder="Mínimo 6 caracteres" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="new-rol" class="form-label fw-semibold small text-muted">Rol de Acceso <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-shield-lock"></i></span>
              <select class="form-select" id="new-rol" name="rol" required>
                <option value="tecnico" selected>Técnico</option>
                <option value="administrador">Administrador</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-primary px-4 fw-semibold">
            <i class="bi bi-check-circle me-1"></i> Registrar Usuario
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
