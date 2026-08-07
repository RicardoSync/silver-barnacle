<!-- Modal para Histórico de Latencia del Servicio -->
<div class="modal fade" id="modalGraficaServicio" tabindex="-1" aria-labelledby="modalGraficaServicioLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-info text-dark py-2">
        <h5 class="modal-title fs-6 fw-bold" id="modalGraficaTitulo">
          <i class="bi bi-graph-up me-2"></i> Histórico de Latencia
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div style="height: 350px; position: relative;">
          <canvas id="chartServicioHistorico"></canvas>
        </div>
      </div>
      <div class="modal-footer py-2 bg-light">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
