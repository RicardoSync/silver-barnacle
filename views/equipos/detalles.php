<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-hdd-network text-secondary me-2"></i> Detalles del Equipo</h2>
    <div>
        <button class="btn btn-success text-white me-2" onclick="refreshEquiposDetalles()"><i class="bi bi-arrow-repeat"></i> Refrescar</button>
        <button class="btn btn-secondary" onclick="loadView('equipos/lista')"><i class="bi bi-arrow-left"></i> Volver</button>
    </div>
</div>

<!-- Nav Tabs -->
<ul class="nav nav-tabs mb-4" id="equipoTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active fw-bold" id="envivo-tab" data-bs-toggle="tab" data-bs-target="#tab-envivo" type="button" role="tab"><i class="bi bi-broadcast"></i> En Vivo</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-bold" id="historico-tab" data-bs-toggle="tab" data-bs-target="#tab-historico" type="button" role="tab" onclick="loadEquipoHistorico()"><i class="bi bi-bar-chart-fill"></i> Estadísticas (24h)</button>
  </li>
</ul>

<div class="tab-content" id="equipoTabsContent">
  
  <!-- PESTAÑA: EN VIVO -->
  <div class="tab-pane fade show active" id="tab-envivo" role="tabpanel">
      <!-- Tarjetas de Información Rápida -->
      <div class="row mb-4" id="cards-info-equipo">
          <div class="col-md-3">
              <div class="card shadow-sm">
                  <div class="card-body">
                      <h6 class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px;">Nombre</h6>
                      <p class="fs-5 fw-bold mb-0 text-primary" id="det-eq-nombre">Cargando...</p>
                  </div>
              </div>
          </div>
          <div class="col-md-3">
              <div class="card shadow-sm">
                  <div class="card-body">
                      <h6 class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px;">IP Address</h6>
                      <p class="fs-5 fw-bold mb-0 text-success" id="det-eq-ip">Cargando...</p>
                  </div>
              </div>
          </div>
          <div class="col-md-3">
              <div class="card shadow-sm">
                  <div class="card-body">
                      <h6 class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px;">Comunidad SNMP</h6>
                      <p class="fs-5 fw-bold mb-0 text-info" id="det-eq-comunidad">Cargando...</p>
                  </div>
              </div>
          </div>
          <div class="col-md-3">
              <div class="card shadow-sm">
                  <div class="card-body">
                      <h6 class="text-muted text-uppercase fw-bold mb-1" style="font-size: 11px;">Contacto / Lugar</h6>
                      <p class="fs-5 fw-bold mb-0 text-secondary" id="det-eq-contacto">Cargando...</p>
                  </div>
              </div>
          </div>
      </div>

      <!-- Gráfica de Ping En Vivo -->
      <div class="row mb-4">
          <div class="col-12">
              <div class="card shadow-sm">
                  <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                      <span><i class="bi bi-activity text-danger"></i> Latencia en Vivo (Servidor -> Equipo)</span>
                      <span class="badge bg-danger" id="ping-live-status">Monitoreando</span>
                  </div>
                  <div class="card-body">
                      <canvas id="chartEquipoPingLive" height="80"></canvas>
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
                      <span><i class="bi bi-clock-history"></i> Latencia Histórica - Últimas 24 Horas (Base de Datos)</span>
                      <button class="btn btn-sm btn-outline-secondary" onclick="openChartFullScreen('eq_historico', 'Historial de Latencia (24h)')"><i class="bi bi-arrows-fullscreen"></i> Ampliar</button>
                  </div>
                  <div class="card-body">
                      <canvas id="chartEquipoPingHist" height="80"></canvas>
                  </div>
              </div>
          </div>
      </div>
  </div>

</div> <!-- /tab-content -->

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

<input type="hidden" id="current_equipo_id" value="<?php echo $id; ?>">
