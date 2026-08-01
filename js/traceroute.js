let tracerouteZoomScale = 1;
let traceroutePanX = 0;
let traceroutePanY = 0;
let isTracerouteDragging = false;
let tracerouteDragStartX = 0;
let tracerouteDragStartY = 0;

function onSelectQuickDevice(ip) {
    if (ip) {
        document.getElementById('tracerouteTarget').value = ip;
    }
}

function runTraceroute(event) {
    if (event) event.preventDefault();

    const target = document.getElementById('tracerouteTarget').value.trim();
    if (!target) return;

    const canvasCard = document.getElementById('tracerouteCanvasCard');
    const targetBadge = document.getElementById('tracerouteTargetBadge');
    const statusBadge = document.getElementById('tracerouteStatusBadge');
    const btn = document.getElementById('btnRunTraceroute');
    const nodesLayer = document.getElementById('traceroute-nodes-layer');
    const linksLayer = document.getElementById('traceroute-links-layer');

    if (canvasCard) canvasCard.style.display = 'block';
    if (targetBadge) targetBadge.innerText = `Destino: ${target}`;
    if (statusBadge) statusBadge.innerHTML = `<span class="badge bg-warning text-dark"><i class="bi bi-arrow-repeat spin me-1"></i> Rastreando saltos a ${target}...</span>`;
    if (btn) btn.disabled = true;

    if (nodesLayer) {
        nodesLayer.innerHTML = `
            <div class="d-flex align-items-center justify-content-center h-100 py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                Ejecutando traceroute hacia <strong>${target}</strong>... (Esto puede tomar entre 5 y 15 segundos)
            </div>
        `;
    }
    if (linksLayer) linksLayer.innerHTML = '';

    fetch(`controllers/HerramientasController.php?action=traceroute&target=${encodeURIComponent(target)}`)
        .then(res => res.json())
        .then(data => {
            if (btn) btn.disabled = false;

            if (data.status === 'success' && data.hops && data.hops.length > 0) {
                if (statusBadge) statusBadge.innerHTML = `<span class="badge bg-success text-white"><i class="bi bi-check-circle me-1"></i> Rastreo Completado (${data.hops.length} saltos)</span>`;
                renderTracerouteCanvasTopology(data.hops, target);
            } else {
                if (statusBadge) statusBadge.innerHTML = `<span class="badge bg-danger text-white"><i class="bi bi-x-circle me-1"></i> Error en Rastreo</span>`;
                if (nodesLayer) {
                    nodesLayer.innerHTML = `
                        <div class="text-center py-5 text-danger">
                            <i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>
                            ${data.message || 'No se pudieron obtener los saltos de red.'}
                        </div>
                    `;
                }
            }
        })
        .catch(e => {
            if (btn) btn.disabled = false;
            if (statusBadge) statusBadge.innerHTML = `<span class="badge bg-danger text-white"><i class="bi bi-x-circle me-1"></i> Error de Red</span>`;
            if (nodesLayer) {
                nodesLayer.innerHTML = `
                    <div class="text-center py-5 text-danger">
                        Fallo de comunicación con el servidor.
                    </div>
                `;
            }
        });
}

function renderTracerouteCanvasTopology(hops, target) {
    const nodesLayer = document.getElementById('traceroute-nodes-layer');
    const linksLayer = document.getElementById('traceroute-links-layer');
    if (!nodesLayer || !linksLayer) return;

    nodesLayer.innerHTML = '';
    linksLayer.innerHTML = '';

    const allNodes = [
        {
            hop: 0,
            ip: 'Servidor Local',
            hostname: 'Servidor NOC',
            ms: 0,
            type: 'noc'
        },
        ...hops.map(h => ({
            ...h,
            type: h.hop === hops.length ? 'target' : 'hop'
        }))
    ];

    const NODE_WIDTH = 170;
    const NODE_HEIGHT = 100;
    const HORIZONTAL_GAP = 90;
    const VERTICAL_GAP = 50;

    const startX = 60;
    const startY = 80;

    let nodesHtml = '';
    let linksSvg = '';

    let prevPos = null;

    allNodes.forEach((node, index) => {
        const row = Math.floor(index / 4);
        const col = index % 4;
        
        let posX = startX + col * (NODE_WIDTH + HORIZONTAL_GAP);
        let posY = startY + row * (NODE_HEIGHT + VERTICAL_GAP);

        if (row % 2 === 1) {
            posX = startX + (3 - col) * (NODE_WIDTH + HORIZONTAL_GAP);
        }

        let badgeBg = 'bg-success';
        let borderColor = 'border-success';
        let msText = node.ms !== null ? `${node.ms} ms` : '* ms';
        let icon = node.type === 'noc' ? 'bi-server' : (node.type === 'target' ? 'bi-flag-fill' : 'bi-router');
        let iconColor = node.type === 'noc' ? 'text-primary' : (node.type === 'target' ? 'text-danger' : 'text-secondary');

        if (node.type === 'noc') {
            badgeBg = 'bg-primary text-white';
            borderColor = 'border-primary';
            msText = '0 ms';
        } else if (node.ms === null || node.ip === '*') {
            badgeBg = 'bg-secondary text-white';
            borderColor = 'border-secondary';
            icon = 'bi-question-circle';
        } else if (node.ms > 150) {
            badgeBg = 'bg-danger text-white';
            borderColor = 'border-danger';
        } else if (node.ms > 60) {
            badgeBg = 'bg-warning text-dark';
            borderColor = 'border-warning';
        }

        const titleText = node.type === 'noc' ? 'Servidor NOC (Origen)' : (node.type === 'target' ? `Destino (${target})` : `Salto #${node.hop}`);
        const hostDisplay = node.hostname !== '*' ? node.hostname : (node.ip !== '*' ? node.ip : 'Sin respuesta');

        if (prevPos) {
            const x1 = prevPos.x + NODE_WIDTH / 2;
            const y1 = prevPos.y + NODE_HEIGHT / 2;
            const x2 = posX + NODE_WIDTH / 2;
            const y2 = posY + NODE_HEIGHT / 2;

            let strokeColor = '#3b82f6';
            if (node.ms > 150 || node.ms === null || node.ip === '*') strokeColor = '#ef4444';
            else if (node.ms > 60) strokeColor = '#f59e0b';

            linksSvg += `
                <line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" 
                      stroke="${strokeColor}" stroke-width="3" stroke-dasharray="6 4" style="opacity: 0.8;"></line>
            `;
        }

        nodesHtml += `
            <div class="position-absolute card border ${borderColor} shadow-sm rounded-3 bg-white p-2"
                 style="left: ${posX}px; top: ${posY}px; width: ${NODE_WIDTH}px; height: ${NODE_HEIGHT}px; transition: transform 0.2s;"
                 title="${titleText}">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge ${badgeBg} rounded-pill" style="font-size: 9px;">${titleText}</span>
                    <span class="fw-bold font-monospace text-muted" style="font-size: 10px;">${msText}</span>
                </div>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <i class="bi ${icon} ${iconColor} fs-4"></i>
                    <div class="overflow-hidden">
                        <div class="fw-bold text-dark text-truncate small" style="font-size: 11px;">${hostDisplay}</div>
                        <div class="font-monospace text-muted text-truncate" style="font-size: 9px;">${node.ip}</div>
                    </div>
                </div>
            </div>
        `;

        prevPos = { x: posX, y: posY };
    });

    linksLayer.innerHTML = linksSvg;
    nodesLayer.innerHTML = nodesHtml;

    initTracerouteWorkspaceEvents();
    resetTracerouteCanvas();
}

function initTracerouteWorkspaceEvents() {
    const workspace = document.getElementById('traceroute-workspace');
    if (!workspace || workspace.dataset.initialized) return;

    workspace.dataset.initialized = 'true';

    workspace.addEventListener('mousedown', (e) => {
        if (e.target.closest('.btn-group') || e.target.closest('button')) return;
        isTracerouteDragging = true;
        tracerouteDragStartX = e.clientX - traceroutePanX;
        tracerouteDragStartY = e.clientY - traceroutePanY;
        workspace.style.cursor = 'grabbing';
    });

    window.addEventListener('mousemove', (e) => {
        if (!isTracerouteDragging) return;
        traceroutePanX = e.clientX - tracerouteDragStartX;
        traceroutePanY = e.clientY - tracerouteDragStartY;
        applyTracerouteTransform();
    });

    window.addEventListener('mouseup', () => {
        if (isTracerouteDragging) {
            isTracerouteDragging = false;
            workspace.style.cursor = 'grab';
        }
    });

    workspace.addEventListener('wheel', (e) => {
        e.preventDefault();
        const factor = e.deltaY < 0 ? 1.1 : 0.9;
        zoomTracerouteCanvas(factor);
    });
}

function applyTracerouteTransform() {
    const viewport = document.getElementById('traceroute-viewport');
    const zoomLevelEl = document.getElementById('traceroute-zoom-level');
    if (viewport) {
        viewport.style.transform = `translate(${traceroutePanX}px, ${traceroutePanY}px) scale(${tracerouteZoomScale})`;
    }
    if (zoomLevelEl) {
        zoomLevelEl.innerText = `${Math.round(tracerouteZoomScale * 100)}%`;
    }
}

function zoomTracerouteCanvas(factor) {
    tracerouteZoomScale = Math.max(0.3, Math.min(2.5, tracerouteZoomScale * factor));
    applyTracerouteTransform();
}

function resetTracerouteCanvas() {
    tracerouteZoomScale = 1;
    traceroutePanX = 0;
    traceroutePanY = 0;
    applyTracerouteTransform();
}

function toggleFullScreenTraceroute() {
    const el = document.getElementById('tracerouteCanvasCard');
    if (!el) return;
    if (!document.fullscreenElement) {
        el.requestFullscreen().catch(err => console.log(err));
    } else {
        document.exitFullscreen();
    }
}

function initTracerouteModule() {
}

window.initTracerouteModule = initTracerouteModule;
window.onSelectQuickDevice = onSelectQuickDevice;
window.runTraceroute = runTraceroute;
window.zoomTracerouteCanvas = zoomTracerouteCanvas;
window.resetTracerouteCanvas = resetTracerouteCanvas;
window.toggleFullScreenTraceroute = toggleFullScreenTraceroute;
