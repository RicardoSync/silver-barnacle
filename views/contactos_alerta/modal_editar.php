<!-- Modal Editar Contacto -->
<div class="modal fade" id="modalEditarContacto" tabindex="-1" aria-labelledby="modalEditarContactoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title fs-6 fw-bold" id="modalEditarContactoLabel">
          <i class="bi bi-pencil-square me-2"></i>Editar Contacto de Alerta
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formEditarContacto">
        <input type="hidden" id="edit_contacto_id" name="id" value="">

        <div class="modal-body p-4">
          <div class="mb-3">
            <label for="edit_contacto_nombre" class="form-label fw-semibold small text-muted">Nombre del Contacto <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
              <input type="text" class="form-control" id="edit_contacto_nombre" name="nombre" placeholder="Nombre..." required>
            </div>
          </div>

          <div class="mb-3">
            <label for="edit_contacto_telefono" class="form-label fw-semibold small text-muted">Número Telefónico <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-whatsapp"></i></span>
              <input type="text" class="form-control" id="edit_contacto_telefono" name="telefono" placeholder="Teléfono..." required>
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
