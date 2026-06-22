let tableUsuarios;

function initUsuariosModule() {
    loadUsuarios();

    $('#formNuevoUsuario').submit(function(e) {
        e.preventDefault();
        saveUsuario(this, '#modalNuevoUsuario');
    });

    $('#formEditarUsuario').submit(function(e) {
        e.preventDefault();
        saveUsuario(this, '#modalEditarUsuario');
    });
}

function loadUsuarios() {
    if ($.fn.DataTable.isDataTable('#tablaUsuarios')) {
        $('#tablaUsuarios').DataTable().destroy();
    }
    
    tableUsuarios = $('#tablaUsuarios').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        ajax: {
            url: 'controllers/UsuarioController.php?action=listar',
            type: 'GET'
        },
        columns: [
            { data: 'id' },
            { data: 'nombre' },
            { data: 'correo' },
            { 
                data: 'rol',
                render: function(data) {
                    const color = data === 'administrador' ? 'danger' : 'info';
                    return `<span class="badge bg-${color}">${data.toUpperCase()}</span>`;
                }
            },
            { data: 'created_at' },
            { 
                data: 'id',
                className: 'text-end',
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-sm btn-outline-primary shadow-sm me-1" onclick="openModalEditarUsuario(${data})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger shadow-sm" onclick="deleteUsuario(${data})">
                            <i class="bi bi-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        order: [[0, 'desc']]
    });
}

function openModalNuevoUsuario() {
    $('#formNuevoUsuario')[0].reset();
    $('#modalNuevoUsuario').modal('show');
}

function openModalEditarUsuario(id) {
    $('#formEditarUsuario')[0].reset();
    fetch('controllers/UsuarioController.php?action=obtener&id=' + id)
    .then(res => res.json())
    .then(data => {
        if(!data.error) {
            $('#edit-user-id').val(data.id);
            $('#edit-nombre').val(data.nombre);
            $('#edit-correo').val(data.correo);
            $('#edit-rol').val(data.rol);
            $('#modalEditarUsuario').modal('show');
        } else {
            Swal.fire('Error', data.error, 'error');
        }
    });
}

function saveUsuario(form, modalId) {
    const formData = new FormData(form);
    
    // UI Loading state
    const btn = $(form).find('button[type="submit"]');
    const originalText = btn.html();
    btn.html('<span class="spinner-border spinner-border-sm"></span> Guardando...').prop('disabled', true);
    
    fetch('controllers/UsuarioController.php?action=guardar', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.html(originalText).prop('disabled', false);
        
        if (data.status === 'success') {
            $(modalId).modal('hide');
            Swal.fire({
                icon: 'success',
                title: '¡Excelente!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            });
            tableUsuarios.ajax.reload();
        } else {
            // Mostrar error (ej: Este correo esta en uso)
            Swal.fire({
                icon: 'error',
                title: 'No se pudo guardar',
                text: data.message
            });
        }
    }).catch(e => {
        btn.html(originalText).prop('disabled', false);
        Swal.fire('Error', 'Error de red o de servidor al intentar guardar', 'error');
    });
}

function deleteUsuario(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer. Se eliminará el acceso de este usuario al sistema.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar usuario',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id', id);
            
            fetch('controllers/UsuarioController.php?action=eliminar', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('¡Eliminado!', 'El usuario ha sido eliminado permanentemente.', 'success');
                    tableUsuarios.ajax.reload();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}
