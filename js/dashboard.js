let dashboardInterval;

function initDashboardModule() {
    loadDashboardData();
    
    if(dashboardInterval) clearInterval(dashboardInterval);
    // Auto refresh every 30 seconds
    dashboardInterval = setInterval(loadDashboardData, 30000);
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
            
            nodos.forEach(n => {
                let cardClass = 'online';
                let iconClass = 'text-success bi-check-circle-fill';
                let stateText = 'Online';
                
                if (n.estado_noc === 'offline') {
                    cardClass = 'offline';
                    iconClass = 'text-danger bi-x-circle-fill';
                    stateText = 'Offline';
                } else if (n.estado_noc === 'alerta') {
                    cardClass = 'alerta';
                    iconClass = 'text-warning bi-exclamation-triangle-fill';
                    stateText = 'Alerta';
                }
                
                const ping = n.ultimo_ping !== null ? n.ultimo_ping + ' ms' : '--';
                
                let cpuHtml = '';
                if (n.tipo === 'equipo') {
                    let borderCol = '#0dcaf0';
                    if (cardClass === 'offline') borderCol = '#dc3545';
                    else if (cardClass === 'alerta') borderCol = '#ffc107';

                    cpuHtml = `
                        <div class="cpu-dial me-3 d-flex align-items-center justify-content-center" style="background: #2b2b3c; border: 3px solid ${borderCol};">
                            <i class="bi bi-hdd-network ${cardClass === 'offline' ? 'text-danger' : 'text-info'} fs-5"></i>
                        </div>
                    `;
                } else {
                    let cpuVal = n.cpu_uso !== null ? parseInt(n.cpu_uso) : 0;
                    let cpuDeg = (cpuVal / 100) * 360;
                    let cpuStr = n.cpu_uso !== null ? cpuVal + '%' : 'N/A';
                    
                    let dialClass = '';
                    if(cardClass === 'offline') {
                        dialClass = 'offline';
                        cpuDeg = 0;
                        cpuStr = 'OFF';
                    } else if (cpuVal > 80) {
                        dialClass = 'high';
                    } else if (cpuVal > 50) {
                        dialClass = 'warning';
                    }

                    cpuHtml = `
                        <div class="cpu-dial ${dialClass} me-3" style="--cpu-deg: ${cpuDeg}deg;">
                            <span>${cpuStr}</span>
                        </div>
                    `;
                }

                const clickAction = n.tipo === 'equipo' 
                    ? `loadView('equipos/detalles', {id: ${n.id}})`
                    : `loadView('mikrotik/detalles', {id: ${n.id}})`;

                const trafficDisplay = n.tipo === 'equipo' ? 'N/A' : `${n.trafico_mbps} Mbps`;
                
                grid.innerHTML += `
                    <div class="noc-card ${cardClass}" onclick="${clickAction}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center">
                                ${cpuHtml}
                                <div>
                                    <h6 class="fw-bold mb-0 text-truncate text-white" style="max-width: 130px;" title="${n.nombre}">${n.nombre}</h6>
                                    <div class="small text-white-50"><i class="bi bi-hdd-network"></i> ${n.ip_address}</div>
                                </div>
                            </div>
                            <i class="bi ${iconClass} fs-5" title="${stateText}"></i>
                        </div>
                        
                        <div class="mt-auto pt-3 border-top" style="border-color: rgba(255,255,255,0.1) !important;">
                            <div class="d-flex justify-content-between text-center">
                                <div>
                                    <div class="small text-white-50" style="font-size: 11px;">LATENCIA</div>
                                    <div class="fw-bold ${n.ultimo_ping > 150 ? 'text-warning' : 'text-white'}">${ping}</div>
                                </div>
                                <div>
                                    <div class="small text-white-50" style="font-size: 11px;">TRÁFICO</div>
                                    <div class="fw-bold text-info">${trafficDisplay}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            document.getElementById('dashboard-last-update').innerText = 'Actualizado: ' + new Date().toLocaleTimeString();
        }
    }).catch(e => console.error("Error al cargar NOC:", e));
}

// Interceptar loadView para limpiar el intervalo cuando salimos de la vista
const origLoadViewDB = window.loadView;
window.loadView = function(viewName, params = null) {
    if(dashboardInterval) clearInterval(dashboardInterval);
    origLoadViewDB(viewName, params);
}
