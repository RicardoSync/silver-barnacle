<?php
// modal_editar.php - Modal para editar un Equipo existente
?>
<div class="modal fade" id="modalEditarEquipo" tabindex="-1" aria-labelledby="modalEditarEquipoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fs-6 fw-bold" id="modalEditarEquipoLabel">
          <i class="bi bi-pencil-square me-2"></i> Editar Equipo
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditarEquipo" autocomplete="off">
        <input type="hidden" id="edit-equipo-id" name="id">
        <div class="modal-body">
          <div class="mb-3">
            <label for="edit-equipo-nombre" class="form-label fw-semibold">Nombre del Equipo <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit-equipo-nombre" name="nombre" required>
          </div>
          <div class="mb-3">
            <label for="edit-equipo-ip" class="form-label fw-semibold">Dirección IP <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit-equipo-ip" name="ip_address" required>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit-equipo-usuario" class="form-label fw-semibold">Usuario</label>
              <input type="text" class="form-control" id="edit-equipo-usuario" name="usuario">
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit-equipo-password" class="form-label fw-semibold">Nueva Contraseña</label>
              <input type="password" class="form-control" id="edit-equipo-password" name="password" placeholder="(Sin cambios)">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="edit-equipo-comunidad" class="form-label fw-semibold">Comunidad SNMP</label>
              <input type="text" class="form-control" id="edit-equipo-comunidad" name="comunidad_snmp">
            </div>
            <div class="col-md-6 mb-3">
              <label for="edit-equipo-contacto" class="form-label fw-semibold">Contacto SNMP / Notas</label>
              <input type="text" class="form-control" id="edit-equipo-contacto" name="contacto_snmp">
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light py-2">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>
