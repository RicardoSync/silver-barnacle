let tableEquipos;
let chartPingLiveEquipo = null;
let chartPingHistoryEquipo = null;
let pingEquipoLiveInterval = null;
let livePingSeriesData = [];
const MAX_LIVE_PING_POINTS = 30;

function initEquiposModule() {
    loadEquipos();

    $('#formNuevoEquipo').off('submit').on('submit', function(e) {
        e.preventDefault();
        saveEquipo(this, '#modalNuevoEquipo');
    });

    $('#formEditarEquipo').off('submit').on('submit', function(e) {
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
                data: null,
                className: 'text-end',
                render: function(data, type, row) {
                    const escName = escapeHtml(row.nombre);
                    const escIp = escapeHtml(row.ip_address);
                    return `
                        <button class="btn btn-sm btn-outline-success shadow-sm me-1" onclick="abrirModalPingEquipo(${row.id}, '${escName}', '${escIp}')" title="Ping en Vivo">
                            <i class="bi bi-activity"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-info shadow-sm me-1" onclick="abrirModalEstadisticasEquipo(${row.id}, '${escName}')" title="Historial de Estadísticas">
                            <i class="bi bi-graph-up"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary shadow-sm me-1" onclick="editarEquipo(${row.id})" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger shadow-sm" onclick="eliminarEquipo(${row.id})" title="Eliminar">
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
                        span.innerHTML = `<i class="bi bi-wifi"></i> Online (${data.ms} ms)`;
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

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/'/g, "\\'").replace(/"/g, "&quot;");
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
            if (tableEquipos) tableEquipos.ajax.reload();
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
                    if (tableEquipos) tableEquipos.ajax.reload();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            }).catch(e => {
                Swal.fire('Error', 'Error al procesar la solicitud de eliminación.', 'error');
            });
        }
    });
}

// -------------------------------------------------------------
// PING EN VIVO (modal_ping.php)
// -------------------------------------------------------------
window.abrirModalPingEquipo = function(id, nombre, ip) {
    $('#ping-equipo-nombre').text(nombre);
    $('#ping-equipo-ip').text(ip);
    $('#ping-equipo-latency-text').text('Calculando...');

    if (chartPingLiveEquipo) {
        chartPingLiveEquipo.destroy();
        chartPingLiveEquipo = null;
    }

    const ctx = document.getElementById('chartPingLiveEquipo').getContext('2d');
    chartPingLiveEquipo = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Ping Servidor -> Equipo (ms)',
                data: [],
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.15)',
                fill: true,
                tension: 0.3,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: { beginAtZero: true, title: { display: true, text: 'Milisegundos (ms)' } }
            }
        }
    });

    $('#modalPingEquipo').modal('show');

    if (pingEquipoLiveInterval) clearInterval(pingEquipoLiveInterval);
    
    const updatePingLive = () => {
        fetch('controllers/EquipoController.php?action=api_ping_server&id=' + id)
            .then(res => res.json())
            .then(data => {
                const timeLabel = new Date().toLocaleTimeString('es-MX', { hour12: false });
                let ms = data.status === 'success' ? (data.ms || 0) : 0;
                
                $('#ping-equipo-latency-text').text(ms + ' ms');

                if (chartPingLiveEquipo) {
                    if (chartPingLiveEquipo.data.labels.length >= MAX_LIVE_PING_POINTS) {
                        chartPingLiveEquipo.data.labels.shift();
                        chartPingLiveEquipo.data.datasets[0].data.shift();
                    }
                    chartPingLiveEquipo.data.labels.push(timeLabel);
                    chartPingLiveEquipo.data.datasets[0].data.push(ms);
                    chartPingLiveEquipo.update();
                }
            })
            .catch(e => console.error('Error fetching live ping equipo:', e));
    };

    updatePingLive();
    pingEquipoLiveInterval = setInterval(updatePingLive, 1000);
};

window.stopPingEquipoLive = function() {
    if (pingEquipoLiveInterval) {
        clearInterval(pingEquipoLiveInterval);
        pingEquipoLiveInterval = null;
    }
};

// -------------------------------------------------------------
// HISTORIAL DE ESTADÍSTICAS (modal_estadisticas.php)
// -------------------------------------------------------------
window.abrirModalEstadisticasEquipo = function(id, nombre) {
    $('#st-equipo-nombre').text(nombre);
    $('#st-equipo-id-current').val(id);
    $('#selectFilterPingHoras').val('4'); // Por defecto 4 horas

    $('#modalEstadisticasEquipo').modal('show');
    cargarEstadisticasPingEquipoActual();
};

window.cargarEstadisticasPingEquipoActual = function() {
    const id = $('#st-equipo-id-current').val();
    const horas = $('#selectFilterPingHoras').val();
    if (!id) return;

    fetch(`controllers/AnaliticasController.php?action=getPingEquipo&equipo_id=${id}&horas=${horas}`)
        .then(res => res.json())
        .then(res => {
            if (!res || !Array.isArray(res)) res = [];

            // Calcular KPIs
            let totalMs = 0;
            let validCount = 0;
            let offlineCount = 0;
            let minMs = Infinity;
            let maxMs = -1;

            res.forEach(item => {
                const ms = parseInt(item.ms);
                if (ms > 0) {
                    totalMs += ms;
                    validCount++;
                    if (ms < minMs) minMs = ms;
                    if (ms > maxMs) maxMs = ms;
                } else {
                    offlineCount++;
                }
            });

            const totalProbes = res.length;
            const avgMs = validCount > 0 ? Math.round(totalMs / validCount) : 0;
            const uptimePercent = totalProbes > 0 ? (Math.round((validCount / totalProbes) * 1000) / 10) : 0;
            const lossPercent = totalProbes > 0 ? (Math.round((offlineCount / totalProbes) * 1000) / 10) : 0;

            $('#st-ping-avg').text(`${avgMs} ms`);
            $('#st-ping-minmax').text(`Min: ${minMs === Infinity ? 0 : minMs} / Max: ${maxMs === -1 ? 0 : maxMs} ms`);
            $('#st-ping-uptime').text(`${uptimePercent}%`);
            $('#st-ping-loss').text(`${lossPercent}%`);
            $('#st-equipo-probes').text(`Muestras BD: ${totalProbes}`);

            // Destruir gráfica previa si existe
            if (chartPingHistoryEquipo) {
                chartPingHistoryEquipo.destroy();
                chartPingHistoryEquipo = null;
            }

            const msData = res.map(item => ({ x: item.fecha_registro, y: item.ms }));

            const ctx = document.getElementById('chartPingHistoryEquipo').getContext('2d');
            chartPingHistoryEquipo = new Chart(ctx, {
                type: 'line',
                data: {
                    datasets: [{
                        label: 'Latencia Ping (ms)',
                        data: msData,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        fill: true,
                        tension: 0.1,
                        segment: {
                            borderColor: ctx => (ctx.p0.parsed.y < 0 || ctx.p1.parsed.y < 0) ? '#dc3545' : '#0d6efd'
                        },
                        pointBackgroundColor: ctx => {
                            let y = ctx.raw ? ctx.raw.y : (ctx.parsed ? ctx.parsed.y : null);
                            return (y !== null && y < 0) ? '#dc3545' : '#0d6efd';
                        },
                        pointRadius: ctx => {
                            let y = ctx.raw ? ctx.raw.y : (ctx.parsed ? ctx.parsed.y : null);
                            return (y !== null && y < 0) ? 3 : 0;
                        }
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    scales: {
                        x: { type: 'time', time: { tooltipFormat: 'dd MMM yyyy HH:mm' } },
                        y: { beginAtZero: true, title: { display: true, text: 'Milisegundos (ms)' } }
                    }
                }
            });
        })
        .catch(err => console.error('Error al cargar historial de ping equipo:', err));
};

window.initEquiposModule = initEquiposModule;
