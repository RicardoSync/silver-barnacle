<div class="app-content-header mb-3">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-sm-6">
        <h3 class="mb-0 fw-bold d-flex align-items-center">
          <i class="bi bi-people-fill text-primary me-2 fs-3"></i>
          <span>Gestión de Usuarios</span>
        </h3>
        <small class="text-muted">Administración de usuarios y permisos de acceso al sistema</small>
      </div>
      <div class="col-sm-6 text-end">
        <button type="button" class="btn btn-primary shadow-sm" onclick="openModalNuevoUsuario()">
          <i class="bi bi-person-plus-fill me-1"></i> Nuevo Usuario
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
              <i class="bi bi-list-ul me-2 text-primary"></i>Usuarios Registrados
            </h3>
            <span class="badge text-bg-light border text-muted">DataTables Server</span>
          </div>
          <div class="card-body p-3 table-responsive">
            <table id="tablaUsuarios" class="table table-striped table-hover align-middle w-100" style="font-size: 13px;">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Nombre Completo</th>
                  <th>Correo Electrónico</th>
                  <th>Rol</th>
                  <th>Fecha Registro</th>
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
