<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-hdd-network text-secondary me-2"></i> Gestión de Equipos (SNMP)</h2>
    <button class="btn btn-primary shadow-sm" onclick="openModalNuevoEquipo()">
        <i class="bi bi-plus-circle me-1"></i> Nuevo Equipo
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaEquipos" class="table table-hover table-striped w-100 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>IP Address</th>
                        <th>Comunidad SNMP</th>
                        <th>Contacto SNMP</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<?php 
require_once 'modal_crear.php';
require_once 'modal_editar.php';
?>
