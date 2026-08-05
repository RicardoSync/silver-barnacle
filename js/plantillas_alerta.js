let tablaPlantillas;

function initPlantillasAlertaModule() {
    if ($.fn.DataTable.isDataTable('#tablaPlantillas')) {
        $('#tablaPlantillas').DataTable().destroy();
    }

    tablaPlantillas = $('#tablaPlantillas').DataTable({
        ajax: {
            url: 'controllers/PlantillaAlertaController.php?action=listar',
            dataSrc: 'data'
        },
        columns: [
            { data: 'id', width: '5%' },
            { data: 'nombre', width: '25%', className: 'fw-bold' },
            { 
                data: 'minutos', 
                width: '10%',
                render: function(data) {
                    if (parseInt(data) >= 60) {
                        let hrs = (parseInt(data) / 60).toFixed(1).replace('.0', '');
                        return `<span class="badge bg-danger">${data} min (~${hrs}h)</span>`;
                    }
                    return `<span class="badge bg-warning text-dark">${data} min</span>`;
                }
            },
            { 
                data: 'mensaje', 
                width: '45%',
                render: function(data) {
                    // Reemplazar saltos de línea para mostrar adecuadamente en la tabla
                    let text = data.replace(/\n/g, '<br>');
                    return `<div class="p-2 bg-light rounded text-muted font-monospace" style="font-size: 0.8rem; white-space: normal; max-height: 120px; overflow-y: auto;">${text}</div>`;
                }
            },
            { 
                data: null, 
                width: '15%',
                className: 'text-end',
                render: function (data, type, row) {
                    return `
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editarPlantilla(${row.id})" title="Editar"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarPlantilla(${row.id})" title="Eliminar"><i class="bi bi-trash"></i></button>
                    `;
                }
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
        }
    });

    $('#formPlantilla').off('submit').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        const originalText = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm"></span> Guardando...').prop('disabled', true);

        $.ajax({
            url: 'controllers/PlantillaAlertaController.php?action=guardar',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    $('#modalPlantilla').modal('hide');
                    tablaPlantillas.ajax.reload();
                    Swal.fire('Éxito', response.message, 'success');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            complete: function() {
                btn.html(originalText).prop('disabled', false);
            }
        });
    });
}

window.openModalNuevaPlantilla = function() {
    $('#formPlantilla')[0].reset();
    $('#plantilla_id').val('');
    $('#modalPlantillaTitle').html('<i class="bi bi-chat-right-text-fill"></i> Registrar Plantilla');
    $('#modalPlantilla').modal('show');
};

window.editarPlantilla = function(id) {
    $.ajax({
        url: 'controllers/PlantillaAlertaController.php?action=obtener&id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#plantilla_id').val(data.id);
            $('#plantilla_nombre').val(data.nombre);
            $('#plantilla_minutos').val(data.minutos);
            $('#plantilla_mensaje').val(data.mensaje);
            $('#modalPlantillaTitle').html('<i class="bi bi-pencil-square"></i> Editar Plantilla');
            $('#modalPlantilla').modal('show');
        }
    });
};

window.eliminarPlantilla = function(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¡Los equipos caídos ya no se notificarán con esta plantilla!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'controllers/PlantillaAlertaController.php?action=eliminar',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        tablaPlantillas.ajax.reload();
                        Swal.fire('Eliminado', response.message, 'success');
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }
    });
};
