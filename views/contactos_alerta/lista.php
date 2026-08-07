<div class="app-content-header mb-3">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-sm-6">
        <h3 class="mb-0 fw-bold d-flex align-items-center">
          <i class="bi bi-telephone-fill text-primary me-2 fs-3"></i>
          <span>Números de Alerta</span>
        </h3>
        <small class="text-muted">Directorio de contactos para envío de notificaciones automáticas por WhatsApp</small>
      </div>
      <div class="col-sm-6 text-end">
        <button type="button" class="btn btn-outline-success me-2 shadow-sm" onclick="openModalTestWhatsapp()">
          <i class="bi bi-send-fill me-1"></i> Probar Envío
        </button>
        <button type="button" class="btn btn-primary shadow-sm" onclick="openModalNuevoContacto()">
          <i class="bi bi-person-plus-fill me-1"></i> Nuevo Contacto
        </button>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card card-outline card-primary shadow-sm rounded-3">
          <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title fs-6 fw-bold m-0 text-dark">
              <i class="bi bi-person-lines-fill me-2 text-primary"></i>Contactos Registrados
            </h3>
            <span class="badge text-bg-light border text-muted">DataTables</span>
          </div>
          <div class="card-body p-3 table-responsive">
            <table id="tablaContactos" class="table table-striped table-hover align-middle w-100" style="font-size: 13px;">
              <thead class="table-light">
                <tr>
                  <th style="width: 10%;">ID</th>
                  <th style="width: 40%;">Nombre del Contacto</th>
                  <th style="width: 35%;">Teléfono / WhatsApp</th>
                  <th style="width: 15%;" class="text-end pe-3">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <!-- Contenido cargado dinámicamente vía AJAX -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
