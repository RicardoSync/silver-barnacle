<!-- Modal Unificado para Crear y Editar Servicio / DNS / Puerto -->
<div class="modal fade" id="modalServicio" tabindex="-1" aria-labelledby="modalServicioTitulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-primary text-white py-2" id="modalServicioHeader">
        <h5 class="modal-title fs-6 fw-bold" id="modalServicioTitulo">
          <i class="bi bi-plus-circle me-2"></i> Registrar Servicio DNS / Web
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formServicio">
        <input type="hidden" id="servicio_id" name="id" value="">
        <div class="modal-body">
          
          <div class="mb-3">
            <label for="servicio_nombre" class="form-label fw-bold">Nombre del Servicio <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="servicio_nombre" name="nombre" placeholder="ej. DNS Cloudflare, Web Google, API Mikrotik" required>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label for="servicio_tipo" class="form-label fw-bold">Tipo de Protocolo <span class="text-danger">*</span></label>
              <select class="form-select" id="servicio_tipo" name="tipo" onchange="onTipoServicioChange()" required>
                <option value="dns">DNS Resolv (IP / Dominio)</option>
                <option value="http">HTTP / HTTPS (Web / URL)</option>
                <option value="puerto">TCP Port Ping (IP + Puerto)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="servicio_target" class="form-label fw-bold">Target / IP / Dominio <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="servicio_target" name="target" placeholder="ej. 8.8.8.8 o google.com" required>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label for="servicio_puerto" class="form-label fw-bold">Puerto TCP</label>
              <input type="number" class="form-control" id="servicio_puerto" name="puerto" placeholder="ej. 80, 443, 53" disabled>
            </div>
            <div class="col-md-6">
              <label for="servicio_umbral" class="form-label fw-bold">Umbral Alerta (ms)</label>
              <input type="number" class="form-control" id="servicio_umbral" name="umbral_ms" value="300" min="10" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="servicio_estado" class="form-label fw-bold">Estado</label>
            <select class="form-select" id="servicio_estado" name="estado">
              <option value="1" selected>Activo (Monitoreando)</option>
              <option value="0">Inactivo (Pausado)</option>
            </select>
          </div>

        </div>
        <div class="modal-footer py-2 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary btn-sm" id="btnGuardarServicio"><i class="bi bi-save me-1"></i> Guardar Servicio</button>
        </div>
      </form>
    </div>
  </div>
</div>
