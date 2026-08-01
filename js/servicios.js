let dtServicios = null;
let chartServicioInstance = null;
let timerServiciosAutoRefresh = null;

$(document).ready(function () {
    if ($('#tablaServicios').length) {
        initServiciosModule();
    }
});

function initServiciosModule() {
    cargarServicios();

    if (timerServiciosAutoRefresh) clearInterval(timerServiciosAutoRefresh);
    timerServiciosAutoRefresh = setInterval(function () {
        if ($('#tablaServicios').is(':visible')) {
            cargarServicios(false);
        }
    }, 20000);

    $('#formServicio').off('submit').on('submit', function (e) {
        e.preventDefault();
        guardarServicio();
    });
}

// Exportar funciones globales
window.initServiciosModule = initServiciosModule;
window.initModuloServicios = initServiciosModule;
window.cargarServicios = cargarServicios;
window.abrirModalCrearServicio = abrirModalCrearServicio;
window.abrirModalEditarServicio = abrirModalEditarServicio;
window.eliminarServicio = eliminarServicio;
window.verGraficaServicio = verGraficaServicio;
window.onTipoServicioChange = onTipoServicioChange;

function cargarServicios(mostrarLoader = true) {
    $.ajax({
        url: 'api.php?action=servicios_listar',
        type: 'GET',
        dataType: 'json',
        success: function (res) {
            if (res.status === 'success') {
                renderizarTablaServicios(res.data);
                actualizarKpisServicios(res.data);
            } else {
                console.error("Error al cargar servicios:", res.message);
            }
        },
        error: function (err) {
            console.error("Error AJAX servicios_listar:", err);
        }
    });
}

function renderizarTablaServicios(servicios) {
    let rows = '';

    servicios.forEach(s => {
        let badgeTipo = '';
        if (s.tipo === 'dns') {
            badgeTipo = '<span class="badge bg-info text-dark"><i class="bi bi-diagram-2 me-1"></i>DNS</span>';
        } else if (s.tipo === 'http') {
            badgeTipo = '<span class="badge bg-primary"><i class="bi bi-globe me-1"></i>HTTP(S)</span>';
        } else {
            badgeTipo = '<span class="badge bg-secondary"><i class="bi bi-ethernet me-1"></i>Puerto ' + (s.puerto || '') + '</span>';
        }

        let estadoCheck = s.estado_check || 'offline';
        let badgeEstado = '';
        if (parseInt(s.estado) === 0) {
            badgeEstado = '<span class="badge bg-secondary">Inactivo</span>';
        } else if (estadoCheck === 'online') {
            badgeEstado = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Online</span>';
        } else if (estadoCheck === 'lento') {
            badgeEstado = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Lento</span>';
        } else {
            badgeEstado = '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Offline</span>';
        }

        let latenciaText = s.ultimo_ms ? s.ultimo_ms + ' ms' : '--';
        let msClass = 'fw-bold ';
        if (!s.ultimo_ms || estadoCheck === 'offline') {
            msClass += 'text-muted';
        } else if (s.ultimo_ms > s.umbral_ms) {
            msClass += 'text-warning';
        } else {
            msClass += 'text-success';
        }

        let detalleExtra = '';
        if (s.tipo === 'http' && s.codigo_http) {
            let codeBg = (s.codigo_http >= 200 && s.codigo_http < 400) ? 'bg-success text-white' : 'bg-danger text-white';
            detalleExtra = `<span class="badge ${codeBg}">HTTP ${s.codigo_http}</span> `;
        }
        if (s.ip_resuelta) {
            detalleExtra += `<small class="text-muted"><i class="bi bi-hdd-network me-1"></i>${s.ip_resuelta}</small>`;
        }

        let fechaPrueba = s.ultima_verificacion ? s.ultima_verificacion : 'Sin registros';

        rows += `
            <tr>
                <td><strong>#${s.id}</strong></td>
                <td class="fw-bold text-dark">${s.nombre}</td>
                <td>${badgeTipo}</td>
                <td><code class="text-dark bg-light px-2 py-1 rounded">${s.target}</code></td>
                <td><span class="${msClass}">${latenciaText}</span> <small class="text-muted d-block" style="font-size:10px;">Max: ${s.umbral_ms}ms</small></td>
                <td>${detalleExtra || '<small class="text-muted">--</small>'}</td>
                <td>${badgeEstado}</td>
                <td><small class="text-muted">${fechaPrueba}</small></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-info me-1" title="Ver Histórico" onclick="verGraficaServicio(${s.id}, '${s.nombre.replace(/'/g, "\\'")}')">
                        <i class="bi bi-graph-up"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning me-1" title="Editar" onclick='abrirModalEditarServicio(${JSON.stringify(s)})'>
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="eliminarServicio(${s.id}, '${s.nombre.replace(/'/g, "\\'")}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    if ($.fn.DataTable.isDataTable('#tablaServicios')) {
        $('#tablaServicios').DataTable().destroy();
    }

    $('#tbody-servicios').html(rows);
    dtServicios = $('#tablaServicios').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        order: [[0, 'asc']]
    });
}

function actualizarKpisServicios(servicios) {
    let total = servicios.filter(s => parseInt(s.estado) === 1).length;
    let online = 0;
    let lento = 0;
    let offline = 0;

    servicios.forEach(s => {
        if (parseInt(s.estado) === 1) {
            let st = s.estado_check || 'offline';
            if (st === 'online') online++;
            else if (st === 'lento') lento++;
            else offline++;
        }
    });

    $('#kpi-total-servicios').text(total);
    $('#kpi-online-servicios').text(online);
    $('#kpi-lento-servicios').text(lento);
    $('#kpi-offline-servicios').text(offline);
}

function onTipoServicioChange() {
    let tipo = $('#servicio_tipo').val();
    if (tipo === 'puerto') {
        $('#servicio_puerto').prop('disabled', false).attr('required', true);
        if (!$('#servicio_puerto').val()) $('#servicio_puerto').val(80);
    } else {
        $('#servicio_puerto').prop('disabled', true).removeAttr('required').val('');
    }
}

function abrirModalCrearServicio() {
    $('#formServicio')[0].reset();
    $('#servicio_id').val('');
    $('#modalServicioHeader').removeClass('bg-dark').addClass('bg-primary');
    $('#modalServicioTitulo').html('<i class="bi bi-plus-circle me-1"></i> Registrar Servicio');
    onTipoServicioChange();
    let modal = new bootstrap.Modal(document.getElementById('modalServicio'));
    modal.show();
}

function abrirModalEditarServicio(servicio) {
    $('#servicio_id').val(servicio.id);
    $('#servicio_nombre').val(servicio.nombre);
    $('#servicio_tipo').val(servicio.tipo);
    $('#servicio_target').val(servicio.target);
    $('#servicio_puerto').val(servicio.puerto || '');
    $('#servicio_umbral').val(servicio.umbral_ms || 300);
    $('#servicio_estado').val(servicio.estado);

    $('#modalServicioHeader').removeClass('bg-primary').addClass('bg-dark');
    $('#modalServicioTitulo').html('<i class="bi bi-pencil-square me-1"></i> Editar Servicio');
    onTipoServicioChange();
    let modal = new bootstrap.Modal(document.getElementById('modalServicio'));
    modal.show();
}

function guardarServicio() {
    let formData = $('#formServicio').serialize();

    $.ajax({
        url: 'api.php?action=servicios_guardar',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function (res) {
            if (res.status === 'success') {
                Swal.fire('Éxito', res.message, 'success');
                let modalEl = document.getElementById('modalServicio');
                let modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
                cargarServicios();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        },
        error: function (err) {
            Swal.fire('Error', 'Error al comunicarse con el servidor.', 'error');
        }
    });
}

function eliminarServicio(id, nombre) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: `¿Deseas eliminar el servicio "${nombre}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'api.php?action=servicios_eliminar',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function (res) {
                    if (res.status === 'success') {
                        Swal.fire('¡Eliminado!', res.message, 'success');
                        cargarServicios();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        }
    });
}

function verGraficaServicio(id, nombre) {
    $('#modalGraficaTitulo').html('<i class="bi bi-graph-up me-1"></i> Histórico de Latencia: ' + nombre);

    let modal = new bootstrap.Modal(document.getElementById('modalGraficaServicio'));
    modal.show();

    $.ajax({
        url: 'api.php?action=servicios_historico',
        type: 'GET',
        data: { id: id, horas: 24 },
        dataType: 'json',
        success: function (res) {
            if (res.status === 'success') {
                renderizarGraficaLatencia(res.data);
            }
        }
    });
}

function renderizarGraficaLatencia(data) {
    let canvas = document.getElementById('chartServicioHistorico');
    if (!canvas) return;
    let ctx = canvas.getContext('2d');

    let labels = data.map(d => d.fecha);
    let valoresMs = data.map(d => d.ms);

    if (chartServicioInstance) {
        chartServicioInstance.destroy();
    }

    chartServicioInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Latencia (ms)',
                data: valoresMs,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    display: true,
                    title: { display: true, text: 'Fecha / Hora' }
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Milisegundos (ms)' }
                }
            }
        }
    });
}
