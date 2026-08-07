<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
require_once __DIR__ . '/../../DAO/MikrotikDAO.php';
$mikrotik = (new MikrotikDAO())->obtenerPorId($id);
$nombreMikrotik = $mikrotik ? htmlspecialchars($mikrotik['nombre']) : 'MikroTik';
$ipMikrotik = $mikrotik ? htmlspecialchars($mikrotik['ip_address']) : '0.0.0.0';
?>

<input type="hidden" id="current_mikrotik_id" value="<?php echo $id; ?>">

<!-- Header de la vista de detalles -->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-md-6 col-12">
        <h3 class="mb-0 fw-bold d-flex align-items-center">
          <i class="bi bi-router text-primary me-2"></i>
          Detalles de Router: <?php echo $nombreMikrotik; ?>
        </h3>
        <p class="text-muted small mb-0">Dirección IP: <span class="badge text-bg-dark"><?php echo $ipMikrotik; ?></span></p>
      </div>
      <div class="col-md-6 col-12 text-md-end mt-2 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
        <button class="btn btn-secondary btn-sm shadow-sm" onclick="loadView('mikrotiks/lista')">
          <i class="bi bi-arrow-left me-1"></i> Volver
        </button>
        <button class="btn btn-primary btn-sm shadow-sm" onclick="refreshDetalles()">
          <i class="bi bi-arrow-clockwise me-1"></i> Recargar
        </button>
        <button class="btn btn-outline-dark btn-sm shadow-sm" onclick="backupMikrotik(<?php echo $id; ?>)">
          <i class="bi bi-download me-1"></i> Backup RSC
        </button>
        <button class="btn btn-danger btn-sm shadow-sm" onclick="rebootMikrotik(<?php echo $id; ?>)">
          <i class="bi bi-power me-1"></i> Reiniciar
        </button>
      </div>
    </div>
  </div>
</div>

<div class="app-content mt-3">
  <div class="container-fluid">
    <!-- FILA 1: Widgets de Recursos AdminLTE 4 (CPU, RAM, Disco, Uptime) -->
    <div class="row mb-4">
      <div class="col-lg-3 col-sm-6 col-12 mb-3 mb-lg-0">
        <div class="info-box shadow-sm border-0">
          <span class="info-box-icon text-bg-danger rounded-circle"><i class="bi bi-cpu"></i></span>
          <div class="info-box-content">
            <span class="info-box-text text-muted fw-semibold">Uso de CPU</span>
            <span class="info-box-number fs-4 fw-bold text-danger" id="det-cpu">0%</span>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-sm-6 col-12 mb-3 mb-lg-0">
        <div class="info-box shadow-sm border-0">
          <span class="info-box-icon text-bg-primary rounded-circle"><i class="bi bi-memory"></i></span>
          <div class="info-box-content">
            <span class="info-box-text text-muted fw-semibold">RAM Libre</span>
            <span class="info-box-number fs-4 fw-bold text-primary" id="det-ram">0 MB</span>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-sm-6 col-12 mb-3 mb-lg-0">
        <div class="info-box shadow-sm border-0">
          <span class="info-box-icon text-bg-success rounded-circle"><i class="bi bi-hdd-fill"></i></span>
          <div class="info-box-content">
            <span class="info-box-text text-muted fw-semibold">Disco Libre</span>
            <span class="info-box-number fs-4 fw-bold text-success" id="det-hdd">0 MB</span>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-sm-6 col-12">
        <div class="info-box shadow-sm border-0">
          <span class="info-box-icon text-bg-info rounded-circle text-white"><i class="bi bi-clock-history"></i></span>
          <div class="info-box-content">
            <span class="info-box-text text-muted fw-semibold">Tiempo Activo (Uptime)</span>
            <span class="info-box-number fs-5 fw-bold text-info" id="det-uptime">--</span>
          </div>
        </div>
      </div>
    </div>

    <!-- FILA 2: Gráficas de Latencia ApexCharts (Google 8.8.8.8 & Servidor Local) -->
    <div class="row mb-4">
      <div class="col-lg-6 col-12 mb-3 mb-lg-0">
        <div class="card card-outline card-danger shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-google text-danger me-2"></i>Ping MikroTik &rarr; Google (8.8.8.8)</h3>
            <span class="badge text-bg-light border text-muted">ApexCharts Line</span>
          </div>
          <div class="card-body p-3">
            <div id="chartPingGoogleApex" style="min-height: 230px;"></div>
          </div>
        </div>
      </div>
      <div class="col-lg-6 col-12">
        <div class="card card-outline card-primary shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-server text-primary me-2"></i>Ping MikroTik &rarr; Servidor Local</h3>
            <span class="badge text-bg-light border text-muted">ApexCharts Line</span>
          </div>
          <div class="card-body p-3">
            <div id="chartPingServerApex" style="min-height: 230px;"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- FILA 3: Lista de Interfaces con DataTables y Acción de Tráfico -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card card-outline card-secondary shadow-sm">
          <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-ethernet me-2"></i>Interfaces de Red</h3>
            <span class="badge text-bg-light border text-muted">DataTables</span>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="tablaInterfaces" class="table table-hover align-middle w-100">
                <thead class="table-light">
                  <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Dirección MAC</th>
                    <th>MTU</th>
                    <th>Flags</th>
                    <th>Estado</th>
                    <th class="text-center">Monitoreo</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FILA 4: Tablas ARP y Neighbors (DataTables) -->
    <div class="row mb-4">
      <div class="col-lg-6 col-12 mb-3 mb-lg-0">
        <div class="card card-outline card-info shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-diagram-3 text-info me-2"></i>Tabla ARP</h3>
            <span class="badge text-bg-light border text-muted">DataTables</span>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="tablaArp" class="table table-hover align-middle w-100">
                <thead class="table-light">
                  <tr>
                    <th>IP Address</th>
                    <th>MAC Address</th>
                    <th>Interfaz</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6 col-12">
        <div class="card card-outline card-warning shadow-sm h-100">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-hdd-rack text-warning me-2"></i>Vecinos (Neighbors)</h3>
            <span class="badge text-bg-light border text-muted">DataTables</span>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="tablaNeighbors" class="table table-hover align-middle w-100">
                <thead class="table-light">
                  <tr>
                    <th>Interfaz</th>
                    <th>IP Address</th>
                    <th>MAC Address</th>
                    <th>Identidad (Identity)</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FILA 5: Registros (Logs) -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card card-outline card-dark shadow-sm">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title fs-6 fw-bold m-0"><i class="bi bi-terminal me-2"></i>Registros del Sistema (Logs)</h3>
            <span class="badge text-bg-light border text-muted">Últimos eventos</span>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="tablaLogs" class="table table-hover align-middle w-100">
                <thead class="table-light">
                  <tr>
                    <th style="width: 140px;">Hora</th>
                    <th style="width: 150px;">Topics</th>
                    <th>Mensaje</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Modal de Monitoreo de Tráfico -->
<?php include __DIR__ . '/modal_monitor.php'; ?>
