let tablaMikrotiks;

function initMikrotikModule() {
    // Inicializar DataTable
    if ($.fn.DataTable.isDataTable('#tablaMikrotiks')) {
        $('#tablaMikrotiks').DataTable().destroy();
    }
    
    tablaMikrotiks = $('#tablaMikrotiks').DataTable({
        "ajax": {
            "url": "controllers/MikrotikController.php?action=listar",
            "type": "GET",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "id" },
            { "data": "nombre" },
            { "data": "ip_address" },
            { "data": "puerto_api" },
            { "data": "estado" },
            { "data": "conexion" },
            { "data": "acciones" }
        ],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        },
        "drawCallback": function(settings) {
            // Executed every time the table is drawn
            document.querySelectorAll('.status-check[data-status="pending"]').forEach(span => {
                let id = span.getAttribute('data-id');
                span.setAttribute('data-status', 'checking');
                
                fetch('controllers/MikrotikController.php?action=ping&id=' + id)
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'online') {
                        span.className = 'badge bg-success';
                        span.innerHTML = '<i class="bi bi-wifi"></i> Online';
                        span.setAttribute('data-status', 'done');
                    } else {
                        span.className = 'badge bg-danger';
                        span.innerHTML = '<i class="bi bi-wifi-off"></i> Offline';
                        span.setAttribute('data-status', 'done');
                        span.setAttribute('title', data.error);
                    }
                })
                .catch(err => {
                    span.className = 'badge bg-danger';
                    span.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Error';
                    span.setAttribute('data-status', 'done');
                });
            });
        }
    });

    // Envío del formulario de Nuevo
    const formNuevo = document.getElementById('formNuevoMikrotik');
    if (formNuevo) {
        // Remover listener anterior si existe
        const newFormNuevo = formNuevo.cloneNode(true);
        formNuevo.parentNode.replaceChild(newFormNuevo, formNuevo);
        
        newFormNuevo.addEventListener('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            
            fetch('controllers/MikrotikController.php?action=guardar', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire('Éxito', data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modalNuevoMikrotik')).hide();
                    newFormNuevo.reset();
                    tablaMikrotiks.ajax.reload();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });
    }

    // Envío del formulario de Editar
    const formEditar = document.getElementById('formEditarMikrotik');
    if (formEditar) {
        // Remover listener anterior si existe
        const newFormEditar = formEditar.cloneNode(true);
        formEditar.parentNode.replaceChild(newFormEditar, formEditar);
        
        newFormEditar.addEventListener('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            
            fetch('controllers/MikrotikController.php?action=guardar', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire('Éxito', data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modalEditarMikrotik')).hide();
                    newFormEditar.reset();
                    tablaMikrotiks.ajax.reload();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });
    }
}

// Funciones globales invocadas desde los botones de la tabla
window.editarMikrotik = function(id) {
    fetch('controllers/MikrotikController.php?action=obtener&id=' + id)
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            document.getElementById('e_id').value = data.data.id;
            document.getElementById('e_nombre').value = data.data.nombre;
            document.getElementById('e_ip_address').value = data.data.ip_address;
            document.getElementById('e_puerto_api').value = data.data.puerto_api;
            document.getElementById('e_usuario').value = data.data.usuario;
            document.getElementById('e_password').value = ''; // Por seguridad no prellenar, pero el dto lo maneja si se deja en blanco
            document.getElementById('e_latitud').value = data.data.latitud || '';
            document.getElementById('e_longitud').value = data.data.longitud || '';
            
            let modal = new bootstrap.Modal(document.getElementById('modalEditarMikrotik'));
            modal.show();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
};

window.eliminarMikrotik = function(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Se cambiará el estado a inactivo (borrado lógico).",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            let formData = new FormData();
            formData.append('id', id);
            
            fetch('controllers/MikrotikController.php?action=eliminar', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire('Eliminado', data.message, 'success');
                    tablaMikrotiks.ajax.reload();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
};
