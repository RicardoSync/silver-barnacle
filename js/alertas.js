let tableAlertas;
let pollingAlertas;

// Polling global para la campana
function startAlertsPolling() {
    checkNotificaciones();
    if (pollingAlertas) clearInterval(pollingAlertas);
    pollingAlertas = setInterval(checkNotificaciones, 30000); // 30s
}

function checkNotificaciones() {
    fetch('controllers/NotificacionController.php?action=campana')
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            const badge = document.getElementById('bell-badge');
            const items = document.getElementById('bell-items');
            
            if (data.total > 0) {
                badge.style.display = 'block';
                badge.innerText = data.total > 9 ? '+9' : data.total;
                
                let html = '';
                data.data.forEach(a => {
                    let badgeColor = 'danger';
                    if(a.tipo === 'cpu' || a.tipo === 'latencia') badgeColor = 'warning';
                    if(a.tipo === 'log_mikrotik') badgeColor = 'secondary';

                    html += `
                        <li><a class="dropdown-item py-2 border-bottom" href="#" onclick="marcarLeida(${a.id})">
                            <div class="d-flex justify-content-between">
                                <div class="small fw-bold text-${badgeColor}">${a.router}</div>
                                <div class="text-end text-black-50" style="font-size: 10px;">${a.fecha_registro}</div>
                            </div>
                            <div class="small text-wrap text-muted mt-1" style="width: 280px; font-size: 11px;">${a.mensaje}</div>
                        </a></li>
                    `;
                });
                items.innerHTML = html;
            } else {
                badge.style.display = 'none';
                items.innerHTML = '<li><a class="dropdown-item text-muted text-center py-3" href="#">No hay notificaciones nuevas</a></li>';
            }
        }
    });
}

function initAlertasModule() {
    if ($.fn.DataTable.isDataTable('#tablaAlertas')) {
        $('#tablaAlertas').DataTable().destroy();
    }
    
    tableAlertas = $('#tablaAlertas').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        ajax: {
            url: 'controllers/NotificacionController.php?action=listar',
            type: 'GET'
        },
        columns: [
            { data: 'fecha_registro' },
            { 
                data: 'tipo',
                render: function(data) {
                    const map = {
                        'offline': '<span class="badge bg-danger">Caída</span>',
                        'cpu': '<span class="badge bg-warning text-dark">CPU</span>',
                        'latencia': '<span class="badge bg-warning text-dark">Latencia</span>',
                        'log_mikrotik': '<span class="badge bg-secondary">Log</span>'
                    };
                    return map[data] || data;
                }
            },
            { data: 'router', className: 'fw-bold text-primary' },
            { data: 'mensaje' },
            { 
                data: 'estado',
                render: function(data) {
                    return data === 'no_leido' ? '<span class="badge bg-danger">Nueva</span>' : '<span class="badge bg-light text-dark border">Leída</span>';
                }
            },
            { 
                data: 'id',
                className: 'text-end',
                render: function(data, type, row) {
                    if (row.estado === 'no_leido') {
                        return `<button class="btn btn-sm btn-outline-success" onclick="marcarLeida(${data}, true)"><i class="bi bi-check2"></i> Marcar</button>`;
                    }
                    return '';
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25
    });
}

window.marcarLeida = function(id, reloadTable = false) {
    const formData = new FormData();
    formData.append('id', id);
    fetch('controllers/NotificacionController.php?action=marcar_leida', {
        method: 'POST', body: formData
    }).then(() => {
        checkNotificaciones();
        if(reloadTable && tableAlertas) tableAlertas.ajax.reload(null, false);
    });
}

window.marcarTodasLeidas = function(reloadTable = false) {
    fetch('controllers/NotificacionController.php?action=marcar_todas')
    .then(() => {
        checkNotificaciones();
        if(reloadTable && tableAlertas) tableAlertas.ajax.reload(null, false);
    });
}

// Iniciar polling al cargar la página
startAlertsPolling();
