<!-- Modal Probar WhatsApp -->
<div class="modal fade" id="modalTestWhatsapp" tabindex="-1" aria-labelledby="modalTestWhatsappLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white py-3">
        <h5 class="modal-title fs-6 fw-bold" id="modalTestWhatsappLabel">
          <i class="bi bi-whatsapp me-2"></i>Probar Envío de WhatsApp (WAHA)
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formTestWhatsapp">
        <div class="modal-body p-4">
          <p class="text-muted small mb-3">
            Envía un mensaje de prueba a un número telefónico para verificar que tu API Key y Endpoint de WAHA estén funcionando correctamente.
          </p>

          <div class="mb-3">
            <label for="test-telefono" class="form-label fw-semibold small text-muted">Número Telefónico (con código de país) <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-telephone-fill"></i></span>
              <input type="text" class="form-control" id="test-telefono" name="telefono" placeholder="Ej. 5215512345678" required>
            </div>
            <small class="form-text text-muted">Incluye código de país sin espacios ni guiones (ej. 521... para México).</small>
          </div>
        </div>

        <div class="modal-footer bg-light py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-success px-4 fw-semibold">
            <i class="bi bi-send-fill me-1"></i> Enviar Mensaje de Prueba
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
