<?php
// modal_crear.php - Modal para agregar un nuevo Equipo
?>
<div class="modal fade" id="modalNuevoEquipo" tabindex="-1" aria-labelledby="modalNuevoEquipoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fs-6 fw-bold" id="modalNuevoEquipoLabel">
          <i class="bi bi-hdd-network me-2"></i> Registrar Nuevo Equipo
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formNuevoEquipo" autocomplete="off">
        <div class="modal-body">
          <div class="mb-3">
            <label for="nombre" class="form-label fw-semibold">Nombre del Equipo <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="ej. Antena Sectorial 01" required>
          </div>
          <div class="mb-3">
            <label for="ip_address" class="form-label fw-semibold">Dirección IP <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="ip_address" name="ip_address" placeholder="ej. 192.168.1.50" required>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="usuario" class="form-label fw-semibold">Usuario</label>
              <input type="text" class="form-control" id="usuario" name="usuario" placeholder="ubnt / admin">
            </div>
            <div class="col-md-6 mb-3">
              <label for="password" class="form-label fw-semibold">Contraseña</label>
              <input type="password" class="form-control" id="password" name="password" placeholder="••••••••">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="comunidad_snmp" class="form-label fw-semibold">Comunidad SNMP</label>
              <input type="text" class="form-control" id="comunidad_snmp" name="comunidad_snmp" value="public">
            </div>
            <div class="col-md-6 mb-3">
              <label for="contacto_snmp" class="form-label fw-semibold">Contacto SNMP / Notas</label>
              <input type="text" class="form-control" id="contacto_snmp" name="contacto_snmp" placeholder="Soporte WISP">
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light py-2">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Guardar Equipo</button>
        </div>
      </form>
    </div>
  </div>
</div>
