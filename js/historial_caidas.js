let chartCaidas = null;
let chartTopCaidasNodos = null;
let tablaHistorial = null;
let hcChartFullScreenInstance = null;

function initHistorialCaidasModule() {
    cargarTodoHistorialCaidas();
}

function cargarTodoHistorialCaidas() {
    const horas = $('#selectHorasHistorialCaidas').val() || 24;
    cargarKPIs(horas);
    cargarGrafica(horas);
    cargarTabla(horas);
}

function cargarKPIs(horas) {
    $.ajax({
        url: `controllers/HistorialCaidaController.php?action=kpis&horas=${horas}`,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#kpiActivas').html(data.activas > 0 ? `<span class="text-danger"><i class="bi bi-circle-fill fs-6 me-1 spinner-grow text-danger" style="width: 12px; height: 12px;"></i>${data.activas}</span>` : '0');
            $('#kpiRango').text(data.rango || 0);
            $('#kpiPromedio').text(data.promedio_minutos ? `${data.promedio_minutos} min` : '0 min');
            $('#kpiMax').text(data.max_minutos ? `${data.max_minutos} min` : '0 min');
        },
        error: function(err) {
            console.error('Error al cargar KPIs de caídas:', err);
        }
    });
}

function cargarGrafica(horas) {
    $.ajax({
        url: `controllers/HistorialCaidaController.php?action=grafica&horas=${horas}`,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            renderGraficaEventos(data.labels || [], data.data || [], data.nodos || [], data.tipos || [], data.estados || []);
            renderGraficaTopNodos(data.data || [], data.nodos || []);
        },
        error: function(err) {
            console.error('Error al cargar gráfica de caídas:', err);
        }
    });
}

const caidasChartTimeScale = {
    type: 'time',
    time: {
        tooltipFormat: 'yyyy-MM-dd HH:mm',
        displayFormats: { 
            minute: 'HH:mm',
            hour: 'HH:mm', 
            day: 'MMM d'
        }
    }
};

function renderGraficaEventos(labels, data, nodos, tipos, estados) {
    const canvas = document.getElementById('graficaCaidas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    
    if (chartCaidas) {
        chartCaidas.destroy();
        chartCaidas = null;
    }

    const backgroundColors = estados.map(st => st === 'en_curso' ? 'rgba(220, 53, 69, 0.85)' : 'rgba(255, 193, 7, 0.85)');
    const borderColors = estados.map(st => st === 'en_curso' ? '#dc3545' : '#ffc107');

    chartCaidas = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Duración Caída (min)',
                data: data,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1,
                barThickness: Math.min(25, Math.max(6, Math.floor(600 / (labels.length || 1))))
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: function(items) {
                            if (!items || !items[0]) return '';
                            const idx = items[0].dataIndex;
                            return `${nodos[idx]} (${tipos[idx] ? tipos[idx].toUpperCase() : 'NODO'})`;
                        },
                        label: function(context) {
                            const idx = context.dataIndex;
                            const st = estados[idx] === 'en_curso' ? 'En Curso (Activa)' : 'Resuelta';
                            const mins = context.raw;
                            return `Duración: ${mins} min | Estado: ${st}`;
                        }
                    }
                }
            },
            scales: {
                x: caidasChartTimeScale,
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Duración (Minutos)' },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                }
            }
        }
    });
}

function renderGraficaTopNodos(data, nodos) {
    const canvas = document.getElementById('graficaTopCaidasNodos');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    if (chartTopCaidasNodos) {
        chartTopCaidasNodos.destroy();
        chartTopCaidasNodos = null;
    }

    // Agrupar duración acumulada por nodo
    const nodeTotals = {};
    for (let i = 0; i < nodos.length; i++) {
        const name = nodos[i] || 'Desconocido';
        const val = data[i] || 0;
        nodeTotals[name] = (nodeTotals[name] || 0) + val;
    }

    // Ordenar de mayor a menor y tomar top 5
    const sorted = Object.entries(nodeTotals).sort((a, b) => b[1] - a[1]).slice(0, 5);
    const topLabels = sorted.map(item => item[0]);
    const topData = sorted.map(item => item[1]);

    const palette = ['#dc3545', '#fd7e14', '#ffc107', '#0d6efd', '#6f42c1'];

    chartTopCaidasNodos = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: topLabels.length > 0 ? topLabels : ['Sin Caídas'],
            datasets: [{
                data: topData.length > 0 ? topData : [1],
                backgroundColor: topData.length > 0 ? palette.slice(0, topData.length) : ['#198754'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (topData.length === 0) return 'Sin eventos de caída';
                            return ` ${context.label}: ${context.raw} min acumulados`;
                        }
                    }
                }
            }
        }
    });
}

function cargarTabla(horas) {
    if ($.fn.DataTable.isDataTable('#tablaHistorial')) {
        $('#tablaHistorial').DataTable().destroy();
    }

    tablaHistorial = $('#tablaHistorial').DataTable({
        ajax: {
            url: `controllers/HistorialCaidaController.php?action=listar&horas=${horas}`,
            dataSrc: 'data'
        },
        columns: [
            { 
                data: 'nombre_nodo',
                render: function(data, type, row) {
                    return `<span class="fw-bold text-dark"><i class="bi bi-hdd-network me-1 text-primary"></i>${escapeHtml(data)}</span>`;
                }
            },
            { 
                data: 'tipo_nodo',
                render: function(data) {
                    const t = (data || '').toLowerCase();
                    return t === 'mikrotik' 
                        ? `<span class="badge bg-primary"><i class="bi bi-router me-1"></i>MikroTik</span>`
                        : `<span class="badge bg-info text-white"><i class="bi bi-hdd me-1"></i>Equipo</span>`;
                }
            },
            { data: 'fecha_caida' },
            { 
                data: 'fecha_recuperacion',
                render: function(data) {
                    return data ? data : `<span class="text-danger fw-semibold"><i class="bi bi-circle-fill fs-6 me-1 spinner-grow text-danger" style="width: 10px; height: 10px;"></i>En curso...</span>`;
                }
            },
            { 
                data: 'duracion_minutos',
                render: function(data, type, row) {
                    if (row.estado === 'en_curso') return '<span class="badge bg-secondary">-</span>';
                    return `<span class="fw-semibold text-dark">${formatearDuracion(data)}</span>`;
                }
            },
            { 
                data: 'estado',
                render: function(data) {
                    if (data === 'en_curso') {
                        return `<span class="badge bg-danger text-white"><i class="bi bi-exclamation-triangle me-1"></i>En Curso</span>`;
                    } else {
                        return `<span class="badge bg-success text-white"><i class="bi bi-check-circle me-1"></i>Resuelta</span>`;
                    }
                }
            }
        ],
        order: [[2, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        }
    });
}

function formatearDuracion(minutos) {
    const mins = parseInt(minutos) || 0;
    if (mins < 60) {
        return `${mins} min`;
    }
    const horas = Math.floor(mins / 60);
    const restoMins = mins % 60;
    return `${horas} hr ${restoMins > 0 ? `${restoMins} min` : ''}`;
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

window.openHistorialChartFullScreen = function() {
    if (!chartCaidas) return;
    
    if (hcChartFullScreenInstance) {
        hcChartFullScreenInstance.destroy();
        hcChartFullScreenInstance = null;
    }

    const ctx = document.getElementById('chartFullScreenCanvas').getContext('2d');
    
    let newOptions = JSON.parse(JSON.stringify(chartCaidas.config.options));
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
            barThickness: 12
        }))
    };
    
    hcChartFullScreenInstance = new Chart(ctx, {
        type: chartCaidas.config.type,
        data: newData,
        options: newOptions
    });

    let modal = new bootstrap.Modal(document.getElementById('modalChartFullScreen'));
    modal.show();
};

window.closeHistorialChartFullScreen = function() {
    if (hcChartFullScreenInstance) {
        hcChartFullScreenInstance.destroy();
        hcChartFullScreenInstance = null;
    }
};

window.initHistorialCaidasModule = initHistorialCaidasModule;
window.cargarTodoHistorialCaidas = cargarTodoHistorialCaidas;
