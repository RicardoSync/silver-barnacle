let chartGoogle, chartServer, chartTraffic;
let chartHistRecursos, chartHistPing;
let chartHistTraficoArr = [];
let pingInterval, trafficInterval;
let historicoDataCache = null;
const MAX_DATA_POINTS = 30;

function initDetallesModule() {
    const id = document.getElementById('current_mikrotik_id').value;
    if (!id || id === '0') return;

    // Load Historico Simple
    fetch('controllers/MikrotikController.php?action=get_historico&id=' + id)
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success' && data.data) {
            document.getElementById('det-cpu').innerText = data.data.cpu_uso + '%';
            document.getElementById('det-ram').innerText = (data.data.ram_libre / 1024 / 1024).toFixed(2) + ' MB';
            document.getElementById('det-hdd').innerText = (data.data.disco_libre / 1024 / 1024).toFixed(2) + ' MB';
            document.getElementById('det-uptime').innerText = data.data.uptime;
        }
    });

    // Initialize DataTables
    if ($.fn.DataTable.isDataTable('#tablaInterfaces')) $('#tablaInterfaces').DataTable().destroy();
    $('#tablaInterfaces').DataTable({
        "ajax": { "url": "controllers/MikrotikController.php?action=api_interfaces&id=" + id, "dataSrc": "data" },
        "columns": [
            { "data": "name" },
            { "data": "type" },
            { "data": "mac-address", "defaultContent": "" },
            { "data": "actual-mtu", "defaultContent": "" },
            { "data": null, "render": function(data, type, row) {
                let f = "";
                if (row.dynamic === "true") f += "D";
                if (row.disabled === "true") f += "X";
                if (row.running === "true") f += "R";
                if (row.slave === "true") f += "S";
                return f;
            }},
            { "data": null, "render": function(data, type, row) {
                if(row.disabled === "true") return '<span class="badge bg-warning text-dark">Disabled</span>';
                if(row.running === "true") return '<span class="badge bg-success">Running</span>';
                return '<span class="badge bg-danger">Not Running</span>';
            }},
            { "data": null, "render": function(data, type, row) {
                return '<button class="btn btn-sm btn-info text-white" onclick="monitorTrafico(\'' + row.name + '\')" title="Monitor de Tráfico"><i class="bi bi-graph-up"></i></button>';
            }}
        ],
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
    });

    if ($.fn.DataTable.isDataTable('#tablaArp')) $('#tablaArp').DataTable().destroy();
    $('#tablaArp').DataTable({
        "ajax": { "url": "controllers/MikrotikController.php?action=api_arp&id=" + id, "dataSrc": "data" },
        "columns": [
            { "data": "address" },
            { "data": "mac-address", "defaultContent": "" },
            { "data": "interface" }
        ],
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
    });

    if ($.fn.DataTable.isDataTable('#tablaNeighbors')) $('#tablaNeighbors').DataTable().destroy();
    $('#tablaNeighbors').DataTable({
        "ajax": { "url": "controllers/MikrotikController.php?action=api_neighbors&id=" + id, "dataSrc": "data" },
        "columns": [
            { "data": "interface" },
            { "data": "address", "defaultContent": "" },
            { "data": "mac-address", "defaultContent": "" },
            { "data": "identity", "defaultContent": "" }
        ],
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
    });

    if ($.fn.DataTable.isDataTable('#tablaLogs')) $('#tablaLogs').DataTable().destroy();
    $('#tablaLogs').DataTable({
        "ajax": { "url": "controllers/MikrotikController.php?action=api_logs&id=" + id, "dataSrc": "data" },
        "columns": [
            { "data": "time", "defaultContent": "" },
            { "data": "topics", "defaultContent": "" },
            { "data": "message", "defaultContent": "" }
        ],
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        "order": [] // No auto-sort, keep server order
    });

    // Initialize Charts
    initCharts();

    // Start Live Ping
    if(pingInterval) clearInterval(pingInterval);
    pingInterval = setInterval(() => updatePings(id), 1000);
}

function initCharts() {
    if(chartGoogle) chartGoogle.destroy();
    if(chartServer) chartServer.destroy();

    const ctxGoogle = document.getElementById('chartPingGoogle').getContext('2d');
    chartGoogle = new Chart(ctxGoogle, {
        type: 'line',
        data: { labels: [], datasets: [{ label: 'Ping a Google', data: [], borderColor: 'rgb(220, 53, 69)', tension: 0.1 }] },
        options: { animation: false, scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return value + ' ms'; } } } } }
    });

    const ctxServer = document.getElementById('chartPingServer').getContext('2d');
    chartServer = new Chart(ctxServer, {
        type: 'line',
        data: { labels: [], datasets: [{ label: 'Ping a Servidor', data: [], borderColor: 'rgb(13, 110, 253)', tension: 0.1 }] },
        options: { animation: false, scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return value + ' ms'; } } } } }
    });
}

function updatePings(id) {
    const timeLabel = new Date().toLocaleTimeString();

    // Ping Google
    fetch('controllers/MikrotikController.php?action=api_ping_google&id=' + id)
    .then(res => res.json())
    .then(data => {
        let ms = data.status === 'success' ? data.ms : 0;
        if(chartGoogle.data.labels.length > MAX_DATA_POINTS) {
            chartGoogle.data.labels.shift();
            chartGoogle.data.datasets[0].data.shift();
        }
        chartGoogle.data.labels.push(timeLabel);
        chartGoogle.data.datasets[0].data.push(ms);
        chartGoogle.update();
    }).catch(e => console.log(e));

    // Ping Server
    fetch('controllers/MikrotikController.php?action=api_ping_server&id=' + id)
    .then(res => res.json())
    .then(data => {
        let ms = data.status === 'success' ? data.ms : 0;
        if(chartServer.data.labels.length > MAX_DATA_POINTS) {
            chartServer.data.labels.shift();
            chartServer.data.datasets[0].data.shift();
        }
        chartServer.data.labels.push(timeLabel);
        chartServer.data.datasets[0].data.push(ms);
        chartServer.update();
    }).catch(e => console.log(e));
}

window.refreshDetalles = function() {
    historicoDataCache = null; // Forzar recarga del histórico
    
    // Reiniciar los componentes de la vista
    initDetallesModule();
    
    // Si la pestaña de estadísticas está activa, recargarla
    if(document.getElementById('historico-tab').classList.contains('active')) {
        loadHistorico();
    }
}

// Global actions
window.rebootMikrotik = function(id) {
    Swal.fire({
        title: '¿Reiniciar Equipo?',
        text: "Esta acción reiniciará el dispositivo inmediatamente.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, reiniciar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('controllers/MikrotikController.php?action=api_reboot&id=' + id)
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') Swal.fire('Reiniciado', 'El equipo se está reiniciando.', 'success');
                else Swal.fire('Error', data.error || 'Fallo al reiniciar', 'error');
            });
        }
    });
}

window.backupMikrotik = function(id) {
    Swal.fire({
        title: 'Generando Backup',
        text: 'Por favor espere...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('controllers/MikrotikController.php?action=api_backup&id=' + id)
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            Swal.close();
            // Trigger download of the returned text
            let blob = new Blob([data.content], {type: "text/plain;charset=utf-8"});
            let url = window.URL.createObjectURL(blob);
            let a = document.createElement('a');
            a.href = url;
            a.download = `backup_mikrotik_${id}.rsc`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        } else {
            Swal.fire('Error', data.error || 'No se pudo generar el backup', 'error');
        }
    }).catch(err => Swal.fire('Error', 'Fallo de red', 'error'));
}

window.monitorTrafico = function(interfaceName) {
    const id = document.getElementById('current_mikrotik_id').value;
    document.getElementById('tm-interface-name').innerText = interfaceName;
    
    // Reset or init chart
    if(chartTraffic) {
        chartTraffic.destroy();
    }
    
    const ctx = document.getElementById('chartTraffic').getContext('2d');
    chartTraffic = new Chart(ctx, {
        type: 'line',
        data: { 
            labels: [], 
            datasets: [
                { label: 'Descarga (RX)', data: [], borderColor: 'rgb(25, 135, 84)', backgroundColor: 'rgba(25, 135, 84, 0.1)', fill: true, tension: 0.1 },
                { label: 'Subida (TX)', data: [], borderColor: 'rgb(13, 110, 253)', backgroundColor: 'rgba(13, 110, 253, 0.1)', fill: true, tension: 0.1 }
            ] 
        },
        options: { 
            animation: false, 
            scales: { 
                y: { 
                    beginAtZero: true, 
                    ticks: { 
                        callback: function(value) { 
                            return value >= 1000000 ? (value / 1000000).toFixed(1) + ' Mbps' : (value / 1000).toFixed(0) + ' Kbps'; 
                        } 
                    } 
                } 
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let value = context.parsed.y;
                            return value >= 1000000 ? (value / 1000000).toFixed(2) + ' Mbps' : (value / 1000).toFixed(2) + ' Kbps';
                        }
                    }
                }
            }
        }
    });

    let modal = new bootstrap.Modal(document.getElementById('modalTrafficMonitor'));
    modal.show();

    if(trafficInterval) clearInterval(trafficInterval);
    trafficInterval = setInterval(() => {
        fetch('controllers/MikrotikController.php?action=api_traffic_monitor&id=' + id + '&interface=' + encodeURIComponent(interfaceName))
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                const timeLabel = new Date().toLocaleTimeString();
                let rx = data.rx_bits;
                let tx = data.tx_bits;
                
                // Update text
                document.getElementById('tm-rx-text').innerText = rx >= 1000000 ? (rx / 1000000).toFixed(2) + ' Mbps' : (rx / 1000).toFixed(2) + ' Kbps';
                document.getElementById('tm-tx-text').innerText = tx >= 1000000 ? (tx / 1000000).toFixed(2) + ' Mbps' : (tx / 1000).toFixed(2) + ' Kbps';

                if(chartTraffic.data.labels.length > MAX_DATA_POINTS) {
                    chartTraffic.data.labels.shift();
                    chartTraffic.data.datasets[0].data.shift();
                    chartTraffic.data.datasets[1].data.shift();
                }
                chartTraffic.data.labels.push(timeLabel);
                chartTraffic.data.datasets[0].data.push(rx);
                chartTraffic.data.datasets[1].data.push(tx);
                chartTraffic.update();
            }
        }).catch(e => console.log(e));
    }, 1000);
}

window.stopTrafficMonitor = function() {
    if(trafficInterval) {
        clearInterval(trafficInterval);
    }
}

// ==============================
// HISTORICAL CHARTS LOGIC
// ==============================
window.loadHistorico = function() {
    const id = document.getElementById('current_mikrotik_id').value;
    if(historicoDataCache) return; // Ya lo cargamos una vez

    Swal.fire({ title: 'Cargando estadísticas', text: 'Obteniendo datos de las últimas 24h...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

    fetch('controllers/MikrotikController.php?action=api_historico_graficas&id=' + id)
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if(data.status === 'success') {
            historicoDataCache = data.data;
            renderHistRecursos();
            renderHistPing();
            renderHistTraffic();
        } else {
            Swal.fire('Error', 'No se pudo cargar el histórico', 'error');
        }
    }).catch(e => Swal.fire('Error', 'Fallo de red', 'error'));
}

const chartTimeScale = {
    type: 'time',
    time: {
        tooltipFormat: 'HH:mm',
        displayFormats: { hour: 'HH:mm', minute: 'HH:mm' }
    }
};

const chartZoomOptions = {
    pan: { enabled: true, mode: 'x' },
    zoom: { wheel: { enabled: true }, drag: { enabled: true }, pinch: { enabled: true }, mode: 'x' }
};

function renderHistRecursos() {
    const data = historicoDataCache.recursos;
    const labels = data.map(d => d.hora_completa.replace(' ', 'T'));
    const cpu = data.map(d => parseInt(d.cpu_uso));
    const ram = data.map(d => {
        const t = parseInt(d.ram_total);
        const l = parseInt(d.ram_libre);
        return t > 0 ? ((t - l) / t * 100).toFixed(2) : 0; // % de RAM usada
    });

    const ctx = document.getElementById('chartHistRecursos').getContext('2d');
    chartHistRecursos = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Uso de CPU (%)', data: cpu, borderColor: 'rgb(220, 53, 69)', backgroundColor: 'rgba(220, 53, 69, 0.1)', fill: true, tension: 0.1 },
                { label: 'Uso de RAM (%)', data: ram, borderColor: 'rgb(13, 110, 253)', backgroundColor: 'rgba(13, 110, 253, 0.1)', fill: true, tension: 0.1 }
            ]
        },
        options: { 
            responsive: true, 
            scales: { x: chartTimeScale, y: { beginAtZero: true, max: 100 } },
            plugins: { zoom: chartZoomOptions }
        }
    });
}

function renderHistPing() {
    const pings = historicoDataCache.pings;
    const labelsGoogle = pings.google ? pings.google.map(d => d.hora_completa.replace(' ', 'T')) : [];
    const dataGoogle = pings.google ? pings.google.map(d => parseInt(d.ms)) : [];
    const labelsServidor = pings.servidor ? pings.servidor.map(d => d.hora_completa.replace(' ', 'T')) : [];
    const dataServidor = pings.servidor ? pings.servidor.map(d => parseInt(d.ms)) : [];

    // Usaremos los labels de google como base asumiendo que ambos CRON corrieron juntos
    const labels = labelsGoogle.length > labelsServidor.length ? labelsGoogle : labelsServidor;

    const ctx = document.getElementById('chartHistPing').getContext('2d');
    chartHistPing = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Google (ms)', data: dataGoogle, borderColor: 'rgb(255, 193, 7)', fill: false, tension: 0.1 },
                { label: 'Servidor (ms)', data: dataServidor, borderColor: 'rgb(25, 135, 84)', fill: false, tension: 0.1 }
            ]
        },
        options: { 
            responsive: true, 
            scales: { x: chartTimeScale, y: { beginAtZero: true } },
            plugins: { zoom: chartZoomOptions }
        }
    });
}

window.renderHistTraffic = function() {
    const container = document.getElementById('hist-traffic-container');
    container.innerHTML = '';
    
    // Destroy previous charts
    chartHistTraficoArr.forEach(c => c.destroy());
    chartHistTraficoArr = [];

    if(!historicoDataCache.trafico) return;

    let index = 0;
    for (let intf in historicoDataCache.trafico) {
        const data = historicoDataCache.trafico[intf];
        const labels = data.map(d => d.hora_completa.replace(' ', 'T'));
        const rx = data.map(d => parseInt(d.rx) / 1000000); 
        const tx = data.map(d => parseInt(d.tx) / 1000000);
        
        const canvasId = 'chartHistTrafico_' + index;
        
        // Create HTML
        const col = document.createElement('div');
        col.className = 'col-md-6 mb-4';
        col.innerHTML = `
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-ethernet"></i> ${intf}</span>
                    <button class="btn btn-sm btn-outline-secondary" onclick="openChartFullScreen('traffic_${index}', 'Tráfico de Interfaces: ${intf}')"><i class="bi bi-arrows-fullscreen"></i> Ampliar</button>
                </div>
                <div class="card-body">
                    <canvas id="${canvasId}" height="100"></canvas>
                </div>
            </div>
        `;
        container.appendChild(col);

        // Init Chart
        const ctx = document.getElementById(canvasId).getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Descarga (Mbps)', data: rx, borderColor: 'rgb(25, 135, 84)', backgroundColor: 'rgba(25, 135, 84, 0.1)', fill: true, tension: 0.1 },
                    { label: 'Subida (Mbps)', data: tx, borderColor: 'rgb(13, 110, 253)', backgroundColor: 'rgba(13, 110, 253, 0.1)', fill: true, tension: 0.1 }
                ]
            },
            options: { 
                responsive: true, 
                scales: { x: chartTimeScale, y: { beginAtZero: true } },
                plugins: { zoom: chartZoomOptions }
            }
        });
        
        chartHistTraficoArr.push(chart);
        index++;
    }
}

// Clear interval when navigating away
const originalLoadView = window.loadView;
window.loadView = function(viewName, params = null) {
    if(pingInterval) clearInterval(pingInterval);
    if(trafficInterval) clearInterval(trafficInterval);
    originalLoadView(viewName, params);
}

let chartFullScreenInstance = null;

window.openChartFullScreen = function(chartType, title) {
    let sourceChart = null;
    if (chartType === 'recursos') sourceChart = chartHistRecursos;
    else if (chartType === 'ping') sourceChart = chartHistPing;
    else if (chartType.startsWith('traffic_')) {
        let idx = parseInt(chartType.split('_')[1]);
        sourceChart = chartHistTraficoArr[idx];
    }

    if(!sourceChart) return;

    document.getElementById('fs-chart-title').innerText = title;
    
    if(chartFullScreenInstance) {
        chartFullScreenInstance.destroy();
    }

    const ctx = document.getElementById('chartFullScreenCanvas').getContext('2d');
    
    // Configurar la nueva grafica combinando opciones
    let newOptions = Object.assign({}, sourceChart.config.options);
    newOptions.responsive = true;
    newOptions.maintainAspectRatio = false;
    
    // Copia manual de data para evitar problemas circulares o de contexto
    let newData = {
        labels: sourceChart.data.labels.slice(),
        datasets: sourceChart.data.datasets.map(ds => ({
            label: ds.label,
            data: ds.data.slice(),
            borderColor: ds.borderColor,
            backgroundColor: ds.backgroundColor,
            fill: ds.fill,
            tension: ds.tension
        }))
    };
    
    chartFullScreenInstance = new Chart(ctx, {
        type: sourceChart.config.type,
        data: newData,
        options: newOptions
    });

    let modal = new bootstrap.Modal(document.getElementById('modalChartFullScreen'));
    modal.show();
}

window.closeChartFullScreen = function() {
    if(chartFullScreenInstance) {
        chartFullScreenInstance.destroy();
        chartFullScreenInstance = null;
    }
}

window.zoomFullScreenChart = function(range) {
    if(!chartFullScreenInstance) return;
    
    let labels = chartFullScreenInstance.data.labels;
    if(!labels || labels.length === 0) return;
    
    // El último dato asumimos es "ahora"
    let lastDate = new Date(labels[labels.length - 1]);
    let pastDate = new Date(lastDate.getTime());
    
    if (range === '10m') pastDate.setMinutes(pastDate.getMinutes() - 10);
    else if (range === '1h') pastDate.setHours(pastDate.getHours() - 1);
    else if (range === '3h') pastDate.setHours(pastDate.getHours() - 3);
    else if (range === '24h') pastDate.setHours(pastDate.getHours() - 24);
    else if (range === '1w') pastDate.setDate(pastDate.getDate() - 7);
    
    chartFullScreenInstance.options.scales.x.min = pastDate.getTime();
    chartFullScreenInstance.options.scales.x.max = lastDate.getTime() + (5 * 60 * 1000); // 5 mins extras visuales
    chartFullScreenInstance.update();
}

window.resetFullScreenChartZoom = function() {
    if(!chartFullScreenInstance) return;
    delete chartFullScreenInstance.options.scales.x.min;
    delete chartFullScreenInstance.options.scales.x.max;
    if (chartFullScreenInstance.resetZoom) {
        chartFullScreenInstance.resetZoom();
    } else {
        chartFullScreenInstance.update();
    }
}
