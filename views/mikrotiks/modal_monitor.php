<?php
// modal_monitor.php - Modal para monitoreo de tráfico de interfaz en tiempo real
?>
<div class="modal fade" id="modalTrafficMonitor" tabindex="-1" aria-labelledby="modalTrafficMonitorLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header bg-dark text-white py-2">
        <h5 class="modal-title fs-6 fw-bold" id="modalTrafficMonitorLabel">
          <i class="bi bi-graph-up text-success me-2"></i>Monitoreo de Tráfico en Vivo: <span id="tm-interface-name" class="text-warning">--</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="stopTrafficMonitor()"></button>
      </div>
      <div class="modal-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="badge text-bg-success fs-6 px-3 py-2">
            <i class="bi bi-download me-1"></i>Descarga (RX): <strong id="tm-rx-text">0 Kbps</strong>
          </span>
          <span class="badge text-bg-primary fs-6 px-3 py-2">
            <i class="bi bi-upload me-1"></i>Subida (TX): <strong id="tm-tx-text">0 Kbps</strong>
          </span>
        </div>
        <div style="height: 250px; position: relative;">
          <canvas id="chartTraffic"></canvas>
        </div>
      </div>
      <div class="modal-footer py-2 bg-light">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="stopTrafficMonitor()">Cerrar Monitor</button>
      </div>
    </div>
  </div>
</div>
