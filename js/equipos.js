let tableEquipos;

function initEquiposModule() {
    loadEquipos();

    $('#formNuevoEquipo').submit(function(e) {
        e.preventDefault();
        saveEquipo(this, '#modalNuevoEquipo');
    });

    $('#formEditarEquipo').submit(function(e) {
        e.preventDefault();
        saveEquipo(this, '#modalEditarEquipo');
    });
}

function loadEquipos() {
    if ($.fn.DataTable.isDataTable('#tablaEquipos')) {
        $('#tablaEquipos').DataTable().destroy();
    }
    
    tableEquipos = $('#tablaEquipos').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        ajax: {
            url: 'controllers/EquipoController.php?action=listar',
            type: 'GET'
        },
        columns: [
            { data: 'id' },
            { data: 'nombre' },
            { data: 'ip_address' },
            { data: 'comunidad_snmp' },
            { data: 'contacto_snmp' },
            { data: 'estado' },
            { 
                data: 'id',
                className: 'text-end',
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-sm btn-outline-info shadow-sm me-1" onclick="loadView('equipos/detalles', { id: ${data} })" title="Ver Detalles">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary shadow-sm me-1" onclick="editarEquipo(${data})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger shadow-sm" onclick="eliminarEquipo(${data})">
                            <i class="bi bi-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        drawCallback: function(settings) {
            document.querySelectorAll('.status-check-equipo[data-status="pending"]').forEach(span => {
                let id = span.getAttribute('data-id');
                span.setAttribute('data-status', 'checking');
                
                fetch('controllers/EquipoController.php?action=ping&id=' + id)
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
}

function openModalNuevoEquipo() {
    $('#formNuevoEquipo')[0].reset();
    $('#modalNuevoEquipo').modal('show');
}

function editarEquipo(id) {
    $('#formEditarEquipo')[0].reset();
    fetch('controllers/EquipoController.php?action=obtener&id=' + id)
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            $('#edit-equipo-id').val(data.data.id);
            $('#edit-equipo-nombre').val(data.data.nombre);
            $('#edit-equipo-ip').val(data.data.ip_address);
            $('#edit-equipo-usuario').val(data.data.usuario);
            $('#edit-equipo-password').val('');
            $('#edit-equipo-comunidad').val(data.data.comunidad_snmp);
            $('#edit-equipo-contacto').val(data.data.contacto_snmp);
            $('#modalEditarEquipo').modal('show');
        } else {
            Swal.fire('Error', data.message || 'No se pudo obtener el equipo', 'error');
        }
    }).catch(e => {
        Swal.fire('Error', 'Fallo al obtener datos del equipo', 'error');
    });
}

function saveEquipo(form, modalId) {
    const formData = new FormData(form);
    const btn = $(form).find('button[type="submit"]');
    const originalText = btn.html();
    btn.html('<span class="spinner-border spinner-border-sm"></span> Guardando...').prop('disabled', true);
    
    fetch('controllers/EquipoController.php?action=guardar', {
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
            tableEquipos.ajax.reload();
        } else {
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

function eliminarEquipo(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "El equipo será desactivado del sistema (borrado lógico).",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id', id);
            
            fetch('controllers/EquipoController.php?action=eliminar', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('¡Eliminado!', 'El equipo ha sido desactivado.', 'success');
                    tableEquipos.ajax.reload();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            }).catch(e => {
                Swal.fire('Error', 'Error al procesar la solicitud de eliminación.', 'error');
            });
        }
    });
}
