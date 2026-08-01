let chartEquipoPingLive, chartEquipoPingHist;
let equipoPingInterval;
let equipoHistoricoDataCache = null;
const MAX_LIVE_POINTS = 30;

function initEquiposDetallesModule() {
    const id = document.getElementById('current_equipo_id').value;
    if (!id || id === '0') return;

    // Cargar datos básicos en las tarjetas
    fetch('controllers/EquipoController.php?action=obtener&id=' + id)
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success' && data.data) {
            document.getElementById('det-eq-nombre').innerText = data.data.nombre;
            document.getElementById('det-eq-ip').innerText = data.data.ip_address;
            document.getElementById('det-eq-comunidad').innerText = data.data.comunidad_snmp || 'Ninguna';
            document.getElementById('det-eq-contacto').innerText = data.data.contacto_snmp || 'No definido';
        }
    });

    // Inicializar Gráfica de Ping En Vivo
    initLivePingChart();

    // Comenzar monitoreo de ping en vivo
    if(equipoPingInterval) clearInterval(equipoPingInterval);
    equipoPingInterval = setInterval(() => updateEquipoLivePing(id), 1000);
}

function initLivePingChart() {
    if(chartEquipoPingLive) chartEquipoPingLive.destroy();

    const ctx = document.getElementById('chartEquipoPingLive').getContext('2d');
    chartEquipoPingLive = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Ping (ms)',
                data: [],
                borderColor: 'rgb(220, 53, 69)',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                fill: true,
                tension: 0.1
            }]
        },
        options: {
            animation: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return value + ' ms'; }
                    }
                }
            }
        }
    });
}

function updateEquipoLivePing(id) {
    const timeLabel = new Date().toLocaleTimeString();

    fetch('controllers/EquipoController.php?action=api_ping_server&id=' + id)
    .then(res => res.json())
    .then(data => {
        let ms = data.status === 'success' ? data.ms : 0;
        
        // Actualizar visualización del estado
        const badge = document.getElementById('ping-live-status');
        if (badge) {
            if (ms > 0) {
                badge.className = "badge bg-success";
                badge.innerText = "Online (" + ms + " ms)";
            } else {
                badge.className = "badge bg-danger";
                badge.innerText = "Offline / Inalcanzable";
            }
        }

        if (chartEquipoPingLive.data.labels.length > MAX_LIVE_POINTS) {
            chartEquipoPingLive.data.labels.shift();
            chartEquipoPingLive.data.datasets[0].data.shift();
        }
        chartEquipoPingLive.data.labels.push(timeLabel);
        chartEquipoPingLive.data.datasets[0].data.push(ms);
        chartEquipoPingLive.update();
    }).catch(e => console.log(e));
}

window.refreshEquiposDetalles = function() {
    equipoHistoricoDataCache = null;
    initEquiposDetallesModule();
    
    // Si la pestaña de histórico está activa, la recargamos
    if(document.getElementById('historico-tab') && document.getElementById('historico-tab').classList.contains('active')) {
        loadEquipoHistorico();
    }
}

window.loadEquipoHistorico = function() {
    const id = document.getElementById('current_equipo_id').value;
    if(equipoHistoricoDataCache) return;

    Swal.fire({
        title: 'Cargando estadísticas',
        text: 'Obteniendo datos de ping de las últimas 24h...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('controllers/EquipoController.php?action=api_historico_pings&id=' + id)
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if(data.status === 'success') {
            equipoHistoricoDataCache = data.data;
            renderEquipoHistChart();
        } else {
            Swal.fire('Error', 'No se pudieron cargar los datos históricos.', 'error');
        }
    }).catch(e => {
        Swal.close();
        Swal.fire('Error', 'Fallo de red al obtener el historial.', 'error');
    });
};

function calculateEquipoStats(data) {
    if (!data || data.length === 0) {
        if (document.getElementById('stat-ping-avg')) document.getElementById('stat-ping-avg').innerText = '0 ms';
        if (document.getElementById('stat-ping-minmax')) document.getElementById('stat-ping-minmax').innerText = 'Min: 0 / Max: 0';
        if (document.getElementById('stat-uptime-percent')) document.getElementById('stat-uptime-percent').innerText = '0%';
        if (document.getElementById('stat-online-time')) document.getElementById('stat-online-time').innerText = '0 min';
        if (document.getElementById('stat-offline-time')) document.getElementById('stat-offline-time').innerText = 'Caído: 0 min';
        if (document.getElementById('stat-loss-percent')) document.getElementById('stat-loss-percent').innerText = '0%';
        if (document.getElementById('stat-total-probes')) document.getElementById('stat-total-probes').innerText = 'Muestras: 0';
        return;
    }

    let totalMs = 0;
    let validCount = 0;
    let offlineCount = 0;
    let minMs = Infinity;
    let maxMs = -1;

    data.forEach(item => {
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

    const totalProbes = data.length;
    const avgMs = validCount > 0 ? Math.round(totalMs / validCount) : 0;
    const uptimePercent = Math.round((validCount / totalProbes) * 1000) / 10;
    const lossPercent = Math.round((offlineCount / totalProbes) * 1000) / 10;

    const onlineMinutes = validCount;
    const offlineMinutes = offlineCount;

    const formatTime = (mins) => {
        if (mins >= 60) {
            const h = Math.floor(mins / 60);
            const m = mins % 60;
            return `${h}h ${m}m`;
        }
        return `${mins} min`;
    };

    if (document.getElementById('stat-ping-avg')) document.getElementById('stat-ping-avg').innerText = `${avgMs} ms`;
    if (document.getElementById('stat-ping-minmax')) document.getElementById('stat-ping-minmax').innerText = `Min: ${minMs === Infinity ? 0 : minMs}ms / Max: ${maxMs === -1 ? 0 : maxMs}ms`;
    
    const uptimeEl = document.getElementById('stat-uptime-percent');
    if (uptimeEl) {
        uptimeEl.innerText = `${uptimePercent}%`;
        uptimeEl.className = uptimePercent >= 98 ? 'fs-4 fw-bold text-success' : (uptimePercent >= 90 ? 'fs-4 fw-bold text-warning' : 'fs-4 fw-bold text-danger');
    }

    if (document.getElementById('stat-online-time')) document.getElementById('stat-online-time').innerText = formatTime(onlineMinutes);
    if (document.getElementById('stat-offline-time')) document.getElementById('stat-offline-time').innerText = `Caído: ${formatTime(offlineMinutes)}`;
    if (document.getElementById('stat-loss-percent')) document.getElementById('stat-loss-percent').innerText = `${lossPercent}%`;
    if (document.getElementById('stat-total-probes')) document.getElementById('stat-total-probes').innerText = `Muestras BD: ${totalProbes}`;
}

function renderEquipoHistChart() {
    if (chartEquipoPingHist) chartEquipoPingHist.destroy();

    const data = equipoHistoricoDataCache;
    calculateEquipoStats(data);

    const labels = data.map(d => d.hora_completa.replace(' ', 'T'));
    const pings = data.map(d => parseInt(d.ms));

    const ctx = document.getElementById('chartEquipoPingHist').getContext('2d');
    chartEquipoPingHist = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Latencia (ms)',
                data: pings,
                borderColor: 'rgb(13, 110, 253)',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    type: 'time',
                    time: {
                        tooltipFormat: 'HH:mm',
                        displayFormats: { hour: 'HH:mm', minute: 'HH:mm' }
                    }
                },
                y: { beginAtZero: true }
            },
            plugins: {
                zoom: {
                    pan: { enabled: true, mode: 'x' },
                    zoom: { wheel: { enabled: true }, drag: { enabled: true }, pinch: { enabled: true }, mode: 'x' }
                }
            }
        }
    });
}

window.clearEquiposDetallesIntervals = function() {
    if (equipoPingInterval) {
        clearInterval(equipoPingInterval);
        equipoPingInterval = null;
    }
};

window.openChartFullScreen = function (chartType, title) {
    if (chartType !== 'eq_historico') return;
    if (!chartEquipoPingHist) return;

    document.getElementById('fs-chart-title').innerText = title;

    if (typeof chartFullScreenInstance !== 'undefined' && chartFullScreenInstance) {
        chartFullScreenInstance.destroy();
    }

    const ctx = document.getElementById('chartFullScreenCanvas').getContext('2d');
    let newOptions = Object.assign({}, chartEquipoPingHist.config.options);
    newOptions.responsive = true;
    newOptions.maintainAspectRatio = false;

    let newData = {
        labels: chartEquipoPingHist.data.labels.slice(),
        datasets: chartEquipoPingHist.data.datasets.map(ds => ({
            label: ds.label,
            data: ds.data.slice(),
            borderColor: ds.borderColor,
            backgroundColor: ds.backgroundColor,
            fill: ds.fill,
            tension: ds.tension
        }))
    };

    chartFullScreenInstance = new Chart(ctx, {
        type: chartEquipoPingHist.config.type,
        data: newData,
        options: newOptions
    });

    let modal = new bootstrap.Modal(document.getElementById('modalChartFullScreen'));
    modal.show();
};