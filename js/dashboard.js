let dashboardInterval;
let trafficRealtimeInterval;
let clockInterval;
let mikrotikDialsInterval;
let previousNodeStates = {};
let currentDashboardFilter = 'all';
let currentDashboardSearch = '';
let lastFetchedNodes = [];
let chartPingGoogle = null;
let chartPingCloudflare = null;

let pingChartData = {
    labels: [],
    google: [],
    cloudflare: []
};

function initDashboardModule() {
    // 1. Iniciar reloj digital
    startDigitalClock();

    // 2. Inicializar gráfica real-time de pings
    initRealtimePingCharts();

    // 3. Cargar datos generales y Diales de MikroTik desde la BD
    loadDashboardData();
    loadMikrotikResourceDials();

    if (dashboardInterval) clearInterval(dashboardInterval);
    // Refresh general cada 15 segundos
    dashboardInterval = setInterval(loadDashboardData, 15000);

    if (trafficRealtimeInterval) clearInterval(trafficRealtimeInterval);
    // Refresh tráfico en tiempo real cada 1.2 segundos (1200 ms)
    trafficRealtimeInterval = setInterval(fetchRealtimePings, 1200);

    if (mikrotikDialsInterval) clearInterval(mikrotikDialsInterval);
    // Refresh diales MikroTik desde BD cada 60 segundos (1 minuto)
    mikrotikDialsInterval = setInterval(loadMikrotikResourceDials, 60000);

    // Tooltip flotante
    if (!document.getElementById('hex-tooltip')) {
        const tt = document.createElement('div');
        tt.id = 'hex-tooltip';
        document.body.appendChild(tt);
    }
}

window.initDashboardModule = initDashboardModule;
window.loadDashboardData = loadDashboardData;
window.loadMikrotikResourceDials = loadMikrotikResourceDials;

// Reloj Digital Header
function startDigitalClock() {
    if (clockInterval) clearInterval(clockInterval);
    const updateClock = () => {
        const now = new Date();
        const clockEl = document.getElementById('noc-digital-clock');
        const dateEl = document.getElementById('noc-date-string');

        if (clockEl) {
            clockEl.innerText = now.toLocaleTimeString('es-MX', { hour12: true });
        }
        if (dateEl) {
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            dateEl.innerText = now.toLocaleDateString('es-MX', options);
        }
    };
    updateClock();
    clockInterval = setInterval(updateClock, 1000);
}

// Pantalla completa
window.toggleFullScreen = function () {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => {
            console.log(`Error attempting to enable fullscreen: ${err.message}`);
        });
    } else {
        document.exitFullscreen();
    }
};

// Inicializar Chart.js para Ping a Google y Cloudflare
function initRealtimePingCharts() {
    const ctxGoogle = document.getElementById('chart-ping-google');
    const ctxCloudflare = document.getElementById('chart-ping-cloudflare');

    if (ctxGoogle) {
        let canvasCtx = ctxGoogle.getContext('2d');
        let gradientGoogle = canvasCtx.createLinearGradient(0, 0, 0, 150);
        gradientGoogle.addColorStop(0, 'rgba(239, 68, 68, 0.4)');
        gradientGoogle.addColorStop(1, 'rgba(239, 68, 68, 0.05)');

        if (chartPingGoogle) chartPingGoogle.destroy();
        chartPingGoogle = new Chart(canvasCtx, {
            type: 'line',
            data: {
                labels: pingChartData.labels,
                datasets: [{
                    label: 'Latencia (ms)',
                    data: pingChartData.google,
                    borderColor: '#ef4444',
                    backgroundColor: gradientGoogle,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 1.5,
                    pointBackgroundColor: '#ef4444'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                scales: {
                    x: { display: false },
                    y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#64748b', font: { size: 9 } }, beginAtZero: true }
                }
            }
        });
    }

    if (ctxCloudflare) {
        let canvasCtx = ctxCloudflare.getContext('2d');
        let gradientCloudflare = canvasCtx.createLinearGradient(0, 0, 0, 150);
        gradientCloudflare.addColorStop(0, 'rgba(245, 158, 11, 0.4)');
        gradientCloudflare.addColorStop(1, 'rgba(245, 158, 11, 0.05)');

        if (chartPingCloudflare) chartPingCloudflare.destroy();
        chartPingCloudflare = new Chart(canvasCtx, {
            type: 'line',
            data: {
                labels: pingChartData.labels,
                datasets: [{
                    label: 'Latencia (ms)',
                    data: pingChartData.cloudflare,
                    borderColor: '#f59e0b',
                    backgroundColor: gradientCloudflare,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 1.5,
                    pointBackgroundColor: '#f59e0b'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                scales: {
                    x: { display: false },
                    y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#64748b', font: { size: 9 } }, beginAtZero: true }
                }
            }
        });
    }
}

function initHistoricoCharts() {
    const ctxCpu = document.getElementById('chart-historico-cpu');
    const ctxRam = document.getElementById('chart-historico-ram');

    if (ctxCpu) {
        if (chartHistCpu) chartHistCpu.destroy();
        chartHistCpu = new Chart(ctxCpu.getContext('2d'), {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'CPU %', data: [], borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', borderWidth: 2, fill: true, tension: 0.3, pointRadius: 1 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#64748b', font: { size: 9 }, maxRotation: 0 } },
                    y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#64748b', font: { size: 9 } }, beginAtZero: true, suggestedMax: 100 }
                }
            }
        });
    }

    if (ctxRam) {
        if (chartHistRam) chartHistRam.destroy();
        chartHistRam = new Chart(ctxRam.getContext('2d'), {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'RAM %', data: [], borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', borderWidth: 2, fill: true, tension: 0.3, pointRadius: 1 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#64748b', font: { size: 9 }, maxRotation: 0 } },
                    y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#64748b', font: { size: 9 } }, beginAtZero: true, suggestedMax: 100 }
                }
            }
        });
    }
}

// Consultar Pings cada 1.2s
async function fetchRealtimePings() {
    const timeStr = new Date().toLocaleTimeString('es-MX', { hour12: false });

    try {
        const [resGoogle, resCloudflare] = await Promise.all([
            fetch('controllers/MikrotikController.php?action=api_ping_google'),
            fetch('controllers/MikrotikController.php?action=api_ping_cloudflare')
        ]);

        const dataGoogle = await resGoogle.json();
        const dataCloudflare = await resCloudflare.json();

        const pingGoogle = (dataGoogle.status === 'success') ? dataGoogle.ms : 0;
        const pingCloudflare = (dataCloudflare.status === 'success') ? dataCloudflare.ms : 0;

        const valGoogleEl = document.getElementById('live-ping-google-val');
        const valCloudflareEl = document.getElementById('live-ping-cloudflare-val');

        if (valGoogleEl) valGoogleEl.innerText = `${pingGoogle} ms`;
        if (valCloudflareEl) valCloudflareEl.innerText = `${pingCloudflare} ms`;

        if (chartPingGoogle && chartPingCloudflare) {
            pingChartData.labels.push(timeStr);
            pingChartData.google.push(pingGoogle);
            pingChartData.cloudflare.push(pingCloudflare);

            if (pingChartData.labels.length > 30) {
                pingChartData.labels.shift();
                pingChartData.google.shift();
                pingChartData.cloudflare.shift();
            }

            chartPingGoogle.update();
            chartPingCloudflare.update();
        }
    } catch (e) {
        console.error("Error fetching live pings", e);
    }
}

window.setDashboardFilter = function (filter) {
    currentDashboardFilter = filter;

    const buttons = document.querySelectorAll('#wisp-filter-buttons button');
    buttons.forEach(btn => {
        if (btn.dataset.filter === filter) {
            btn.classList.add('active', 'btn-light');
            btn.classList.remove('btn-outline-light');
        } else {
            btn.classList.remove('active', 'btn-light');
            btn.classList.add('btn-outline-light');
        }
    });

    renderFilteredNodes();
};

window.onDashboardSearchChange = function (query) {
    currentDashboardSearch = (query || '').toLowerCase().trim();
    renderFilteredNodes();
};

// Cargar Diales Medidores (0-100%) por MikroTik desde la BD
function loadMikrotikResourceDials() {
    const container = document.getElementById('mikrotik-dials-container');
    if (!container) return;

    fetch('controllers/MikrotikController.php?action=api_mikrotik_resources_bd')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data) {
                if (res.data.length === 0) {
                    container.innerHTML = '<div class="col-12 text-center text-muted small py-4">No hay MikroTiks registrados en la BD.</div>';
                    return;
                }
                
                let html = '';
                res.data.forEach(mk => {
                    const cpu = (mk.cpu_uso !== null && !isNaN(parseInt(mk.cpu_uso))) ? Math.max(0, Math.min(100, parseInt(mk.cpu_uso))) : 0;
                    const ram = (mk.ram_uso !== null && !isNaN(parseInt(mk.ram_uso))) ? Math.max(0, Math.min(100, parseInt(mk.ram_uso))) : 0;
                    const lastUpdate = mk.ultima_actualizacion ? mk.ultima_actualizacion.split(' ')[1] : '--:--';

                    const cpuColor = cpu > 85 ? '#ef4444' : (cpu > 60 ? '#f59e0b' : '#10b981');
                    const ramColor = ram > 85 ? '#ef4444' : (ram > 60 ? '#f59e0b' : '#3b82f6');

                    const cpuOffset = 125.6 - (125.6 * (cpu / 100));
                    const ramOffset = 125.6 - (125.6 * (ram / 100));

                    const cpuAngle = -90 + (cpu * 1.8);
                    const ramAngle = -90 + (ram * 1.8);

                    html += `
                    <div class="col-12 col-md-6">
                        <div class="border rounded-3 p-3 bg-light shadow-sm h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom border-secondary border-opacity-10">
                                <div>
                                    <span class="fw-bold text-dark d-block" style="font-size: 13px;">
                                        <i class="bi bi-router text-primary me-1"></i>${mk.nombre}
                                    </span>
                                    <small class="text-muted font-monospace" style="font-size: 10px;">${mk.ip_address}</small>
                                </div>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size: 9px;" title="Última lectura BD">
                                    <i class="bi bi-clock me-1"></i>${lastUpdate}
                                </span>
                            </div>

                            <div class="d-flex justify-content-around align-items-center">
                                <!-- CPU Meter -->
                                <div class="d-flex flex-column align-items-center">
                                    <div class="speedometer-wrapper position-relative my-1" style="width: 80px; height: 45px;">
                                        <svg viewBox="0 0 100 55" class="speedometer-svg">
                                            <path d="M 10 50 A 40 40 0 0 1 90 50" fill="none" stroke="#e2e8f0" stroke-width="8" stroke-linecap="round" />
                                            <path d="M 10 50 A 40 40 0 0 1 90 50" fill="none" stroke="${cpuColor}" stroke-width="8" stroke-dasharray="125.6" stroke-dashoffset="${cpuOffset}" stroke-linecap="round" style="transition: stroke-dashoffset 0.8s ease;" />
                                            <g style="transition: transform 0.8s ease; transform-origin: 50px 50px; transform: rotate(${cpuAngle}deg);">
                                                <polygon points="50,12 46.5,50 53.5,50" fill="#1e293b" />
                                                <circle cx="50" cy="50" r="5" fill="${cpuColor}" stroke="#ffffff" stroke-width="1.5" />
                                            </g>
                                        </svg>
                                        <div class="speedometer-val text-dark fw-bold" style="font-size: 11px;">${cpu}%</div>
                                    </div>
                                    <small class="text-muted text-uppercase fw-semibold" style="font-size: 9px;">CPU</small>
                                </div>

                                <!-- RAM Meter -->
                                <div class="d-flex flex-column align-items-center">
                                    <div class="speedometer-wrapper position-relative my-1" style="width: 80px; height: 45px;">
                                        <svg viewBox="0 0 100 55" class="speedometer-svg">
                                            <path d="M 10 50 A 40 40 0 0 1 90 50" fill="none" stroke="#e2e8f0" stroke-width="8" stroke-linecap="round" />
                                            <path d="M 10 50 A 40 40 0 0 1 90 50" fill="none" stroke="${ramColor}" stroke-width="8" stroke-dasharray="125.6" stroke-dashoffset="${ramOffset}" stroke-linecap="round" style="transition: stroke-dashoffset 0.8s ease;" />
                                            <g style="transition: transform 0.8s ease; transform-origin: 50px 50px; transform: rotate(${ramAngle}deg);">
                                                <polygon points="50,12 46.5,50 53.5,50" fill="#1e293b" />
                                                <circle cx="50" cy="50" r="5" fill="${ramColor}" stroke="#ffffff" stroke-width="1.5" />
                                            </g>
                                        </svg>
                                        <div class="speedometer-val text-dark fw-bold" style="font-size: 11px;">${ram}%</div>
                                    </div>
                                    <small class="text-muted text-uppercase fw-semibold" style="font-size: 9px;">RAM</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    `;
                });
                container.innerHTML = html;
            }
        })
        .catch(e => console.error("Error al cargar diales de MikroTik:", e));
}

// Cargar Datos NOC
function loadDashboardData() {
    fetch('controllers/MikrotikController.php?action=api_dashboard_noc')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const kpis = data.data.kpis;
                const nodos = data.data.nodos;

                // Actualizar Banners Estilo Zabbix Host Availability
                const availEl = document.getElementById('kpi-available-count');
                const unavailEl = document.getElementById('kpi-unavailable-count');
                const warnEl = document.getElementById('kpi-warning-count');
                const totalEl = document.getElementById('kpi-total-count');

                if (availEl) availEl.innerText = kpis.online;
                if (unavailEl) unavailEl.innerText = kpis.offline;
                if (warnEl) warnEl.innerText = kpis.alertas;
                if (totalEl) totalEl.innerText = kpis.total;

                // Actualizar Salud Global Banner
                const healthBanner = document.getElementById('global-health-status');
                if (healthBanner) {
                    if (kpis.offline > 0) {
                        healthBanner.innerText = `CRÍTICO (${kpis.offline} CAÍDOS)`;
                        healthBanner.className = 'fw-bold fs-6 text-danger';
                    } else if (kpis.alertas > 0) {
                        healthBanner.innerText = `ALERTA (${kpis.alertas} ADVERTENCIAS)`;
                        healthBanner.className = 'fw-bold fs-6 text-warning';
                    } else {
                        healthBanner.innerText = `OPTIMO (100% OPERATIVO)`;
                        healthBanner.className = 'fw-bold fs-6 text-success';
                    }
                }

                // Gauge CPU y RAM del Servidor Local
                const cpuVal = (!kpis.server_cpu || isNaN(parseInt(kpis.server_cpu))) ? 0 : Math.max(0, Math.min(100, parseInt(kpis.server_cpu)));
                const ramVal = (!kpis.server_ram || isNaN(parseInt(kpis.server_ram))) ? 0 : Math.max(0, Math.min(100, parseInt(kpis.server_ram)));

                const cpuPath = document.getElementById('gauge-cpu-path');
                const cpuNeedle = document.getElementById('gauge-cpu-needle-group');
                const cpuText = document.getElementById('gauge-cpu-val');

                if (cpuPath && cpuNeedle && cpuText) {
                    const offset = 125.6 - (125.6 * (cpuVal / 100));
                    cpuPath.style.strokeDashoffset = offset;
                    cpuPath.style.stroke = cpuVal > 80 ? '#ef4444' : (cpuVal > 50 ? '#f59e0b' : '#10b981');

                    const angle = -90 + (cpuVal * 1.8);
                    cpuNeedle.style.transform = `rotate(${angle}deg)`;
                    cpuText.innerText = `${cpuVal}%`;
                }

                const ramPath = document.getElementById('gauge-ram-path');
                const ramNeedle = document.getElementById('gauge-ram-needle-group');
                const ramText = document.getElementById('gauge-ram-val');

                if (ramPath && ramNeedle && ramText) {
                    const offset = 125.6 - (125.6 * (ramVal / 100));
                    ramPath.style.strokeDashoffset = offset;
                    ramPath.style.stroke = ramVal > 80 ? '#ef4444' : (ramVal > 50 ? '#f59e0b' : '#3b82f6');

                    const angle = -90 + (ramVal * 1.8);
                    ramNeedle.style.transform = `rotate(${angle}deg)`;
                    ramText.innerText = `${ramVal}%`;
                }

                // Ordenar nodos (Caídos y Alertas primero)
                nodos.sort((a, b) => {
                    let getPriority = (node) => {
                        if (node.estado_noc === 'offline') return 3;
                        if (node.estado_noc === 'alerta') return 2;
                        return 1;
                    };
                    let pA = getPriority(a);
                    let pB = getPriority(b);
                    if (pA !== pB) return pB - pA;
                    return a.nombre.localeCompare(b.nombre);
                });

                // Detectar caídas nuevas
                let newlyOfflineNodes = [];
                nodos.forEach(n => {
                    let prev = previousNodeStates[n.id];
                    if (prev && prev !== 'offline' && n.estado_noc === 'offline') {
                        newlyOfflineNodes.push(n.nombre);
                    }
                    previousNodeStates[n.id] = n.estado_noc;
                });

                lastFetchedNodes = nodos;

                // Renderizar la lista de problemas activos (Tabla Zabbix)
                renderActiveProblemsTable(nodos);

                if (newlyOfflineNodes.length > 0) {
                    if (newlyOfflineNodes.length === 1) {
                        showCriticalAlert(`CAÍDO: ${newlyOfflineNodes[0]}`);
                    } else {
                        showCriticalAlert(`CAÍDOS: ${newlyOfflineNodes.join(', ')}`);
                    }
                }

                const updateEl = document.getElementById('dashboard-last-update');
                if (updateEl) updateEl.innerHTML = '<i class="bi bi-check-circle text-success me-1"></i> Actualizado: ' + new Date().toLocaleTimeString();

                // Cargar estado del widget de servicios web y DNS
                loadDashboardServicesStatus();
            }
        }).catch(e => console.error("Error al cargar NOC:", e));
}

// Renderizar Tabla de Problemas Activos (Estilo Zabbix Image 3)
function renderActiveProblemsTable(nodes) {
    const tbody = document.getElementById('problems-tbody');
    const badge = document.getElementById('problem-count-badge');
    if (!tbody) return;

    const problemNodes = nodes.filter(n => n.estado_noc === 'offline' || n.estado_noc === 'alerta');

    if (badge) {
        badge.innerText = `${problemNodes.length} Problemas`;
        badge.className = problemNodes.length > 0 ? 'badge bg-danger text-white blink-badge' : 'badge bg-success text-white';
    }

    if (problemNodes.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                    <i class="bi bi-shield-check text-success fs-3 d-block mb-1"></i>
                    No hay incidentes ni problemas activos en la red.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = '';

    problemNodes.forEach(n => {
        const isOffline = n.estado_noc === 'offline';
        const trClass = isOffline ? 'problem-row-down' : 'problem-row-alert';

        let severityBadge = isOffline
            ? '<span class="badge bg-danger text-white blink-badge"><i class="bi bi-x-octagon me-1"></i>DESCONECTADO (DOWN)</span>'
            : '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>LATENCIA ALTA / RECURSOS</span>';

        const pingText = n.ultimo_ping !== null ? `${n.ultimo_ping} ms` : 'OFFLINE';

        const clickAction = n.tipo === 'equipo'
            ? `loadView('equipos/detalles', {id: ${n.id}})`
            : `loadView('mikrotik/detalles', {id: ${n.id}})`;

        tbody.innerHTML += `
            <tr class="${trClass}">
                <td class="fw-bold text-dark">
                    <i class="bi ${n.tipo === 'mikrotik' ? 'bi-router' : 'bi-broadcast-pin'} me-1 text-primary"></i>
                    ${n.nombre}
                </td>
                <td class="font-monospace text-muted">${n.ip_address}</td>
                <td>${severityBadge}</td>
                <td class="font-monospace fw-bold ${isOffline ? 'text-danger' : 'text-warning'}">${pingText}</td>
                <td>
                    <button class="btn btn-xs btn-outline-primary" onclick="${clickAction}" title="Ver Detalles">
                        <i class="bi bi-eye me-1"></i> Ver
                    </button>
                </td>
            </tr>
        `;
    });
}

function renderFilteredNodes() {
    const grid = document.getElementById('noc-grid');
    if (!grid) return;

    let filtered = lastFetchedNodes.filter(n => {
        if (currentDashboardFilter === 'offline' && n.estado_noc !== 'offline') return false;
        if (currentDashboardFilter === 'alerta' && n.estado_noc !== 'alerta') return false;
        if (currentDashboardFilter === 'online' && n.estado_noc !== 'online') return false;

        if (currentDashboardSearch) {
            const nom = (n.nombre || '').toLowerCase();
            const ip = (n.ip_address || '').toLowerCase();
            if (!nom.includes(currentDashboardSearch) && !ip.includes(currentDashboardSearch)) {
                return false;
            }
        }

        return true;
    });

    if (filtered.length === 0) {
        grid.innerHTML = `
            <div class="text-center text-muted py-5 w-100">
                <i class="bi bi-search fs-3 text-muted d-block mb-1"></i>
                Sin coincidencias en los filtros.
            </div>
        `;
        return;
    }

    renderColmenaView(grid, filtered);
}

// Renderizado de Matriz Colmena Hexagonal (Estilo Zabbix Light)
function renderColmenaView(grid, nodes) {
    grid.className = 'wisp-colmena-container';

    const containerWidth = grid.clientWidth || 500;
    const hexWidth = 100;
    const itemsPerRow = Math.max(2, Math.floor(containerWidth / hexWidth));

    let html = '<div class="hex-grid-wrapper">';
    let currentRowNodes = [];
    let rowIdx = 0;

    nodes.forEach((n, index) => {
        currentRowNodes.push(n);
        if (currentRowNodes.length === itemsPerRow || index === nodes.length - 1) {
            const isOddRow = (rowIdx % 2 !== 0);
            const rowStyle = isOddRow ? 'style="padding-left: 52px;"' : '';

            html += `<div class="hex-row" ${rowStyle}>`;
            currentRowNodes.forEach(node => {
                html += createHexNodeHtml(node);
            });
            html += `</div>`;

            currentRowNodes = [];
            rowIdx++;
        }
    });

    html += '</div>';
    grid.innerHTML = html;
}

// Generador HTML de Hexágono Light estilo Zabbix
function createHexNodeHtml(n) {
    let statusClass = 'online';
    let statusText = 'UP';

    if (n.estado_noc === 'offline') {
        statusClass = 'offline';
        statusText = 'DOWN';
    } else if (n.estado_noc === 'alerta') {
        statusClass = 'alerta';
        statusText = 'ALERTA';
    }

    const pingMs = n.ultimo_ping !== null ? n.ultimo_ping : null;
    let badgeText = pingMs !== null ? `${pingMs}ms` : 'OFF';

    const clickAction = n.tipo === 'equipo'
        ? `loadView('equipos/detalles', {id: ${n.id}})`
        : `loadView('mikrotik/detalles', {id: ${n.id}})`;

    const safeName = (n.nombre || '').replace(/"/g, '&quot;');
    const pingHistJson = JSON.stringify(n.ping_history || []).replace(/"/g, '&quot;');

    return `
        <div class="hex-node ${statusClass}" 
             onclick="${clickAction}"
             onmouseenter="showHexTooltip(event, this)" 
             onmouseleave="hideHexTooltip()"
             data-nombre="${safeName}"
             data-ip="${n.ip_address}"
             data-estado="${statusText}"
             data-ping="${badgeText}"
             data-cpu="${n.cpu_uso || 0}%"
             data-ram="${n.ram_uso || 0}%"
             data-trafico="${n.trafico_mbps || 'N/A'} Mbps"
             data-tipo="${n.tipo}"
             data-history="${pingHistJson}">
             
            <div class="hex-inner">
                <div class="hex-title" title="${safeName}">${safeName}</div>
                <div class="hex-status">${statusText}</div>
                <div class="hex-ping">${badgeText}</div>
            </div>
        </div>
    `;
}

// Función para limpiar todos los intervalos del Dashboard NOC
window.clearDashboardIntervals = function () {
    if (dashboardInterval) clearInterval(dashboardInterval);
    if (trafficRealtimeInterval) clearInterval(trafficRealtimeInterval);
    if (clockInterval) clearInterval(clockInterval);
    if (mikrotikDialsInterval) clearInterval(mikrotikDialsInterval);
    hideHexTooltip();
};

// Tooltip flotante Zabbix Style
let hexTooltipChart = null;

window.showHexTooltip = function (e, element) {
    const tt = document.getElementById('hex-tooltip');
    if (!tt) return;

    const ds = element.dataset;
    const isOffline = ds.estado === 'DOWN';
    const isAlerta = ds.estado === 'ALERTA';
    const statusColor = isOffline ? 'text-danger' : (isAlerta ? 'text-warning' : 'text-success');

    let extraHtml = '';
    if (ds.tipo !== 'equipo') {
        extraHtml = `
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">CPU:</span> 
                <span class="fw-bold text-dark">${ds.cpu}</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
                <span class="text-muted">RAM:</span> 
                <span class="fw-bold text-dark">${ds.ram}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Tráfico:</span> 
                <span class="text-primary fw-bold">${ds.trafico}</span>
            </div>
        `;
    }

    tt.innerHTML = `
        <div class="fw-bold border-bottom border-secondary border-opacity-25 pb-1 mb-2 text-primary">
            <i class="bi bi-router me-1"></i> ${ds.nombre}
        </div>
        <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">Estado:</span> 
            <span class="fw-bold ${statusColor}">${ds.estado}</span>
        </div>
        <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">IP:</span> 
            <span class="font-monospace text-dark">${ds.ip}</span>
        </div>
        <div class="d-flex justify-content-between mb-1">
            <span class="text-muted">Ping:</span> 
            <span class="fw-bold font-monospace text-dark">${ds.ping}</span>
        </div>
        ${extraHtml}
        <div style="height: 50px; width: 100%; margin-top: 6px;">
            <canvas id="hex-tooltip-chart"></canvas>
        </div>
    `;

    tt.style.display = 'block';

    const rect = element.getBoundingClientRect();
    tt.style.left = (rect.left + rect.width / 2 + window.scrollX) + 'px';
    tt.style.top = (rect.top + window.scrollY - 10) + 'px';

    let history = [];
    try { history = JSON.parse(ds.history || '[]'); } catch (e) { }

    const ctx = document.getElementById('hex-tooltip-chart').getContext('2d');
    if (hexTooltipChart) hexTooltipChart.destroy();

    const chartColor = isOffline ? '#dc3545' : (isAlerta ? '#f59e0b' : '#10b981');

    hexTooltipChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: history.map((_, i) => ''),
            datasets: [{
                data: history,
                borderColor: chartColor,
                backgroundColor: chartColor + '33',
                borderWidth: 2,
                pointRadius: 0,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: {
                x: { display: false },
                y: { display: false, min: 0 }
            }
        }
    });
};

window.hideHexTooltip = function () {
    const tt = document.getElementById('hex-tooltip');
    if (tt) tt.classList.remove('active');
};

function loadDashboardServicesStatus() {
    const container = document.getElementById('dashboard-services-container');
    if (!container) return;

    fetch('api.php?action=servicios_resumen_dashboard')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data) {
                let html = '';
                res.data.forEach(s => {
                    let icon = 'bi-globe';
                    if (s.tipo === 'dns') icon = 'bi-diagram-2';
                    else if (s.tipo === 'puerto') icon = 'bi-ethernet';

                    let badgeClass = 'bg-success-subtle text-success border-success';
                    let stateText = s.ultimo_ms ? `${s.ultimo_ms}ms` : 'UP';
                    if (s.estado_check === 'lento') {
                        badgeClass = 'bg-warning-subtle text-warning-emphasis border-warning';
                    } else if (s.estado_check === 'offline') {
                        badgeClass = 'bg-danger-subtle text-danger border-danger';
                        stateText = 'DOWN';
                    }

                    html += `
                    <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                        <div class="p-2 rounded-3 border ${badgeClass} d-flex align-items-center justify-content-between">
                            <div class="text-truncate me-1">
                                <i class="bi ${icon} me-1"></i>
                                <span class="fw-bold small text-truncate d-inline-block" style="max-width: 90px;" title="${s.nombre}">${s.nombre}</span>
                            </div>
                            <span class="badge rounded-pill ${s.estado_check === 'offline' ? 'bg-danger' : (s.estado_check === 'lento' ? 'bg-warning text-dark' : 'bg-success')}" style="font-size: 10px;">${stateText}</span>
                        </div>
                    </div>
                `;
                });

                container.innerHTML = html || '<div class="col-12 text-center text-muted small">No hay servicios configurados.</div>';
            }
        })
        .catch(e => console.error("Error al cargar resumen de servicios en dashboard:", e));
}
