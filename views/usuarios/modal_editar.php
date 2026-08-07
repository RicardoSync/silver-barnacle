<!-- Modal Editar Usuario -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-labelledby="modalEditarUsuarioLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title fs-6 fw-bold" id="modalEditarUsuarioLabel">
          <i class="bi bi-pencil-square me-2"></i>Editar Datos de Usuario
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formEditarUsuario">
        <input type="hidden" id="edit-user-id" name="id" value="0">

        <div class="modal-body p-4">
          <div class="mb-3">
            <label for="edit-nombre" class="form-label fw-semibold small text-muted">Nombre Completo <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
              <input type="text" class="form-control" id="edit-nombre" name="nombre" placeholder="Nombre completo..." required>
            </div>
          </div>

          <div class="mb-3">
            <label for="edit-correo" class="form-label fw-semibold small text-muted">Correo Electrónico <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
              <input type="email" class="form-control" id="edit-correo" name="correo" placeholder="correo@ejemplo.com" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="edit-password" class="form-label fw-semibold small text-muted">Contraseña <small class="text-muted">(Dejar en blanco para no cambiar)</small></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
              <input type="password" class="form-control" id="edit-password" name="password" placeholder="Nueva contraseña (opcional)">
            </div>
          </div>

          <div class="mb-3">
            <label for="edit-rol" class="form-label fw-semibold small text-muted">Rol de Acceso <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-shield-lock"></i></span>
              <select class="form-select" id="edit-rol" name="rol" required>
                <option value="tecnico">Técnico</option>
                <option value="administrador">Administrador</option>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-primary px-4 fw-semibold">
            <i class="bi bi-save me-1"></i> Guardar Cambios
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
