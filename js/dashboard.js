let dashboardInterval;
let previousNodeStates = {}; // Track state for animations
let criticalAlertTimeout;

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
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => {
            console.error(`Error al intentar pantalla completa: ${err.message}`);
        });
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
};

function initDashboardModule() {
    loadDashboardData();
    
    if(dashboardInterval) clearInterval(dashboardInterval);
    // Auto refresh every 30 seconds
    dashboardInterval = setInterval(loadDashboardData, 30000);

    // Inject tooltip if not exists
    if (!document.getElementById('hex-tooltip')) {
        const tt = document.createElement('div');
        tt.id = 'hex-tooltip';
        document.body.appendChild(tt);
    }

    // Inject critical alert overlay if not exists
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
            
            const grid = document.getElementById('noc-grid');
            grid.innerHTML = '';
            
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
                let cardClass = 'online';
                let stateText = 'Online';
                let iconClass = 'bi-hdd-network';
                
                if (n.tipo === 'mikrotik') {
                    iconClass = 'bi-router';
                } else if (n.tipo === 'equipo') {
                    let com = (n.comunidad_snmp || '').toLowerCase();
                    if (com.includes('antena') || com.includes('ptp') || com.includes('torre') || com.includes('ap')) {
                        iconClass = 'bi-broadcast';
                    } else if (com.includes('cliente') || com.includes('usuario')) {
                        iconClass = 'bi-person-circle';
                    }
                }
                
                if (n.estado_noc === 'offline') {
                    cardClass = 'offline';
                    stateText = 'Offline';
                } else if (n.estado_noc === 'alerta') {
                    cardClass = 'alerta';
                    stateText = 'Alerta';
                }
                
                // Track state changes to apply cool animations
                let prev = previousNodeStates[n.id];
                if (prev) {
                    if (prev !== 'offline' && n.estado_noc === 'offline') {
                        cardClass += ' just-offline-anim';
                        newlyOfflineNodes.push(n.nombre);
                    } else if (prev === 'offline' && n.estado_noc === 'online') {
                        cardClass += ' just-online-anim';
                    }
                }
                previousNodeStates[n.id] = n.estado_noc;
                
                const ping = n.ultimo_ping !== null ? n.ultimo_ping + ' ms' : '--';
                
                let cpuStr = 'N/A';
                if (n.tipo !== 'equipo') {
                    let cpuVal = n.cpu_uso !== null ? parseInt(n.cpu_uso) : 0;
                    cpuStr = n.cpu_uso !== null ? cpuVal + '%' : 'N/A';
                    if (cardClass === 'offline') cpuStr = 'OFF';
                }

                const clickAction = n.tipo === 'equipo' 
                    ? `loadView('equipos/detalles', {id: ${n.id}})`
                    : `loadView('mikrotik/detalles', {id: ${n.id}})`;

                const trafficDisplay = n.tipo === 'equipo' ? 'N/A' : `${n.trafico_mbps} Mbps`;
                const safeName = n.nombre.replace(/"/g, '&quot;');
                
                grid.innerHTML += `
                    <div class="noc-hexagon ${cardClass}" 
                         onclick="${clickAction}"
                         data-nombre="${safeName}"
                         data-ip="${n.ip_address}"
                         data-ping="${ping}"
                         data-cpu="${cpuStr}"
                         data-trafico="${trafficDisplay}"
                         data-estado="${stateText}"
                         data-tipo="${n.tipo}"
                         data-history="${JSON.stringify(n.ping_history)}"
                         onmouseenter="showHexTooltip(event, this)"
                         onmouseleave="hideHexTooltip()">
                        <i class="bi ${iconClass} hex-icon"></i>
                        <div class="hex-title" title="${safeName}">${safeName}</div>
                    </div>
                `;
            });
            
            if (newlyOfflineNodes.length > 0) {
                if (newlyOfflineNodes.length === 1) {
                    showCriticalAlert(`CAÍDO: ${newlyOfflineNodes[0]}`);
                } else {
                    showCriticalAlert(`CAÍDOS: ${newlyOfflineNodes.join(', ')}`);
                }
            }

            document.getElementById('dashboard-last-update').innerText = 'Actualizado: ' + new Date().toLocaleTimeString();
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
