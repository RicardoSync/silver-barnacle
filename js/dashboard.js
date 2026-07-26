let dashboardInterval;
let previousNodeStates = {};
let criticalAlertTimeout;
let currentDashboardFilter = 'all';
let currentDashboardSearch = '';
let lastFetchedNodes = [];

window.showCriticalAlert = function(text) {
    const overlay = document.getElementById('critical-alert-overlay');
    const textElement = document.getElementById('critical-alert-text');
    if (overlay && textElement) {
        textElement.innerText = text;
        overlay.classList.add('active');
        
        if (criticalAlertTimeout) clearTimeout(criticalAlertTimeout);
        criticalAlertTimeout = setTimeout(() => {
            overlay.classList.remove('active');
        }, 3000);
    }
};

window.toggleFullScreen = function() {
    if (!document.fullscreenElement && !document.webkitFullscreenElement) {
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen();
        } else if (document.documentElement.webkitRequestFullscreen) {
            document.documentElement.webkitRequestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        }
    }
};

function initDashboardModule() {
    loadDashboardData();
    
    if(dashboardInterval) clearInterval(dashboardInterval);
    // Refresh cada 30 segundos
    dashboardInterval = setInterval(loadDashboardData, 30000);

    // Overlay de alerta crítica si no existe
    if (!document.getElementById('critical-alert-overlay')) {
        const alertOverlay = document.createElement('div');
        alertOverlay.id = 'critical-alert-overlay';
        alertOverlay.innerHTML = `
            <i class="bi bi-exclamation-octagon-fill"></i>
            <h1 id="critical-alert-text">CAÍDO: NODO</h1>
        `;
        document.body.appendChild(alertOverlay);
    }
}

window.initDashboardModule = initDashboardModule;
window.loadDashboardData = loadDashboardData;

window.setDashboardFilter = function(filter) {
    currentDashboardFilter = filter;
    
    // Actualizar botones de filtro
    const buttons = document.querySelectorAll('#wisp-filter-buttons button');
    buttons.forEach(btn => {
        if (btn.dataset.filter === filter) {
            btn.classList.add('active', 'btn-primary', 'text-white');
            btn.classList.remove('btn-outline-secondary');
        } else {
            btn.classList.remove('active', 'btn-primary', 'text-white');
        }
    });
    
    renderFilteredNodes();
};

window.onDashboardSearchChange = function(query) {
    currentDashboardSearch = (query || '').toLowerCase().trim();
    renderFilteredNodes();
};

function renderFilteredNodes() {
    const grid = document.getElementById('noc-grid');
    if (!grid) return;
    
    let filtered = lastFetchedNodes.filter(n => {
        // Filtrado por categoría / estado
        if (currentDashboardFilter === 'offline' && n.estado_noc !== 'offline') return false;
        if (currentDashboardFilter === 'alerta' && n.estado_noc !== 'alerta') return false;
        if (currentDashboardFilter === 'online' && n.estado_noc !== 'online') return false;
        if (currentDashboardFilter === 'mikrotik' && n.tipo !== 'mikrotik') return false;
        if (currentDashboardFilter === 'equipo' && n.tipo !== 'equipo') return false;
        
        // Filtrado por búsqueda de texto (nombre o IP)
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
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-search fs-1 text-muted opacity-50"></i>
                <h5 class="mt-2 fw-bold">No se encontraron dispositivos</h5>
                <p class="small mb-0">Intenta cambiar los filtros o el término de búsqueda.</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = '';
    
    filtered.forEach(n => {
        let cardStatusClass = 'online';
        let statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2"><i class="bi bi-dot fs-6"></i> En Línea</span>';
        let ledClass = 'led-online';
        let iconClass = 'bi-router-fill';
        let typeBadge = '<span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2">MikroTik</span>';
        
        if (n.tipo === 'equipo') {
            typeBadge = '<span class="badge bg-purple-subtle text-purple border border-purple-subtle rounded-pill px-2" style="color: #6f42c1; background-color: #f3ebf9; border-color: #d8b4fe !important;">Equipo / Antena</span>';
            let com = (n.comunidad_snmp || '').toLowerCase();
            if (com.includes('antena') || com.includes('ptp') || com.includes('torre') || com.includes('ap')) {
                iconClass = 'bi-broadcast-pin';
            } else if (com.includes('cliente') || com.includes('usuario')) {
                iconClass = 'bi-person-badge';
            } else {
                iconClass = 'bi-hdd-network-fill';
            }
        }

        if (n.estado_noc === 'offline') {
            cardStatusClass = 'offline';
            ledClass = 'led-offline';
            statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Caído</span>';
        } else if (n.estado_noc === 'alerta') {
            cardStatusClass = 'alerta';
            ledClass = 'led-alerta';
            statusBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2"><i class="bi bi-exclamation-circle-fill me-1"></i> Alerta</span>';
        }

        // Animación si acaba de cambiar de estado
        let prev = previousNodeStates[n.id];
        if (prev) {
            if (prev !== 'offline' && n.estado_noc === 'offline') {
                cardStatusClass += ' just-offline-anim';
            } else if (prev === 'offline' && n.estado_noc === 'online') {
                cardStatusClass += ' just-online-anim';
            }
        }

        const pingMs = n.ultimo_ping !== null ? n.ultimo_ping : null;
        let pingBadgeClass = 'bg-success';
        let pingText = pingMs !== null ? `${pingMs} ms` : 'OFF';

        if (pingMs === null || n.estado_noc === 'offline') {
            pingBadgeClass = 'bg-danger';
            pingText = 'OFF';
        } else if (pingMs > 150) {
            pingBadgeClass = 'bg-warning text-dark';
        } else if (pingMs > 80) {
            pingBadgeClass = 'bg-info text-dark';
        }

        let cpuValue = n.cpu_uso !== null ? parseInt(n.cpu_uso) : 0;
        let cpuBarColor = cpuValue > 80 ? 'bg-danger' : (cpuValue > 50 ? 'bg-warning' : 'bg-success');
        let cpuHtml = n.tipo === 'mikrotik' && n.estado_noc !== 'offline' ? `
            <div class="mt-2">
                <div class="d-flex justify-content-between align-items-center small text-muted mb-1" style="font-size: 11px;">
                    <span>CPU:</span>
                    <span class="fw-bold text-dark">${cpuValue}%</span>
                </div>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar ${cpuBarColor}" role="progressbar" style="width: ${cpuValue}%"></div>
                </div>
            </div>
        ` : '';

        let trafficHtml = n.tipo === 'mikrotik' && n.estado_noc !== 'offline' ? `
            <div class="d-flex justify-content-between align-items-center small text-muted mt-2" style="font-size: 11px;">
                <span>Tráfico:</span>
                <span class="fw-bold text-primary">${n.trafico_mbps} Mbps</span>
            </div>
        ` : '';

        const clickAction = n.tipo === 'equipo' 
            ? `loadView('equipos/detalles', {id: ${n.id}})`
            : `loadView('mikrotik/detalles', {id: ${n.id}})`;

        const safeName = (n.nombre || '').replace(/"/g, '&quot;');

        grid.innerHTML += `
            <div class="wisp-card-col">
                <div class="wisp-node-card ${cardStatusClass}" onclick="${clickAction}">
                    <div class="wisp-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <span class="wisp-led ${ledClass}"></span>
                            <i class="bi ${iconClass} wisp-icon"></i>
                            <div class="wisp-card-title text-truncate" title="${safeName}">${safeName}</div>
                        </div>
                        <div>${typeBadge}</div>
                    </div>
                    
                    <div class="wisp-card-body">
                        <div class="d-flex justify-content-between align-items-center my-2">
                            <span class="text-muted font-monospace small" style="font-size: 12px;">
                                <i class="bi bi-hdd-network me-1"></i>${n.ip_address}
                            </span>
                            <span class="badge ${pingBadgeClass} font-monospace shadow-sm" style="font-size: 11px;">
                                ${pingText}
                            </span>
                        </div>

                        ${cpuHtml}
                        ${trafficHtml}
                    </div>

                    <div class="wisp-card-footer">
                        <div>${statusBadge}</div>
                        <div class="wisp-action-link">
                            Detalles <i class="bi bi-arrow-right-short"></i>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
}

function loadDashboardData() {
    fetch('controllers/MikrotikController.php?action=api_dashboard_noc')
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            const kpis = data.data.kpis;
            const nodos = data.data.nodos;
            
            document.getElementById('kpi-total').innerText = kpis.total;
            document.getElementById('kpi-online').innerText = kpis.online;
            document.getElementById('kpi-offline').innerText = kpis.offline;
            document.getElementById('kpi-alertas').innerText = kpis.alertas;
            
            // Ordenar nodos para que los caídos/alertados aparezcan primero
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

            let newlyOfflineNodes = [];
            nodos.forEach(n => {
                let prev = previousNodeStates[n.id];
                if (prev && prev !== 'offline' && n.estado_noc === 'offline') {
                    newlyOfflineNodes.push(n.nombre);
                }
                previousNodeStates[n.id] = n.estado_noc;
            });

            lastFetchedNodes = nodos;
            renderFilteredNodes();

            if (newlyOfflineNodes.length > 0) {
                if (newlyOfflineNodes.length === 1) {
                    showCriticalAlert(`CAÍDO: ${newlyOfflineNodes[0]}`);
                } else {
                    showCriticalAlert(`CAÍDOS: ${newlyOfflineNodes.join(', ')}`);
                }
            }

            document.getElementById('dashboard-last-update').innerHTML = '<i class="bi bi-check-circle text-success me-1"></i> Actualizado: ' + new Date().toLocaleTimeString();
        }
    }).catch(e => console.error("Error al cargar NOC:", e));
}

// Interceptar loadView para limpiar el intervalo cuando salimos de la vista
const origLoadViewDB = window.loadView;
window.loadView = function(viewName, params = null) {
    if(dashboardInterval) clearInterval(dashboardInterval);
    hideHexTooltip();
    origLoadViewDB(viewName, params);
}

// Tooltip functions for Honeycomb Grid
let hexTooltipChart = null;

window.showHexTooltip = function(e, element) {
    const tt = document.getElementById('hex-tooltip');
    if (!tt) return;
    
    const ds = element.dataset;
    const isOffline = ds.estado === 'Offline';
    const isAlerta = ds.estado === 'Alerta';
    const statusColor = isOffline ? 'text-danger' : (isAlerta ? 'text-warning' : 'text-success');
    const pingVal = parseInt(ds.ping);
    const pingColor = (!isNaN(pingVal) && pingVal > 150) ? 'text-warning' : '';
    
    let extraHtml = '';
    if (ds.tipo !== 'equipo') {
        extraHtml = `
            <div class="d-flex justify-content-between mb-1">
                <span class="text-white-50">CPU:</span> 
                <span>${ds.cpu}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-white-50">Tráfico:</span> 
                <span class="text-info">${ds.trafico}</span>
            </div>
        `;
    } else {
        extraHtml = `<div class="mb-2"></div>`;
    }
    
    tt.innerHTML = `
        <div class="fw-bold border-bottom border-secondary pb-1 mb-2 text-info">
            <i class="bi bi-router"></i> ${ds.nombre}
        </div>
        <div class="d-flex justify-content-between mb-1">
            <span class="text-white-50">Estado:</span> 
            <span class="fw-bold ${statusColor}">${ds.estado}</span>
        </div>
        <div class="d-flex justify-content-between mb-1">
            <span class="text-white-50">IP:</span> 
            <span>${ds.ip}</span>
        </div>
        <div class="d-flex justify-content-between mb-1">
            <span class="text-white-50">Ping:</span> 
            <span class="fw-bold ${pingColor}">${ds.ping}</span>
        </div>
        ${extraHtml}
        <div style="height: 60px; width: 100%; mt-2">
            <canvas id="hex-tooltip-chart"></canvas>
        </div>
    `;
    
    tt.style.display = 'block';
    
    const rect = element.getBoundingClientRect();
    tt.style.left = (rect.left + rect.width / 2 + window.scrollX) + 'px';
    tt.style.top = (rect.top + window.scrollY) + 'px';
    
    // Initialize mini chart
    let history = [];
    try { history = JSON.parse(ds.history || '[]'); } catch(e){}
    
    const ctx = document.getElementById('hex-tooltip-chart').getContext('2d');
    if (hexTooltipChart) hexTooltipChart.destroy();
    
    const chartColor = isOffline ? '#dc3545' : (isAlerta ? '#ffc107' : '#198754');
    
    hexTooltipChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: history.map((_, i) => ''),
            datasets: [{
                data: history,
                borderColor: chartColor,
                backgroundColor: chartColor + '33', // 20% opacity
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
            },
            layout: { padding: 0 }
        }
    });
};

window.hideHexTooltip = function() {
    const tt = document.getElementById('hex-tooltip');
    if(tt) tt.style.display = 'none';
};
