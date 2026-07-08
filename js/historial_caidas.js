let chartCaidas = null;
let tablaHistorial = null;

function initHistorialCaidasModule() {
    cargarKPIs();
    cargarGrafica(7);
    inicializarTabla();

    $('#selectDiasGrafica').on('change', function() {
        cargarGrafica($(this).val());
    });
}

function cargarKPIs() {
    $.ajax({
        url: 'controllers/HistorialCaidaController.php?action=kpis',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#kpiActivas').text(data.activas);
            $('#kpiHoy').text(data.hoy);
            $('#kpiPromedio').text(data.promedio_minutos);
        }
    });
}

function cargarGrafica(dias) {
    $.ajax({
        url: 'controllers/HistorialCaidaController.php?action=grafica&dias=' + dias,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            renderGrafica(data.labels, data.data, data.nodos);
        }
    });
}

const caidasChartTimeScale = {
    type: 'time',
    time: {
        tooltipFormat: 'yyyy-MM-dd HH:mm',
        displayFormats: { 
            hour: 'HH:mm', 
            minute: 'HH:mm',
            day: 'MMM d'
        }
    }
};

const caidasChartZoomOptions = {
    pan: { enabled: true, mode: 'x' },
    zoom: { wheel: { enabled: true }, drag: { enabled: true }, pinch: { enabled: true }, mode: 'x' }
};

function renderGrafica(labels, data, nodos) {
    const ctx = document.getElementById('graficaCaidas').getContext('2d');
    
    if (chartCaidas) {
        chartCaidas.destroy();
    }

    chartCaidas = new Chart(ctx, {
        type: 'bar', // Barra para representar la duración de cada evento discreto
        data: {
            labels: labels,
            datasets: [{
                label: 'Duración (min)',
                data: data,
                backgroundColor: 'rgba(220, 53, 69, 0.8)',
                borderColor: '#dc3545',
                borderWidth: 1,
                barThickness: 6 // Barras delgadas
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                zoom: caidasChartZoomOptions,
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            // Mostrar el nombre del nodo en el tooltip
                            return 'Nodo: ' + nodos[context.dataIndex];
                        }
                    }
                }
            },
            scales: {
                x: caidasChartTimeScale,
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Minutos' },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                }
            }
        }
    });
}

function renderizarGrafica(labels, data) {
    // Usada como proxy para mantener la compatibilidad del signature si se llamó externamente,
    // aunque la llamada en cargarGrafica la cambié a pasar los nodos.
}

let hcChartFullScreenInstance = null;

window.openHistorialChartFullScreen = function() {
    if(!chartCaidas) return;
    
    if(hcChartFullScreenInstance) {
        hcChartFullScreenInstance.destroy();
    }

    const ctx = document.getElementById('chartFullScreenCanvas').getContext('2d');
    
    let newOptions = Object.assign({}, chartCaidas.config.options);
    newOptions.responsive = true;
    newOptions.maintainAspectRatio = false;
    
    let newData = {
        labels: chartCaidas.data.labels.slice(),
        datasets: chartCaidas.data.datasets.map(ds => ({
            label: ds.label,
            data: ds.data.slice(),
            borderColor: ds.borderColor,
            backgroundColor: ds.backgroundColor,
            borderWidth: ds.borderWidth,
            barThickness: 10 // Un poco más gruesas en full screen
        }))
    };
    
    hcChartFullScreenInstance = new Chart(ctx, {
        type: chartCaidas.config.type,
        data: newData,
        options: newOptions
    });

    let modal = new bootstrap.Modal(document.getElementById('modalChartFullScreen'));
    modal.show();
}

window.closeHistorialChartFullScreen = function() {
    if(hcChartFullScreenInstance) {
        hcChartFullScreenInstance.destroy();
        hcChartFullScreenInstance = null;
    }
}

window.zoomHistorialFullScreenChart = function(range) {
    if(!hcChartFullScreenInstance) return;
    
    let labels = hcChartFullScreenInstance.data.labels;
    if(!labels || labels.length === 0) return;
    
    let lastDate = new Date(); // Asumimos 'ahora' para monitoreo
    let pastDate = new Date();
    
    if (range === '10m') pastDate.setMinutes(pastDate.getMinutes() - 10);
    else if (range === '1h') pastDate.setHours(pastDate.getHours() - 1);
    else if (range === '3h') pastDate.setHours(pastDate.getHours() - 3);
    else if (range === '24h') pastDate.setHours(pastDate.getHours() - 24);
    else if (range === '1w') pastDate.setDate(pastDate.getDate() - 7);
    
    hcChartFullScreenInstance.options.scales.x.min = pastDate.getTime();
    hcChartFullScreenInstance.options.scales.x.max = lastDate.getTime();
    hcChartFullScreenInstance.update();
}

window.resetHistorialFullScreenChartZoom = function() {
    if(!hcChartFullScreenInstance) return;
    delete hcChartFullScreenInstance.options.scales.x.min;
    delete hcChartFullScreenInstance.options.scales.x.max;
    if (hcChartFullScreenInstance.resetZoom) {
        hcChartFullScreenInstance.resetZoom();
    } else {
        hcChartFullScreenInstance.update();
    }
}

function inicializarTabla() {
    if ($.fn.DataTable.isDataTable('#tablaHistorial')) {
        $('#tablaHistorial').DataTable().destroy();
    }

    tablaHistorial = $('#tablaHistorial').DataTable({
        ajax: {
            url: 'controllers/HistorialCaidaController.php?action=listar',
            dataSrc: 'data'
        },
        columns: [
            { data: 'nombre_nodo' },
            { 
                data: 'tipo_nodo',
                render: function(data) {
                    return data;
                }
            },
            { data: 'fecha_caida' },
            { 
                data: 'fecha_recuperacion',
                render: function(data) {
                    return data ? data : `<span class="text-muted fst-italic">En curso...</span>`;
                }
            },
            { 
                data: 'duracion_minutos',
                render: function(data, type, row) {
                    if (row.estado === 'en_curso') return '-';
                    return formatearDuracion(data);
                }
            },
            { 
                data: 'estado',
                render: function(data) {
                    if (data === 'en_curso') {
                        return `<span class="badge bg-danger">En Curso</span>`;
                    } else {
                        return `<span class="badge bg-success">Resuelta</span>`;
                    }
                }
            }
        ],
        order: [[2, 'desc']], // Ordenar por fecha_caida descendente
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        }
    });
}

function formatearDuracion(minutos) {
    if (minutos < 60) {
        return `${minutos} min`;
    }
    const horas = Math.floor(minutos / 60);
    const mins = minutos % 60;
    return `${horas} hr ${mins > 0 ? `${mins} min` : ''}`;
}
