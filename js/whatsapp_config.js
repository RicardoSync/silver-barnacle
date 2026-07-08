function initWhatsappConfigModule() {
    cargarConfiguracion();

    // Eliminar el event listener anterior si existe (para evitar doble submit si se navega de un lado a otro)
    $('#formWhatsappConfig').off('submit').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        const originalText = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...').prop('disabled', true);

        $.ajax({
            url: 'controllers/WhatsappConfigController.php?action=guardar',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    Swal.fire('Guardado', response.message, 'success');
                } else {
                    Swal.fire('Error', response.message || 'Error desconocido', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
            },
            complete: function() {
                btn.html(originalText).prop('disabled', false);
            }
        });
    });
}

function cargarConfiguracion() {
    $.ajax({
        url: 'controllers/WhatsappConfigController.php?action=obtener',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success' && response.data) {
                const data = response.data;
                $('#waha_url').val(data.waha_url);
                $('#waha_api_key').val(data.waha_api_key);
                $('#url_sistema').val(data.url_sistema);
                $('#api_secret').val(data.api_secret);
                
                if (data.enlaces_publicos_activos == '1') {
                    $('#enlaces_publicos_activos').prop('checked', true);
                } else {
                    $('#enlaces_publicos_activos').prop('checked', false);
                }
            }
        }
    });
}
