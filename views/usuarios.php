<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-people text-secondary me-2"></i> Gestión de Usuarios</h2>
    <button class="btn btn-primary shadow-sm" onclick="openModalNuevoUsuario()">
        <i class="bi bi-person-plus-fill me-1"></i> Nuevo Usuario
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tablaUsuarios" class="table table-hover table-striped w-100 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Fecha de Registro</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nuevo Usuario -->
<div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <form id="formNuevoUsuario" class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus"></i> Registrar Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre Completo</label>
                    <input type="text" class="form-control" name="nombre" required placeholder="Ej. Ricardo Escobedo">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Correo Electrónico</label>
                    <input type="email" class="form-control" name="correo" required placeholder="ricardo2003@wisp.com">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Contraseña</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Rol en el Sistema</label>
                    <select class="form-select" name="rol" required>
                        <option value="tecnico">Técnico</option>
                        <option value="administrador">Administrador</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Usuario</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <form id="formEditarUsuario" class="modal-content border-0 shadow">
            <input type="hidden" name="id" id="edit-user-id">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Editar Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre Completo</label>
                    <input type="text" class="form-control" name="nombre" id="edit-nombre" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Correo Electrónico</label>
                    <input type="email" class="form-control" name="correo" id="edit-correo" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Nueva Contraseña</label>
                    <input type="password" class="form-control" name="password" id="edit-password" placeholder="***">
                    <div class="form-text text-muted"><i class="bi bi-info-circle"></i> Déjalo en blanco si no deseas cambiar la contraseña actual.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Rol en el Sistema</label>
                    <select class="form-select" name="rol" id="edit-rol" required>
                        <option value="tecnico">Técnico</option>
                        <option value="administrador">Administrador</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-dark"><i class="bi bi-arrow-repeat"></i> Actualizar Usuario</button>
            </div>
        </form>
    </div>
</div>
