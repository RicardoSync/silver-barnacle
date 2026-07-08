<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-whatsapp text-success me-2"></i> Configuración de WhatsApp (WAHA)</h2>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-gear-fill"></i> Parámetros de la API</h5>
            </div>
            <div class="card-body">
                <form id="formWhatsappConfig">
                    <div class="mb-3">
                        <label class="form-label fw-bold">WAHA URL</label>
                        <input type="url" class="form-control" name="waha_url" id="waha_url" required placeholder="http://localhost:3000/api/sendText">
                        <div class="form-text">URL de tu servidor WAHA para enviar mensajes.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">WAHA API Key</label>
                        <input type="text" class="form-control" name="waha_api_key" id="waha_api_key" placeholder="Tu API Key de WAHA">
                        <div class="form-text">Opcional si configuraste tu contenedor WAHA con seguridad por apiKey.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">URL Pública del Sistema</label>
                        <input type="url" class="form-control" name="url_sistema" id="url_sistema" required placeholder="http://mi-dominio.com">
                        <div class="form-text">Usada para generar los enlaces de descarga de PDFs de los cortes.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">API Secret (Descarga de PDFs)</label>
                        <input type="text" class="form-control" name="api_secret" id="api_secret" required>
                        <div class="form-text">Token de seguridad para permitir la descarga del PDF sin iniciar sesión.</div>
                    </div>

                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enlaces_publicos_activos" name="enlaces_publicos_activos" value="1">
                        <label class="form-check-label fw-bold" for="enlaces_publicos_activos">Activar enlaces públicos en mensajes</label>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-save"></i> Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


