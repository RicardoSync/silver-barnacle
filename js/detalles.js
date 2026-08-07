let apexGoogleChart = null;
let apexServerChart = null;
let chartTraffic = null;
let pingInterval = null;
let trafficInterval = null;

let googlePingSeries = [];
let serverPingSeries = [];
const MAX_DATA_POINTS = 30;

function initDetallesModule() {
    const idInput = document.getElementById('current_mikrotik_id');
    if (!idInput) return;
    const id = idInput.value;
    if (!id || id === '0') return;

    // 1. Cargar Recursos (CPU, RAM, Disco, Uptime)
    fetchResources(id);

    // 2. Inicializar DataTables
    initDetallesDataTables(id);

    // 3. Inicializar Gráficas ApexCharts
    initApexPingCharts();

    // 4. Iniciar polling de Pings cada 1 segundo
    if (pingInterval) clearInterval(pingInterval);
    updatePings(id);
    pingInterval = setInterval(() => updatePings(id), 1000);
}

function fetchResources(id) {
    fetch('controllers/MikrotikController.php?action=get_historico&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.data) {
                const cpuEl = document.getElementById('det-cpu');
                const ramEl = document.getElementById('det-ram');
                const hddEl = document.getElementById('det-hdd');
                const uptimeEl = document.getElementById('det-uptime');

                if (cpuEl) cpuEl.innerText = (data.data.cpu_uso || 0) + '%';
                if (ramEl) {
                    const ramMb = ((data.data.ram_libre || 0) / (1024 * 1024)).toFixed(2);
                    ramEl.innerText = ramMb + ' MB';
                }
                if (hddEl) {
                    const hddMb = ((data.data.disco_libre || 0) / (1024 * 1024)).toFixed(2);
                    hddEl.innerText = hddMb + ' MB';
                }
                if (uptimeEl) uptimeEl.innerText = data.data.uptime || '--';
            }
        })
        .catch(err => console.error('Error fetching resources:', err));
}

function initDetallesDataTables(id) {
    // Interfaces
    if ($.fn.DataTable.isDataTable('#tablaInterfaces')) {
        $('#tablaInterfaces').DataTable().destroy();
    }
    $('#tablaInterfaces').DataTable({
        "ajax": { "url": "controllers/MikrotikController.php?action=api_interfaces&id=" + id, "dataSrc": "data" },
        "columns": [
            { "data": "name" },
            { "data": "type" },
            { "data": "mac-address", "defaultContent": "-" },
            { "data": "actual-mtu", "defaultContent": "-" },
            { 
                "data": null, 
                "render": function(data, type, row) {
                    let flags = "";
                    if (row.dynamic === "true") flags += '<span class="badge text-bg-secondary me-1">D</span>';
                    if (row.disabled === "true") flags += '<span class="badge text-bg-danger me-1">X</span>';
                    if (row.running === "true") flags += '<span class="badge text-bg-success me-1">R</span>';
                    if (row.slave === "true") flags += '<span class="badge text-bg-info me-1">S</span>';
                    return flags || '-';
                }
            },
            { 
                "data": null, 
                "render": function(data, type, row) {
                    if (row.disabled === "true") return '<span class="badge bg-warning text-dark"><i class="bi bi-pause-circle me-1"></i>Disabled</span>';
                    if (row.running === "true") return '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Running</span>';
                    return '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Not Running</span>';
                }
            },
            { 
                "data": null, 
                "className": "text-center",
                "render": function(data, type, row) {
                    return `<button class="btn btn-sm btn-info text-white shadow-sm" onclick="monitorTrafico('${row.name}')" title="Monitorear Tráfico"><i class="bi bi-graph-up me-1"></i> Tráfico</button>`;
                }
            }
        ],
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
    });

    // Tabla ARP
    if ($.fn.DataTable.isDataTable('#tablaArp')) {
        $('#tablaArp').DataTable().destroy();
    }
    $('#tablaArp').DataTable({
        "ajax": { "url": "controllers/MikrotikController.php?action=api_arp&id=" + id, "dataSrc": "data" },
        "columns": [
            { "data": "address" },
            { "data": "mac-address", "defaultContent": "-" },
            { "data": "interface" }
        ],
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
    });

    // Tabla Neighbors
    if ($.fn.DataTable.isDataTable('#tablaNeighbors')) {
        $('#tablaNeighbors').DataTable().destroy();
    }
    $('#tablaNeighbors').DataTable({
        "ajax": { "url": "controllers/MikrotikController.php?action=api_neighbors&id=" + id, "dataSrc": "data" },
        "columns": [
            { "data": "interface" },
            { "data": "address", "defaultContent": "-" },
            { "data": "mac-address", "defaultContent": "-" },
            { "data": "identity", "defaultContent": "-" }
        ],
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
    });

    // Tabla Logs
    if ($.fn.DataTable.isDataTable('#tablaLogs')) {
        $('#tablaLogs').DataTable().destroy();
    }
    $('#tablaLogs').DataTable({
        "ajax": { "url": "controllers/MikrotikController.php?action=api_logs&id=" + id, "dataSrc": "data" },
        "columns": [
            { "data": "time", "defaultContent": "-" },
            { "data": "topics", "defaultContent": "-" },
            { "data": "message", "defaultContent": "-" }
        ],
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        "order": []
    });
}

function initApexPingCharts() {
    googlePingSeries = [];
    serverPingSeries = [];

    if (apexGoogleChart) {
        try { apexGoogleChart.destroy(); } catch (e) {}
        apexGoogleChart = null;
    }
    if (apexServerChart) {
        try { apexServerChart.destroy(); } catch (e) {}
        apexServerChart = null;
    }

    const optionsGoogle = {
        series: [{ name: 'Ping Google (8.8.8.8)', data: [] }],
        chart: {
            type: 'line',
            height: 230,
            toolbar: { show: false },
            animations: { enabled: false }
        },
        colors: ['#dc3545'],
        stroke: { curve: 'smooth', width: 2 },
        markers: { size: 3 },
        xaxis: { categories: [], labels: { style: { fontSize: '10px' } } },
        yaxis: { labels: { formatter: val => `${Math.round(val)} ms` }, min: 0 },
        tooltip: { y: { formatter: val => `${val} ms` } }
    };

    const optionsServer = {
        series: [{ name: 'Ping Servidor', data: [] }],
        chart: {
            type: 'line',
            height: 230,
            toolbar: { show: false },
            animations: { enabled: false }
        },
        colors: ['#0d6efd'],
        stroke: { curve: 'smooth', width: 2 },
        markers: { size: 3 },
        xaxis: { categories: [], labels: { style: { fontSize: '10px' } } },
        yaxis: { labels: { formatter: val => `${Math.round(val)} ms` }, min: 0 },
        tooltip: { y: { formatter: val => `${val} ms` } }
    };

    const elGoogle = document.querySelector('#chartPingGoogleApex');
    if (elGoogle) {
        apexGoogleChart = new ApexCharts(elGoogle, optionsGoogle);
        apexGoogleChart.render();
    }

    const elServer = document.querySelector('#chartPingServerApex');
    if (elServer) {
        apexServerChart = new ApexCharts(elServer, optionsServer);
        apexServerChart.render();
    }
}

function updatePings(id) {
    const timeLabel = new Date().toLocaleTimeString('es-MX', { hour12: false });

    // Ping Google
    fetch('controllers/MikrotikController.php?action=api_ping_google&id=' + id)
        .then(res => res.json())
        .then(data => {
            let ms = data.status === 'success' ? (data.ms || 0) : 0;
            if (googlePingSeries.length >= MAX_DATA_POINTS) googlePingSeries.shift();
            googlePingSeries.push({ x: timeLabel, y: ms });

            if (apexGoogleChart) {
                apexGoogleChart.updateSeries([{ name: 'Ping Google (8.8.8.8)', data: googlePingSeries }]);
            }
        })
        .catch(e => console.error('Error ping google:', e));

    // Ping Server
    fetch('controllers/MikrotikController.php?action=api_ping_server&id=' + id)
        .then(res => res.json())
        .then(data => {
            let ms = data.status === 'success' ? (data.ms || 0) : 0;
            if (serverPingSeries.length >= MAX_DATA_POINTS) serverPingSeries.shift();
            serverPingSeries.push({ x: timeLabel, y: ms });

            if (apexServerChart) {
                apexServerChart.updateSeries([{ name: 'Ping Servidor', data: serverPingSeries }]);
            }
        })
        .catch(e => console.error('Error ping server:', e));
}

// Acción del botón Monitoreo de Tráfico por Interfaz
window.monitorTrafico = function(interfaceName) {
    const idInput = document.getElementById('current_mikrotik_id');
    if (!idInput) return;
    const id = idInput.value;

    const nameSpan = document.getElementById('tm-interface-name');
    if (nameSpan) nameSpan.innerText = interfaceName;

    if (chartTraffic) {
        chartTraffic.destroy();
        chartTraffic = null;
    }

    const ctx = document.getElementById('chartTraffic');
    if (ctx) {
        chartTraffic = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    { label: 'Descarga (RX)', data: [], borderColor: '#198754', backgroundColor: 'rgba(25, 135, 84, 0.1)', fill: true, tension: 0.3 },
                    { label: 'Subida (TX)', data: [], borderColor: '#0dcaf0', backgroundColor: 'rgba(13, 202, 240, 0.1)', fill: true, tension: 0.3 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.parsed.y || 0;
                                return (context.dataset.label || '') + ': ' + (value >= 1000000 ? (value / 1000000).toFixed(2) + ' Mbps' : (value / 1000).toFixed(2) + ' Kbps');
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 9 } } },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value >= 1000000 ? (value / 1000000).toFixed(1) + ' M' : (value / 1000).toFixed(0) + ' K';
                            }
                        }
                    }
                }
            }
        });
    }

    const modalEl = document.getElementById('modalTrafficMonitor');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    if (trafficInterval) clearInterval(trafficInterval);
    trafficInterval = setInterval(() => {
        fetch('controllers/MikrotikController.php?action=api_traffic_monitor&id=' + id + '&interface=' + encodeURIComponent(interfaceName))
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const timeLabel = new Date().toLocaleTimeString('es-MX', { hour12: false });
                    let rx = data.rx_bits || 0;
                    let tx = data.tx_bits || 0;

                    const rxEl = document.getElementById('tm-rx-text');
                    const txEl = document.getElementById('tm-tx-text');
                    if (rxEl) rxEl.innerText = rx >= 1000000 ? (rx / 1000000).toFixed(2) + ' Mbps' : (rx / 1000).toFixed(2) + ' Kbps';
                    if (txEl) txEl.innerText = tx >= 1000000 ? (tx / 1000000).toFixed(2) + ' Mbps' : (tx / 1000).toFixed(2) + ' Kbps';

                    if (chartTraffic) {
                        if (chartTraffic.data.labels.length > MAX_DATA_POINTS) {
                            chartTraffic.data.labels.shift();
                            chartTraffic.data.datasets[0].data.shift();
                            chartTraffic.data.datasets[1].data.shift();
                        }
                        chartTraffic.data.labels.push(timeLabel);
                        chartTraffic.data.datasets[0].data.push(rx);
                        chartTraffic.data.datasets[1].data.push(tx);
                        chartTraffic.update();
                    }
                }
            })
            .catch(e => console.error('Error fetching traffic monitor:', e));
    }, 1000);
};

window.stopTrafficMonitor = function() {
    if (trafficInterval) {
        clearInterval(trafficInterval);
        trafficInterval = null;
    }
};

window.refreshDetalles = function() {
    initDetallesModule();
};

window.rebootMikrotik = function(id) {
    Swal.fire({
        title: '¿Reiniciar Equipo?',
        text: "Esta acción reiniciará el dispositivo MikroTik inmediatamente.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, reiniciar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('controllers/MikrotikController.php?action=api_reboot&id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('Reiniciado', 'El equipo está reiniciando.', 'success');
                    } else {
                        Swal.fire('Error', data.error || 'Fallo al reiniciar', 'error');
                    }
                })
                .catch(err => Swal.fire('Error', 'Fallo de conexión', 'error'));
        }
    });
};

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
            if (data.status === 'success') {
                Swal.close();
                let blob = new Blob([data.content], { type: "text/plain;charset=utf-8" });
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
        })
        .catch(err => Swal.fire('Error', 'Fallo de red', 'error'));
};

window.initDetallesModule = initDetallesModule;

// Limpiar intervalos al salir de la vista
if (typeof window.clearEquiposDetallesIntervals !== 'function') {
    window.clearEquiposDetallesIntervals = function() {
        if (pingInterval) clearInterval(pingInterval);
        if (trafficInterval) clearInterval(trafficInterval);
    };
}
