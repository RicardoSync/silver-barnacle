<?php
// modal_estadisticas.php - Modal para consultar el historial de ping de un Equipo con filtros temporales
?>
<div class="modal fade" id="modalEstadisticasEquipo" tabindex="-1" aria-labelledby="modalEstadisticasEquipoLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-dark text-white py-2">
        <h5 class="modal-title fs-6 fw-bold" id="modalEstadisticasEquipoLabel">
          <i class="bi bi-graph-up-arrow text-info me-2"></i> Historial de Ping: <span id="st-equipo-nombre" class="text-warning">--</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <input type="hidden" id="st-equipo-id-current">
        
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <label for="selectFilterPingHoras" class="form-label fw-bold small mb-0 me-1"><i class="bi bi-funnel me-1"></i> Rango Temporal:</label>
            <select id="selectFilterPingHoras" class="form-select form-select-sm" style="width: auto;" onchange="cargarEstadisticasPingEquipoActual()">
              <option value="0.16667">10 Minutos</option>
              <option value="0.5">30 Minutos</option>
              <option value="1">1 Hora</option>
              <option value="2">2 Horas</option>
              <option value="4" selected>4 Horas</option>
              <option value="8">8 Horas</option>
              <option value="24">1 Día (24 Horas)</option>
              <option value="168">1 Semana (7 Días)</option>
            </select>
          </div>

          <div class="d-flex gap-2">
            <span class="badge text-bg-light border text-secondary" id="st-equipo-probes">Muestras: 0</span>
          </div>
        </div>

        <!-- KPI Cards -->
        <div class="row g-2 mb-3">
          <div class="col-md-3 col-6">
            <div class="border rounded p-2 text-center bg-light">
              <span class="text-muted small d-block">Ping Promedio</span>
              <strong class="fs-6 text-success" id="st-ping-avg">0 ms</strong>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="border rounded p-2 text-center bg-light">
              <span class="text-muted small d-block">Mínimo / Máximo</span>
              <strong class="fs-6 text-info" id="st-ping-minmax">0 / 0 ms</strong>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="border rounded p-2 text-center bg-light">
              <span class="text-muted small d-block">Uptime (%)</span>
              <strong class="fs-6 text-primary" id="st-ping-uptime">0%</strong>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="border rounded p-2 text-center bg-light">
              <span class="text-muted small d-block">Pérdida de Paquetes</span>
              <strong class="fs-6 text-danger" id="st-ping-loss">0%</strong>
            </div>
          </div>
        </div>

        <div style="height: 300px; position: relative;">
          <canvas id="chartPingHistoryEquipo"></canvas>
        </div>
      </div>
      <div class="modal-footer py-2 bg-light">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
