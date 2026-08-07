<?php
// modal_ping.php - Modal para monitoreo de ping en tiempo real de un Equipo
?>
<div class="modal fade" id="modalPingEquipo" tabindex="-1" aria-labelledby="modalPingEquipoLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-dark text-white py-2">
        <h5 class="modal-title fs-6 fw-bold" id="modalPingEquipoLabel">
          <i class="bi bi-activity text-success me-2"></i> Ping en Vivo: <span id="ping-equipo-nombre" class="text-warning">--</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="stopPingEquipoLive()"></button>
      </div>
      <div class="modal-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="badge text-bg-dark fs-6 px-3 py-2">
            IP: <strong id="ping-equipo-ip">0.0.0.0</strong>
          </span>
          <span class="badge text-bg-success fs-6 px-3 py-2" id="ping-equipo-latency-badge">
            <i class="bi bi-speedometer2 me-1"></i> Latencia: <strong id="ping-equipo-latency-text">0 ms</strong>
          </span>
        </div>
        <div style="height: 250px; position: relative;">
          <canvas id="chartPingLiveEquipo"></canvas>
        </div>
      </div>
      <div class="modal-footer py-2 bg-light">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="stopPingEquipoLive()">Cerrar Monitor</button>
      </div>
    </div>
  </div>
</div>
