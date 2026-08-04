let dashboardInterval;
let trafficRealtimeInterval;
let clockInterval;
let mikrotikDialsInterval;
let previousNodeStates = {};
let currentDashboardFilter = 'all';
let currentDashboardSearch = '';
let lastFetchedNodes = [];
let chartPingRealtime = null;

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

    // 3. Cargar datos generales
    loadDashboardData();

    if (dashboardInterval) clearInterval(dashboardInterval);
    // Refresh general cada 15 segundos
    dashboardInterval = setInterval(loadDashboardData, 15000);

    if (trafficRealtimeInterval) clearInterval(trafficRealtimeInterval);
    // Refresh tráfico en tiempo real cada 1.2 segundos (1200 ms)
    trafficRealtimeInterval = setInterval(fetchRealtimePings, 1200);
}

window.initDashboardModule = initDashboardModule;
window.loadDashboardData = loadDashboardData;

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

// Inicializar Chart.js para Ping a Google y Cloudflare unificado
function initRealtimePingCharts() {
    const ctx = document.getElementById('chart-ping-realtime');
    if (!ctx) return;

    let canvasCtx = ctx.getContext('2d');
    
    // Gradients for area fills
    let gradientGoogle = canvasCtx.createLinearGradient(0, 0, 0, 250);
    gradientGoogle.addColorStop(0, 'rgba(239, 68, 68, 0.15)');
    gradientGoogle.addColorStop(1, 'rgba(239, 68, 68, 0.0)');

    let gradientCloudflare = canvasCtx.createLinearGradient(0, 0, 0, 250);
    gradientCloudflare.addColorStop(0, 'rgba(245, 158, 11, 0.15)');
    gradientCloudflare.addColorStop(1, 'rgba(245, 158, 11, 0.0)');

    if (chartPingRealtime) chartPingRealtime.destroy();
    
    chartPingRealtime = new Chart(canvasCtx, {
        type: 'line',
        data: {
            labels: pingChartData.labels,
            datasets: [
                {
                    label: 'Google (8.8.8.8)',
                    data: pingChartData.google,
                    borderColor: '#ef4444',
                    backgroundColor: gradientGoogle,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 1,
                    pointHoverRadius: 4,
                    pointBackgroundColor: '#ef4444'
                },
                {
                    label: 'Cloudflare (1.1.1.1)',
                    data: pingChartData.cloudflare,
                    borderColor: '#f59e0b',
                    backgroundColor: gradientCloudflare,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 1,
                    pointHoverRadius: 4,
                    pointBackgroundColor: '#f59e0b'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: '#475569',
                        boxWidth: 12,
                        font: { size: 10 }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleColor: '#f8fafc',
                    bodyColor: '#f8fafc',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 9 }, maxTicksLimit: 10 }
                },
                y: {
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: { color: '#94a3b8', font: { size: 9 } },
                    beginAtZero: true
                }
            }
        }
    });
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

        if (chartPingRealtime) {
            pingChartData.labels.push(timeStr);
            pingChartData.google.push(pingGoogle);
            pingChartData.cloudflare.push(pingCloudflare);

            if (pingChartData.labels.length > 30) {
                pingChartData.labels.shift();
                pingChartData.google.shift();
                pingChartData.cloudflare.shift();
            }

            chartPingRealtime.update();
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

function loadMikrotikResourceDials() {
    // Función obsoleta en el nuevo diseño
}

// Cargar Datos NOC
function loadDashboardData() {
    fetch('controllers/MikrotikController.php?action=api_dashboard_noc')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const kpis = data.data.kpis;
                const nodos = data.data.nodos;

                // Actualizar Banners de disponibilidad
                const availEl = document.getElementById('kpi-available-count');
                const unavailEl = document.getElementById('kpi-unavailable-count');
                const warnEl = document.getElementById('kpi-warning-count');
                const totalEl = document.getElementById('kpi-total-count');

                if (availEl) availEl.innerText = kpis.online;
                if (unavailEl) unavailEl.innerText = kpis.offline;
                if (warnEl) warnEl.innerText = kpis.alertas;
                if (totalEl) totalEl.innerText = kpis.total;

                // Bordes de alerta si hay caídas/alertas (Estilo simple Bootstrap)
                const offlineCard = document.getElementById('kpi-offline-card-wrapper');
                if (offlineCard) {
                    if (kpis.offline > 0) {
                        offlineCard.classList.add('border', 'border-danger');
                    } else {
                        offlineCard.classList.remove('border', 'border-danger');
                    }
                }
                const warningCard = document.getElementById('kpi-warning-card-wrapper');
                if (warningCard) {
                    if (kpis.alertas > 0) {
                        warningCard.classList.add('border', 'border-warning');
                    } else {
                        warningCard.classList.remove('border', 'border-warning');
                    }
                }

                // Barras de Progreso CPU y RAM del Servidor Local
                const cpuVal = (!kpis.server_cpu || isNaN(parseInt(kpis.server_cpu))) ? 0 : Math.max(0, Math.min(100, parseInt(kpis.server_cpu)));
                const ramVal = (!kpis.server_ram || isNaN(parseInt(kpis.server_ram))) ? 0 : Math.max(0, Math.min(100, parseInt(kpis.server_ram)));

                const cpuText = document.getElementById('gauge-cpu-val');
                const cpuProgress = document.getElementById('progress-cpu-local');
                if (cpuText && cpuProgress) {
                    cpuText.innerText = `${cpuVal}%`;
                    cpuProgress.style.width = `${cpuVal}%`;
                    cpuProgress.className = `progress-bar ${cpuVal > 80 ? 'bg-danger' : (cpuVal > 50 ? 'bg-warning' : 'bg-success')}`;
                }

                const ramText = document.getElementById('gauge-ram-val');
                const ramProgress = document.getElementById('progress-ram-local');
                if (ramText && ramProgress) {
                    ramText.innerText = `${ramVal}%`;
                    ramProgress.style.width = `${ramVal}%`;
                    ramProgress.className = `progress-bar ${ramVal > 80 ? 'bg-danger' : (ramVal > 50 ? 'bg-warning' : 'bg-primary')}`;
                }

                // Calcular Top 3 MikroTiks por Carga CPU
                const topCpuContainer = document.getElementById('dashboard-top-cpu-container');
                if (topCpuContainer) {
                    const mikrotiksOnly = nodos.filter(n => n.tipo === 'mikrotik');
                    mikrotiksOnly.sort((a, b) => {
                        const cpuA = a.cpu_uso !== null ? parseInt(a.cpu_uso) : 0;
                        const cpuB = b.cpu_uso !== null ? parseInt(b.cpu_uso) : 0;
                        return cpuB - cpuA;
                    });
                    const top3 = mikrotiksOnly.slice(0, 3);
                    if (top3.length === 0) {
                        topCpuContainer.innerHTML = '<div class="text-center py-3 text-muted small">No hay nodos MikroTik registrados.</div>';
                    } else {
                        let topHtml = '';
                        top3.forEach(mk => {
                            const cpu = mk.cpu_uso || 0;
                            const barColor = cpu > 80 ? 'bg-danger' : (cpu > 50 ? 'bg-warning' : 'bg-success');
                            topHtml += `
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-semibold text-dark text-truncate" style="max-width: 170px;" title="${mk.nombre}"><i class="bi bi-router text-muted me-1"></i>${mk.nombre}</span>
                                    <span class="badge bg-light text-dark fw-bold" style="font-size: 10px;">${cpu}%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar ${barColor}" role="progressbar" style="width: ${cpu}%" aria-valuenow="${cpu}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            `;
                        });
                        topCpuContainer.innerHTML = topHtml;
                    }
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

                // Renderizar la lista de problemas activos
                renderActiveProblemsTable(nodos);

                const updateEl = document.getElementById('dashboard-last-update');
                if (updateEl) updateEl.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> Actualizado: ' + new Date().toLocaleTimeString();

                // Cargar estado del widget de servicios web y DNS
                loadDashboardServicesStatus();
            }
        }).catch(e => console.error("Error al cargar NOC:", e));
}

// Renderizar Tabla de Problemas Activos
function renderActiveProblemsTable(nodes) {
    const tbody = document.getElementById('problems-tbody');
    const badge = document.getElementById('problem-count-badge');
    if (!tbody) return;

    const problemNodes = nodes.filter(n => n.estado_noc === 'offline' || n.estado_noc === 'alerta');

        if (badge) {
            if (problemNodes.length === 0) {
                badge.innerText = 'Red Operativa';
                badge.className = 'badge bg-success';
            } else {
                badge.innerText = `${problemNodes.length} Incidentes`;
                badge.className = 'badge bg-danger';
            }
        }

    if (problemNodes.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                    <i class="bi bi-shield-fill-check text-success fs-2 d-block mb-2"></i>
                    <span class="fw-semibold">No hay incidentes activos en la red.</span>
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
            ? '<span class="badge bg-danger text-white"><i class="bi bi-x-circle-fill me-1"></i>Caído (DOWN)</span>'
            : '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill me-1"></i>Alerta / Latencia</span>';

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
                <td class="text-end pe-3">
                    <button class="btn btn-xs btn-outline-primary" onclick="${clickAction}" title="Ver Detalles">
                        <i class="bi bi-eye-fill me-1"></i> Ver
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
                if (res.data.length === 0) {
                    container.innerHTML = '<div class="text-center py-4 text-muted small">No hay servicios configurados.</div>';
                    return;
                }
                
                let html = '';
                res.data.forEach(s => {
                    let icon = 'bi-globe text-primary';
                    if (s.tipo === 'dns') icon = 'bi-diagram-2 text-info';
                    else if (s.tipo === 'puerto') icon = 'bi-ethernet text-secondary';

                    let ledClass = 'led-success';
                    let stateText = s.ultimo_ms ? `${s.ultimo_ms} ms` : 'UP';
                    if (s.estado_check === 'lento') {
                        ledClass = 'led-warning';
                        stateText = `${s.ultimo_ms} ms`;
                    } else if (s.estado_check === 'offline') {
                        ledClass = 'led-danger';
                        stateText = 'DOWN';
                    }

                    html += `
                    <div class="service-item-row d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center text-truncate me-2">
                            <i class="bi ${icon} me-2 fs-6"></i>
                            <span class="fw-semibold text-dark text-truncate" style="font-size: 13px;" title="${s.nombre}">${s.nombre}</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="font-monospace text-muted" style="font-size: 12px;">${stateText}</span>
                            <span class="led-indicator ${ledClass}" title="Estado: ${s.estado_check}"></span>
                        </div>
                    </div>
                    `;
                });

                container.innerHTML = html;
            }
        })
        .catch(e => console.error("Error al cargar resumen de servicios en dashboard:", e));
}
