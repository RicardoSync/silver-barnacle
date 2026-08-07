<!-- Modal Nueva Plantilla -->
<div class="modal fade" id="modalNuevaPlantilla" tabindex="-1" aria-labelledby="modalNuevaPlantillaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning text-dark py-3">
        <h5 class="modal-title fs-6 fw-bold" id="modalNuevaPlantillaLabel">
          <i class="bi bi-chat-right-text-fill me-2"></i>Registrar Nueva Plantilla de Alerta
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formNuevaPlantilla">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label for="new_plantilla_nombre" class="form-label fw-semibold small text-muted">Nombre de la Plantilla <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-tag"></i></span>
              <input type="text" class="form-control" id="new_plantilla_nombre" name="nombre" placeholder="Ej. Alerta Inicial (3 minutos)" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="new_plantilla_minutos" class="form-label fw-semibold small text-muted">Minutos Transcurridos de Caída <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-clock-history"></i></span>
              <input type="number" class="form-control" id="new_plantilla_minutos" name="minutos" min="1" placeholder="Ej. 3" required>
              <span class="input-group-text bg-light">minutos</span>
            </div>
          </div>

          <div class="mb-3">
            <label for="new_plantilla_mensaje" class="form-label fw-semibold small text-muted">Mensaje de WhatsApp <span class="text-danger">*</span></label>
            <textarea class="form-control font-monospace" id="new_plantilla_mensaje" name="mensaje" rows="4" placeholder="Escribe el cuerpo del mensaje..." required></textarea>
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
            <i class="bi bi-check-circle me-1"></i> Guardar Plantilla
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
