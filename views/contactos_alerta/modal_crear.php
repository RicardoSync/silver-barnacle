<!-- Modal Nuevo Contacto -->
<div class="modal fade" id="modalNuevoContacto" tabindex="-1" aria-labelledby="modalNuevoContactoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title fs-6 fw-bold" id="modalNuevoContactoLabel">
          <i class="bi bi-person-plus-fill me-2"></i>Registrar Contacto de Alerta
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formNuevoContacto">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label for="new_contacto_nombre" class="form-label fw-semibold small text-muted">Nombre del Contacto <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
              <input type="text" class="form-control" id="new_contacto_nombre" name="nombre" placeholder="Ej. Ing. Carlos Guardado" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="new_contacto_telefono" class="form-label fw-semibold small text-muted">Número Telefónico (con Código de País) <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-whatsapp"></i></span>
              <input type="text" class="form-control" id="new_contacto_telefono" name="telefono" placeholder="Ej. 5215512345678" required>
            </div>
            <small class="form-text text-muted">Incluye código de país sin espacios ni guiones (ej. 521 para México).</small>
          </div>
        </div>

        <div class="modal-footer bg-light py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-primary px-4 fw-semibold">
            <i class="bi bi-check-circle me-1"></i> Guardar Contacto
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
