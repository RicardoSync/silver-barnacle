<div class="app-content-header mb-3">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-sm-6">
        <h3 class="mb-0 fw-bold d-flex align-items-center">
          <i class="bi bi-chat-quote-fill text-warning me-2 fs-3"></i>
          <span>Plantillas de Alerta</span>
        </h3>
        <small class="text-muted">Mensajes preconfigurados para notificaciones automáticas enviadas vía WhatsApp</small>
      </div>
      <div class="col-sm-6 text-end">
        <button type="button" class="btn btn-warning text-dark shadow-sm fw-bold" onclick="openModalNuevaPlantilla()">
          <i class="bi bi-plus-circle-fill me-1"></i> Nueva Plantilla
        </button>
      </div>
    </div>
  </div>
</div>

<div class="app-content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card card-outline card-warning shadow-sm rounded-3">
          <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title fs-6 fw-bold m-0 text-dark">
              <i class="bi bi-chat-left-text me-2 text-warning"></i>Plantillas Registradas
            </h3>
            <span class="badge text-bg-light border text-muted">DataTables</span>
          </div>
          <div class="card-body p-3 table-responsive">
            <table id="tablaPlantillas" class="table table-striped table-hover align-middle w-100" style="font-size: 13px;">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Nombre de Plantilla</th>
                  <th>Disparo (Minutos)</th>
                  <th>Mensaje de Notificación</th>
                  <th class="text-end pe-3">Acciones</th>
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
