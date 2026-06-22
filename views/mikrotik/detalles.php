<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-display text-secondary me-2"></i> Detalles del MikroTik</h2>
    <div>
        <button class="btn btn-success text-white me-2" onclick="refreshDetalles()"><i class="bi bi-arrow-repeat"></i> Refrescar</button>
        <button class="btn btn-warning text-dark me-2" onclick="rebootMikrotik(<?php echo $id; ?>)"><i class="bi bi-arrow-clockwise"></i> Reiniciar</button>
        <button class="btn btn-primary" onclick="backupMikrotik(<?php echo $id; ?>)"><i class="bi bi-download"></i> Backup .rsc</button>
        <button class="btn btn-secondary ms-2" onclick="loadView('mikrotiks')"><i class="bi bi-arrow-left"></i> Volver</button>
    </div>
</div>

<!-- Nav Tabs -->
<ul class="nav nav-tabs mb-4" id="mikrotikTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active fw-bold" id="envivo-tab" data-bs-toggle="tab" data-bs-target="#tab-envivo" type="button" role="tab"><i class="bi bi-broadcast"></i> En Vivo</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-bold" id="historico-tab" data-bs-toggle="tab" data-bs-target="#tab-historico" type="button" role="tab" onclick="loadHistorico()"><i class="bi bi-bar-chart-fill"></i> Estadísticas</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-bold" id="logs-tab" data-bs-toggle="tab" data-bs-target="#tab-logs" type="button" role="tab"><i class="bi bi-journal-text"></i> Logs</button>
  </li>
</ul>

<div class="tab-content" id="mikrotikTabsContent">
  
  <!-- PESTAÑA: EN VIVO -->
  <div class="tab-pane fade show active" id="tab-envivo" role="tabpanel">
      <!-- Tarjetas de Información Rápida -->
      <div class="row mb-4" id="cards-historico">
          <div class="col-md-3">
              <div class="card shadow-sm">
                  <div class="card-body">
                      <h6 class="text-muted text-uppercase fw-bold mb-1">CPU</h6>
                      <p class="fs-4 fw-bold mb-0 text-primary" id="det-cpu">--%</p>
                  </div>
              </div>
          </div>
          <div class="col-md-3">
              <div class="card shadow-sm">
                  <div class="card-body">
                      <h6 class="text-muted text-uppercase fw-bold mb-1">RAM Libre</h6>
                      <p class="fs-4 fw-bold mb-0 text-success" id="det-ram">-- MB</p>
                  </div>
              </div>
          </div>
          <div class="col-md-3">
              <div class="card shadow-sm">
                  <div class="card-body">
                      <h6 class="text-muted text-uppercase fw-bold mb-1">Disco Libre</h6>
                      <p class="fs-4 fw-bold mb-0 text-info" id="det-hdd">-- MB</p>
                  </div>
              </div>
          </div>
          <div class="col-md-3">
              <div class="card shadow-sm">
                  <div class="card-body">
                      <h6 class="text-muted text-uppercase fw-bold mb-1">Uptime</h6>
                      <p class="fs-5 fw-bold mb-0 text-secondary" id="det-uptime">--</p>
                  </div>
              </div>
          </div>
      </div>

      <!-- Gráficas de Ping -->
      <div class="row mb-4">
          <div class="col-md-6">
              <div class="card shadow-sm">
                  <div class="card-header bg-white fw-bold">
                      <i class="bi bi-activity text-danger"></i> Ping a Google (8.8.8.8)
                  </div>
                  <div class="card-body">
                      <canvas id="chartPingGoogle" height="100"></canvas>
                  </div>
              </div>
          </div>
          <div class="col-md-6">
              <div class="card shadow-sm">
                  <div class="card-header bg-white fw-bold">
                      <i class="bi bi-server text-primary"></i> Ping al Servidor Monitor
                  </div>
                  <div class="card-body">
                      <canvas id="chartPingServer" height="100"></canvas>
                  </div>
              </div>
          </div>
      </div>

      <!-- Tablas -->
      <div class="row">
          <div class="col-12 mb-4">
              <div class="card shadow-sm">
                  <div class="card-header bg-white fw-bold"><i class="bi bi-ethernet"></i> Interfaces</div>
                  <div class="card-body table-responsive">
                      <table id="tablaInterfaces" class="table table-hover table-striped w-100">
                          <thead class="table-dark">
                              <tr>
                                  <th>Nombre</th>
                                  <th>Tipo</th>
                                  <th>MAC</th>
                                  <th>MTU</th>
                                  <th>Flags</th>
                                  <th>Estado</th>
                                  <th>Acciones</th>
                              </tr>
                          </thead>
                          <tbody></tbody>
                      </table>
                  </div>
              </div>
          </div>
      </div>

      <div class="row">
          <div class="col-md-6 mb-4">
              <div class="card shadow-sm">
                  <div class="card-header bg-white fw-bold"><i class="bi bi-hdd-network"></i> Tabla ARP</div>
                  <div class="card-body table-responsive">
                      <table id="tablaArp" class="table table-hover table-striped w-100">
                          <thead class="table-dark">
                              <tr>
                                  <th>IP</th>
                                  <th>MAC</th>
                                  <th>Interfaz</th>
                              </tr>
                          </thead>
                          <tbody></tbody>
                      </table>
                  </div>
              </div>
          </div>
          <div class="col-md-6 mb-4">
              <div class="card shadow-sm">
                  <div class="card-header bg-white fw-bold"><i class="bi bi-diagram-3"></i> Vecinos (Neighbors)</div>
                  <div class="card-body table-responsive">
                      <table id="tablaNeighbors" class="table table-hover table-striped w-100">
                          <thead class="table-dark">
                              <tr>
                                  <th>Interfaz</th>
                                  <th>IP</th>
                                  <th>MAC</th>
                                  <th>Identidad</th>
                              </tr>
                          </thead>
                          <tbody></tbody>
                      </table>
                  </div>
              </div>
          </div>
      </div>
  </div>

  <!-- PESTAÑA: ESTADÍSTICAS (HISTÓRICO) -->
  <div class="tab-pane fade" id="tab-historico" role="tabpanel">
      <div class="row mb-4">
          <div class="col-12">
              <div class="card shadow-sm">
                  <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                      <span><i class="bi bi-cpu"></i> Uso de Recursos (24h)</span>
                      <button class="btn btn-sm btn-outline-secondary" onclick="openChartFullScreen('recursos', 'Uso de Recursos (24h)')"><i class="bi bi-arrows-fullscreen"></i> Ampliar</button>
                  </div>
                  <div class="card-body">
                      <canvas id="chartHistRecursos" height="80"></canvas>
                  </div>
              </div>
          </div>
      </div>
      <div class="row mb-4">
          <div class="col-12">
              <div class="card shadow-sm">
                  <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                      <span><i class="bi bi-activity"></i> Latencia Promedio (24h)</span>
                      <button class="btn btn-sm btn-outline-secondary" onclick="openChartFullScreen('ping', 'Latencia Promedio (24h)')"><i class="bi bi-arrows-fullscreen"></i> Ampliar</button>
                  </div>
                  <div class="card-body">
                      <canvas id="chartHistPing" height="80"></canvas>
                  </div>
              </div>
          </div>
      </div>
      <div class="row mb-2">
          <div class="col-12">
              <h5 class="fw-bold text-secondary"><i class="bi bi-graph-up"></i> Tráfico de Interfaces (24h)</h5>
              <hr>
          </div>
      </div>
      <div class="row" id="hist-traffic-container">
          <!-- Las gráficas se generarán aquí dinámicamente -->
      </div>
  </div>

  <!-- PESTAÑA: LOGS -->
  <div class="tab-pane fade" id="tab-logs" role="tabpanel">
      <div class="row">
          <div class="col-12 mb-4">
              <div class="card shadow-sm">
                  <div class="card-header bg-white fw-bold"><i class="bi bi-journal-text"></i> System Logs</div>
                  <div class="card-body table-responsive">
                      <table id="tablaLogs" class="table table-hover table-striped w-100">
                          <thead class="table-dark">
                              <tr>
                                  <th>Tiempo</th>
                                  <th>Tópico</th>
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

</div> <!-- /tab-content -->

<!-- Modal Traffic Monitor -->
<div class="modal fade" id="modalTrafficMonitor" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title"><i class="bi bi-graph-up"></i> Monitor de Tráfico: <span id="tm-interface-name" class="text-info"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="stopTrafficMonitor()"></button>
      </div>
      <div class="modal-body bg-light">
          <div class="row text-center mb-3">
              <div class="col-6">
                  <h6 class="text-muted text-uppercase mb-1">Descarga (RX)</h6>
                  <h4 class="text-success fw-bold" id="tm-rx-text">0.00 Mbps</h4>
              </div>
              <div class="col-6">
                  <h6 class="text-muted text-uppercase mb-1">Subida (TX)</h6>
                  <h4 class="text-primary fw-bold" id="tm-tx-text">0.00 Mbps</h4>
              </div>
          </div>
          <div class="card shadow-sm">
              <div class="card-body">
                  <canvas id="chartTraffic" height="100"></canvas>
              </div>
          </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Chart Full Screen -->
<div class="modal fade" id="modalChartFullScreen" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="fs-chart-title">Gráfica en Pantalla Completa</h5>
        <div class="ms-auto me-3">
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-light" onclick="zoomFullScreenChart('10m')">10 Min</button>
                <button class="btn btn-outline-light" onclick="zoomFullScreenChart('1h')">1 Hora</button>
                <button class="btn btn-outline-light" onclick="zoomFullScreenChart('3h')">3 Horas</button>
                <button class="btn btn-outline-light" onclick="zoomFullScreenChart('24h')">24 Horas</button>
                <button class="btn btn-outline-light" onclick="zoomFullScreenChart('1w')">1 Semana</button>
                <button class="btn btn-warning" onclick="resetFullScreenChartZoom()">Reset</button>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="closeChartFullScreen()"></button>
      </div>
      <div class="modal-body bg-light p-4">
        <div class="card shadow-sm h-100">
            <div class="card-body h-100 d-flex flex-column">
                <canvas id="chartFullScreenCanvas" style="flex-grow: 1;"></canvas>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>

<input type="hidden" id="current_mikrotik_id" value="<?php echo $id; ?>">
