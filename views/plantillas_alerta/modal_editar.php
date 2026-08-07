<!-- Modal Editar Plantilla -->
<div class="modal fade" id="modalEditarPlantilla" tabindex="-1" aria-labelledby="modalEditarPlantillaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning text-dark py-3">
        <h5 class="modal-title fs-6 fw-bold" id="modalEditarPlantillaLabel">
          <i class="bi bi-pencil-square me-2"></i>Editar Plantilla de Alerta
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formEditarPlantilla">
        <input type="hidden" id="edit_plantilla_id" name="id" value="">

        <div class="modal-body p-4">
          <div class="mb-3">
            <label for="edit_plantilla_nombre" class="form-label fw-semibold small text-muted">Nombre de la Plantilla <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-tag"></i></span>
              <input type="text" class="form-control" id="edit_plantilla_nombre" name="nombre" placeholder="Nombre..." required>
            </div>
          </div>

          <div class="mb-3">
            <label for="edit_plantilla_minutos" class="form-label fw-semibold small text-muted">Minutos Transcurridos de Caída <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-clock-history"></i></span>
              <input type="number" class="form-control" id="edit_plantilla_minutos" name="minutos" min="1" required>
              <span class="input-group-text bg-light">minutos</span>
            </div>
          </div>

          <div class="mb-3">
            <label for="edit_plantilla_mensaje" class="form-label fw-semibold small text-muted">Mensaje de WhatsApp <span class="text-danger">*</span></label>
            <textarea class="form-control font-monospace" id="edit_plantilla_mensaje" name="mensaje" rows="4" required></textarea>
            <div class="mt-2 small text-muted">
              <strong>Variables disponibles:</strong>
              <span class="badge bg-secondary me-1">%nombre%</span>
              <span class="badge bg-secondary me-1">%tipo%</span>
              <span class="badge bg-secondary">%minutos%</span>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-warning text-dark px-4 fw-bold">
            <i class="bi bi-save me-1"></i> Guardar Cambios
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
