<div class="app-content-header mb-3">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-sm-6">
        <h3 class="mb-0 fw-bold d-flex align-items-center">
          <i class="bi bi-whatsapp text-success me-2 fs-3"></i>
          <span>Configuración WhatsApp (WAHA API)</span>
        </h3>
        <small class="text-muted">Gestión de API Key, Endpoints y envío de notificaciones automáticas de red</small>
      </div>
      <div class="col-sm-6 text-end">
        <button type="button" class="btn btn-outline-success shadow-sm me-2" onclick="openModalTestWhatsapp()">
          <i class="bi bi-send-fill me-1"></i> Probar Envío
        </button>
        <button type="button" class="btn btn-success shadow-sm" onclick="cargarConfiguracion()">
          <i class="bi bi-arrow-clockwise me-1"></i> Recargar
        </button>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-8 col-12 mx-auto">
        <div class="card card-outline card-success shadow-sm rounded-3">
          <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h3 class="card-title fs-6 fw-bold m-0 text-dark">
              <i class="bi bi-gear-wide-connected text-success me-2"></i>Parámetros de Integración WAHA
            </h3>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">API Conectada</span>
          </div>

          <form id="formWhatsappConfig">
            <div class="card-body p-4">
              <!-- URL Servidor WAHA -->
              <div class="mb-3">
                <label for="waha_url" class="form-label fw-semibold small text-muted">
                  URL Endpoint WAHA API <span class="text-danger">*</span>
                </label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light"><i class="bi bi-link-45deg"></i></span>
                  <input type="text" class="form-control" id="waha_url" name="waha_url" placeholder="http://localhost:3000/api/sendText" required>
                </div>
                <small class="form-text text-muted">Ejemplo: http://192.168.1.50:3000/api/sendText o https://waha.midominio.com/api/sendText</small>
              </div>

              <!-- API Key WAHA -->
              <div class="mb-3">
                <label for="waha_api_key" class="form-label fw-semibold small text-muted">
                  API Key de WAHA
                </label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light"><i class="bi bi-key-fill"></i></span>
                  <input type="password" class="form-control" id="waha_api_key" name="waha_api_key" placeholder="Tu API Key secreta de WAHA">
                </div>
                <small class="form-text text-muted">Si tu servidor WAHA requiere clave de autorización HTTP / Header X-Api-Key.</small>
              </div>

              <hr class="my-4">

              <!-- URL Pública del Sistema -->
              <div class="mb-3">
                <label for="url_sistema" class="form-label fw-semibold small text-muted">
                  URL Pública de tu Panel de Monitoreo <span class="text-danger">*</span>
                </label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light"><i class="bi bi-globe2"></i></span>
                  <input type="text" class="form-control" id="url_sistema" name="url_sistema" placeholder="http://midominio.com/monitoreo" required>
                </div>
                <small class="form-text text-muted">Se utiliza para incrustar enlaces directos a reportes de caída en los mensajes de WhatsApp.</small>
              </div>

              <!-- Token / API Secret del Sistema -->
              <div class="mb-3">
                <label for="api_secret" class="form-label fw-semibold small text-muted">
                  Secret Token de Seguridad del Sistema <span class="text-danger">*</span>
                </label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light"><i class="bi bi-shield-lock-fill"></i></span>
                  <input type="text" class="form-control" id="api_secret" name="api_secret" placeholder="WISP_SEC_2026" required>
                </div>
              </div>

              <!-- Switch Enlaces Públicos -->
              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="enlaces_publicos_activos" name="enlaces_publicos_activos" value="1" checked>
                <label class="form-check-label fw-semibold small text-dark" for="enlaces_publicos_activos">
                  Habilitar inclusión de enlaces de reporte en notificaciones de WhatsApp
                </label>
              </div>
            </div>

            <div class="card-footer bg-light py-3 d-flex justify-content-between align-items-center">
              <button type="button" class="btn btn-sm btn-outline-success" onclick="openModalTestWhatsapp()">
                <i class="bi bi-chat-left-dots-fill me-1"></i> Probar Envío de Alerta
              </button>
              <button type="submit" class="btn btn-sm btn-success px-4 fw-bold">
                <i class="bi bi-check-circle-fill me-1"></i> Guardar Configuración
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
