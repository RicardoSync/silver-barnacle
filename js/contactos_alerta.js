let tablaContactos;

function initContactosAlertaModule() {
    if ($.fn.DataTable.isDataTable('#tablaContactos')) {
        $('#tablaContactos').DataTable().destroy();
    }

    tablaContactos = $('#tablaContactos').DataTable({
        ajax: {
            url: 'controllers/ContactoAlertaController.php?action=listar',
            dataSrc: 'data'
        },
        columns: [
            { data: 'id' },
            { data: 'nombre' },
            { data: 'telefono' },
            { 
                data: null, 
                className: 'text-end',
                render: function (data, type, row) {
                    return `
                        <button class="btn btn-sm btn-outline-primary" onclick="editarContacto(${row.id})"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarContacto(${row.id})"><i class="bi bi-trash"></i></button>
                    `;
                }
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
        }
    });

    $('#formContacto').off('submit').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        const originalText = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm"></span> Guardando...').prop('disabled', true);

        $.ajax({
            url: 'controllers/ContactoAlertaController.php?action=guardar',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    $('#modalContacto').modal('hide');
                    tablaContactos.ajax.reload();
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

window.openModalNuevoContacto = function() {
    $('#formContacto')[0].reset();
    $('#contacto_id').val('');
    $('#modalContactoTitle').html('<i class="bi bi-person-lines-fill"></i> Registrar Contacto');
    $('#modalContacto').modal('show');
};

window.editarContacto = function(id) {
    $.ajax({
        url: 'controllers/ContactoAlertaController.php?action=obtener&id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#contacto_id').val(data.id);
            $('#contacto_nombre').val(data.nombre);
            $('#contacto_telefono').val(data.telefono);
            $('#modalContactoTitle').html('<i class="bi bi-pencil-square"></i> Editar Contacto');
            $('#modalContacto').modal('show');
        }
    });
};

window.eliminarContacto = function(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¡El contacto ya no recibirá alertas de WhatsApp!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'controllers/ContactoAlertaController.php?action=eliminar',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        tablaContactos.ajax.reload();
                        Swal.fire('Eliminado', response.message, 'success');
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }
    });
};
