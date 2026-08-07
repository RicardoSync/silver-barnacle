/**
 * Módulo JavaScript de Topología de Red
 * Desarrollado para Elissa
 * Incluye Zoom, Paneo en 360° y Creación Independiente de Enlaces
 */

let topologíaState = {
    nodos: [],
    enlaces: [],
    pingMap: {},
    connectMode: false,
    origenNodeId: null,
    dragNode: null,
    dragStartMouse: { x: 0, y: 0 },
    dragStartPos: { x: 0, y: 0 },
    pingInterval: null,
    intervalMs: 5000,
    // Paneo y Zoom
    zoom: 1,
    pan: { x: 0, y: 0 },
    isPanning: false,
    panStart: { x: 0, y: 0 }
};

function initTopologiaModule() {
    cargarTopologiaData();
    
    // Iniciar polling de ping por defecto
    cambiarIntervaloPing(topologíaState.intervalMs);

    // Ajustar SVG al redimensionar la ventana o cambiar pantalla completa
    window.removeEventListener('resize', renderEnlacesSVG);
    window.addEventListener('resize', renderEnlacesSVG);

    document.removeEventListener('fullscreenchange', onFullScreenChangeTopologia);
    document.addEventListener('fullscreenchange', onFullScreenChangeTopologia);
    document.removeEventListener('webkitfullscreenchange', onFullScreenChangeTopologia);
    document.addEventListener('webkitfullscreenchange', onFullScreenChangeTopologia);

    // Eventos de Zoom y Paneo en el Workspace
    setupWorkspacePanAndZoom();
}

/* --- Configuración de Paneo y Zoom --- */
function setupWorkspacePanAndZoom() {
    const workspace = document.getElementById('topology-workspace');
    if (!workspace) return;

    workspace.removeEventListener('wheel', onWorkspaceWheel);
    workspace.addEventListener('wheel', onWorkspaceWheel, { passive: false });

    workspace.removeEventListener('mousedown', onWorkspaceMouseDown);
    workspace.addEventListener('mousedown', onWorkspaceMouseDown);
}

function onWorkspaceWheel(e) {
    e.preventDefault();
    const factor = e.deltaY < 0 ? 1.15 : 0.85;
    zoomTopologia(factor, e.clientX, e.clientY);
}

function onWorkspaceMouseDown(e) {
    if (e.target.closest('.topology-node-card') || e.target.closest('.btn') || e.target.closest('.modal')) {
        return;
    }
    topologíaState.isPanning = true;
    topologíaState.panStart = {
        x: e.clientX - topologíaState.pan.x,
        y: e.clientY - topologíaState.pan.y
    };
    
    const workspace = document.getElementById('topology-workspace');
    if (workspace) workspace.style.cursor = 'grabbing';

    document.addEventListener('mousemove', onWorkspaceMouseMove);
    document.addEventListener('mouseup', onWorkspaceMouseUp);
}

function onWorkspaceMouseMove(e) {
    if (!topologíaState.isPanning) return;
    topologíaState.pan.x = e.clientX - topologíaState.panStart.x;
    topologíaState.pan.y = e.clientY - topologíaState.panStart.y;
    applyViewportTransform();
}

function onWorkspaceMouseUp() {
    if (topologíaState.isPanning) {
        topologíaState.isPanning = false;
        const workspace = document.getElementById('topology-workspace');
        if (workspace) workspace.style.cursor = 'grab';
    }
    document.removeEventListener('mousemove', onWorkspaceMouseMove);
    document.removeEventListener('mouseup', onWorkspaceMouseUp);
}

function applyViewportTransform() {
    const viewport = document.getElementById('topology-viewport');
    const badge = document.getElementById('topology-zoom-level');
    if (viewport) {
        viewport.style.transform = `translate(${topologíaState.pan.x}px, ${topologíaState.pan.y}px) scale(${topologíaState.zoom})`;
    }
    if (badge) {
        badge.textContent = `${Math.round(topologíaState.zoom * 100)}%`;
    }
}

window.zoomTopologia = function(factor, originX = null, originY = null) {
    const workspace = document.getElementById('topology-workspace');
    if (!workspace) return;

    const oldZoom = topologíaState.zoom;
    let newZoom = oldZoom * factor;
    newZoom = Math.max(0.25, Math.min(3.0, newZoom)); // Rango entre 25% y 300%

    if (newZoom === oldZoom) return;

    if (originX !== null && originY !== null) {
        const rect = workspace.getBoundingClientRect();
        const mouseX = originX - rect.left;
        const mouseY = originY - rect.top;

        topologíaState.pan.x = mouseX - (mouseX - topologíaState.pan.x) * (newZoom / oldZoom);
        topologíaState.pan.y = mouseY - (mouseY - topologíaState.pan.y) * (newZoom / oldZoom);
    } else {
        const rect = workspace.getBoundingClientRect();
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        topologíaState.pan.x = centerX - (centerX - topologíaState.pan.x) * (newZoom / oldZoom);
        topologíaState.pan.y = centerY - (centerY - topologíaState.pan.y) * (newZoom / oldZoom);
    }

    topologíaState.zoom = newZoom;
    applyViewportTransform();
};

window.resetWorkspaceView = function() {
    topologíaState.zoom = 1;
    topologíaState.pan = { x: 0, y: 0 };
    applyViewportTransform();
    renderNodosHTML();
    renderEnlacesSVG();
};

// Función global de Pantalla Completa para Topología
window.toggleFullScreenTopologia = function() {
    const elem = document.getElementById('topology-workspace-wrapper') || document.documentElement;
    if (!document.fullscreenElement && !document.webkitFullscreenElement) {
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        }
    }
};

function onFullScreenChangeTopologia() {
    const wrapper = document.getElementById('topology-workspace-wrapper');
    const btnText = document.getElementById('btn-fullscreen-text');

    if (document.fullscreenElement || document.webkitFullscreenElement) {
        if (wrapper) wrapper.classList.add('is-fullscreen');
        if (btnText) btnText.textContent = 'Salir Fullscreen';
    } else {
        if (wrapper) wrapper.classList.remove('is-fullscreen');
        if (btnText) btnText.textContent = 'Pantalla Completa';
    }

    setTimeout(renderEnlacesSVG, 100);
}

function cargarTopologiaData() {
    fetch('controllers/TopologiaController.php?action=obtener')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                topologíaState.nodos = data.nodos || [];
                topologíaState.enlaces = data.enlaces || [];
                renderNodosHTML();
                renderEnlacesSVG();
                actualizarEstadisticas();
                ejecutarPingBatch();
                applyViewportTransform();
            } else {
                console.error('Error al cargar topología:', data.message);
            }
        })
        .catch(err => console.error('Error AJAX:', err));
}

function renderNodosHTML() {
    const layer = document.getElementById('topology-nodes-layer');
    if (!layer) return;

    layer.innerHTML = '';

    topologíaState.nodos.forEach(nodo => {
        const isOnline = topologíaState.pingMap[nodo.id]?.status === 'online';
        const ms = topologíaState.pingMap[nodo.id]?.ms;
        const statusClass = isOnline ? 'online' : (topologíaState.pingMap[nodo.id] ? 'offline' : 'pending');
        const isSelectedSource = topologíaState.origenNodeId === nodo.id;

        const nodeEl = document.createElement('div');
        nodeEl.className = `topology-node-card ${statusClass} ${isSelectedSource ? 'selected-source' : ''}`;
        nodeEl.id = `node-card-${nodo.id}`;
        nodeEl.style.left = `${nodo.pos_x}px`;
        nodeEl.style.top = `${nodo.pos_y}px`;
        nodeEl.setAttribute('data-id', nodo.id);

        const iconClass = getNodoIconClass(nodo.tipo);

        // Construcción del Popover de Detalles al hacer Hover
        let detailsHtml = '';
        let statusText = isOnline 
            ? `<span class="badge bg-success me-1">Online</span> (${ms} ms)` 
            : `<span class="badge bg-danger me-1">Caído</span>`;

        if (nodo.tipo_ref === 'mikrotik' && nodo.version_ros) {
            const ramTotalMb = Math.round(nodo.ram_total / (1024 * 1024));
            const ramLibreMb = Math.round(nodo.ram_libre / (1024 * 1024));
            const ramUsoMb = ramTotalMb - ramLibreMb;
            const ramUsoPct = Math.round((ramUsoMb / ramTotalMb) * 100);

            detailsHtml = `
                <div class="hover-stat-item"><strong>IP:</strong> ${escapeHtml(nodo.ip_address)}</div>
                <div class="hover-stat-item"><strong>Estado:</strong> ${statusText}</div>
                <div class="hover-stat-item"><strong>OS:</strong> RouterOS ${escapeHtml(nodo.version_ros)}</div>
                <div class="hover-stat-item"><strong>Uptime:</strong> ${escapeHtml(nodo.uptime)}</div>
                <div class="hover-stat-item mt-1">
                    <strong>CPU:</strong> ${nodo.cpu_uso}%
                    <div class="progress progress-xs mt-1">
                        <div class="progress-bar bg-primary" style="width: ${nodo.cpu_uso}%"></div>
                    </div>
                </div>
            `;
        } else {
            let deviceLabel = nodo.tipo.toUpperCase();
            detailsHtml = `
                <div class="hover-stat-item"><strong>Tipo:</strong> ${deviceLabel}</div>
                <div class="hover-stat-item"><strong>IP:</strong> ${escapeHtml(nodo.ip_address)}</div>
                <div class="hover-stat-item"><strong>Estado:</strong> ${statusText}</div>
            `;
        }

        const actionsHtml = `
            <div class="hover-actions-bar">
                <button class="btn btn-hover-action btn-outline-primary" onclick="event.stopPropagation(); prepararConectarDesde(${nodo.id})" title="Conectar"><i class="bi bi-link-45deg me-1"></i>Enlace</button>
                <button class="btn btn-hover-action btn-outline-secondary" onclick="event.stopPropagation(); editarNodo(${nodo.id})" title="Editar"><i class="bi bi-pencil me-1"></i>Editar</button>
                <button class="btn btn-hover-action btn-outline-danger" onclick="event.stopPropagation(); eliminarNodo(${nodo.id})" title="Eliminar"><i class="bi bi-trash me-1"></i>Borrar</button>
                <button class="btn btn-hover-action btn-outline-success" onclick="event.stopPropagation(); ejecutarHerramientaRed('ping', '${escapeHtml(nodo.ip_address)}', '${escapeHtml(nodo.nombre)}')"><i class="bi bi-play-fill me-1"></i>Ping</button>
            </div>
        `;

        const popoverHtml = `
            <div class="node-hover-details">
                <div class="hover-details-title">${escapeHtml(nodo.nombre)}</div>
                <div class="hover-details-body">
                    ${detailsHtml}
                    ${actionsHtml}
                </div>
            </div>
        `;

        nodeEl.innerHTML = `
            ${popoverHtml}
            <div class="node-icon-circle device-type-${nodo.tipo}">
                <i class="bi ${iconClass}"></i>
                <div class="status-led-badge"></div>
            </div>
            <div class="node-label-pill" title="${escapeHtml(nodo.nombre)} (${escapeHtml(nodo.ip_address)})">
                ${escapeHtml(nodo.nombre)}
            </div>
        `;

        // Eventos de Drag & Drop y Selección
        nodeEl.addEventListener('mousedown', (e) => iniciarArrastreNodo(e, nodo));
        nodeEl.addEventListener('click', (e) => onClickNodo(e, nodo));

        layer.appendChild(nodeEl);
    });
}

function getNodoIconClass(tipo) {
    switch (tipo) {
        case 'router':
            return 'bi-router';
        case 'ap':
            return 'bi-broadcast';
        case 'switch':
            return 'bi-hdd-network-fill';
        case 'cpe':
            return 'bi-reception-4';
        case 'servidor':
            return 'bi-server';
        case 'pc':
            return 'bi-pc-display';
        case 'iot':
            return 'bi-camera-video-fill';
        default:
            return 'bi-hdd-fill';
    }
}

function renderEnlacesSVG() {
    const svgLayer = document.getElementById('topology-links-layer');
    if (!svgLayer) return;

    svgLayer.innerHTML = '';

    topologíaState.enlaces.forEach(enlace => {
        const origenNode = topologíaState.nodos.find(n => n.id == enlace.nodo_origen_id);
        const destinoNode = topologíaState.nodos.find(n => n.id == enlace.nodo_destino_id);

        if (!origenNode || !destinoNode) return;

        const elOrigen = document.getElementById(`node-card-${origenNode.id}`);
        const elDestino = document.getElementById(`node-card-${destinoNode.id}`);

        // Puntos centrales exactos de los círculos de íconos (72px ancho total, centro X: 36px, Y del centro del círculo: 27px)
        const widthOrigen = elOrigen ? elOrigen.offsetWidth : 72;
        const widthDestino = elDestino ? elDestino.offsetWidth : 72;

        const x1 = parseFloat(origenNode.pos_x) + widthOrigen / 2;
        const y1 = parseFloat(origenNode.pos_y) + 27; // 27px es la mitad del círculo de 54px
        const x2 = parseFloat(destinoNode.pos_x) + widthDestino / 2;
        const y2 = parseFloat(destinoNode.pos_y) + 27;

        // Determinar estado de conectividad entre ambos nodos
        const origenStatus = topologíaState.pingMap[origenNode.id]?.status === 'online';
        const destinoStatus = topologíaState.pingMap[destinoNode.id]?.status === 'online';
        const isLinkOnline = origenStatus && destinoStatus;

        // Grupo SVG para la línea
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('class', 'topology-link-group');
        g.setAttribute('data-enlace-id', enlace.id);

        // Estilos diferenciadores por tipo de enlace
        let strokeColor = '#198754';
        let strokeWidth = '2.5';
        let dashArray = null;

        if (isLinkOnline) {
            switch (enlace.tipo_enlace) {
                case 'fibra':
                    strokeColor = '#00bcff'; // Azul brillante / Cyan
                    strokeWidth = '3.5';
                    break;
                case 'ethernet':
                    strokeColor = '#198754'; // Verde sólido
                    strokeWidth = '2.8';
                    break;
                case 'inalambrico':
                default:
                    strokeColor = '#10b981'; // Verde menta
                    strokeWidth = '2.2';
                    dashArray = '5, 4'; // Segmentada para representar ondas
                    break;
            }
        } else {
            strokeColor = '#dc3545'; // Rojo caído
            strokeWidth = '2.5';
            dashArray = '4, 4'; // Punteada roja para indicar desconexión
        }

        // Línea principal como path para soportar animateMotion
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        const pathData = `M ${x1} ${y1} L ${x2} ${y2}`;
        line.setAttribute('d', pathData);
        line.setAttribute('class', isLinkOnline ? 'topology-line-active' : 'topology-line-inactive');
        line.setAttribute('stroke', strokeColor);
        line.setAttribute('stroke-width', strokeWidth);
        if (dashArray) {
            line.setAttribute('stroke-dasharray', dashArray);
        }
        line.setAttribute('fill', 'none');

        g.appendChild(line);

        // Si el enlace está activo, agregamos los dos puntitos que viajan de ida y vuelta
        if (isLinkOnline) {
            // Colores de esferas diferenciados por medio físico/tipo de enlace
            let dot1Color = '#ffffff';
            let dot2Color = '#a7f3d0';

            switch (enlace.tipo_enlace) {
                case 'fibra':
                    dot1Color = '#00f0ff'; // Cyan neón
                    dot2Color = '#d946ef'; // Magenta neón
                    break;
                case 'ethernet':
                    dot1Color = '#ffffff'; // Blanco puro
                    dot2Color = '#22c55e'; // Verde intenso
                    break;
                case 'inalambrico':
                default:
                    dot1Color = '#ffbb00'; // Ámbar / Naranja claro
                    dot2Color = '#00c8ff'; // Cyan
                    break;
            }

            // Punto 1: Origen -> Destino -> Origen (Color 1, más grande y con contorno blanco)
            const dot1 = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            dot1.setAttribute('r', '5.5');
            dot1.setAttribute('fill', dot1Color);
            dot1.setAttribute('stroke', '#ffffff');
            dot1.setAttribute('stroke-width', '1.5');
            
            const anim1 = document.createElementNS('http://www.w3.org/2000/svg', 'animateMotion');
            anim1.setAttribute('dur', '3.5s');
            anim1.setAttribute('repeatCount', 'indefinite');
            anim1.setAttribute('path', `M ${x1} ${y1} L ${x2} ${y2} L ${x1} ${y1}`);
            dot1.appendChild(anim1);
            g.appendChild(dot1);

            // Punto 2: Destino -> Origen -> Destino (Color 2, más grande y con contorno blanco)
            const dot2 = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            dot2.setAttribute('r', '5.5');
            dot2.setAttribute('fill', dot2Color);
            dot2.setAttribute('stroke', '#ffffff');
            dot2.setAttribute('stroke-width', '1.5');
            
            const anim2 = document.createElementNS('http://www.w3.org/2000/svg', 'animateMotion');
            anim2.setAttribute('dur', '3.5s');
            anim2.setAttribute('repeatCount', 'indefinite');
            anim2.setAttribute('path', `M ${x2} ${y2} L ${x1} ${y1} L ${x2} ${y2}`);
            dot2.appendChild(anim2);
            g.appendChild(dot2);
        }

        // Etiqueta en el punto medio del enlace
        const midX = (x1 + x2) / 2;
        const midY = (y1 + y2) / 2;

        const textBg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        textBg.setAttribute('x', midX - 35);
        textBg.setAttribute('y', midY - 9);
        textBg.setAttribute('width', '70');
        textBg.setAttribute('height', '18');
        textBg.setAttribute('rx', '4');
        textBg.setAttribute('fill', '#ffffff');
        textBg.setAttribute('stroke', isLinkOnline ? strokeColor : '#dc3545');
        textBg.setAttribute('stroke-width', '1');

        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', midX);
        text.setAttribute('y', midY + 3);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('fill', '#334155');
        text.setAttribute('font-size', '9');
        text.setAttribute('font-weight', '600');
        
        let labelText = enlace.etiqueta;
        if (!labelText) {
            if (enlace.tipo_enlace === 'fibra') labelText = 'Fibra';
            else if (enlace.tipo_enlace === 'ethernet') labelText = 'Ethernet';
            else labelText = 'Inalámbrico';
        }
        text.textContent = labelText;

        g.addEventListener('dblclick', (e) => {
            e.stopPropagation();
            eliminarEnlace(enlace.id);
        });

        g.appendChild(textBg);
        g.appendChild(text);
        svgLayer.appendChild(g);
    });
}

/* --- Drag & Drop de Nodos --- */
function iniciarArrastreNodo(e, nodo) {
    if (e.target.closest('.node-actions-menu') || topologíaState.connectMode) return;
    e.stopPropagation();

    topologíaState.dragNode = nodo;
    topologíaState.dragStartMouse = { x: e.clientX, y: e.clientY };
    topologíaState.dragStartPos = { x: parseFloat(nodo.pos_x), y: parseFloat(nodo.pos_y) };

    document.addEventListener('mousemove', moverArrastreNodo);
    document.addEventListener('mouseup', finalizarArrastreNodo);
}

function moverArrastreNodo(e) {
    if (!topologíaState.dragNode) return;

    const dx = (e.clientX - topologíaState.dragStartMouse.x) / topologíaState.zoom;
    const dy = (e.clientY - topologíaState.dragStartMouse.y) / topologíaState.zoom;

    let newX = Math.round(topologíaState.dragStartPos.x + dx);
    let newY = Math.round(topologíaState.dragStartPos.y + dy);

    topologíaState.dragNode.pos_x = newX;
    topologíaState.dragNode.pos_y = newY;

    const cardEl = document.getElementById(`node-card-${topologíaState.dragNode.id}`);
    if (cardEl) {
        cardEl.style.left = `${newX}px`;
        cardEl.style.top = `${newY}px`;
    }

    renderEnlacesSVG();
}

function finalizarArrastreNodo() {
    if (!topologíaState.dragNode) return;

    const nodo = topologíaState.dragNode;
    topologíaState.dragNode = null;

    document.removeEventListener('mousemove', moverArrastreNodo);
    document.removeEventListener('mouseup', finalizarArrastreNodo);

    // Guardar posición en servidor
    const formData = new FormData();
    formData.append('action', 'actualizar_posicion');
    formData.append('id', nodo.id);
    formData.append('pos_x', nodo.pos_x);
    formData.append('pos_y', nodo.pos_y);

    fetch('controllers/TopologiaController.php', {
        method: 'POST',
        body: formData
    }).catch(err => console.error('Error al guardar posición:', err));
}

/* --- Modo Conectar Nodos --- */
function toggleModoConectar(activar) {
    topologíaState.connectMode = activar !== undefined ? activar : !topologíaState.connectMode;
    topologíaState.origenNodeId = null;

    const banner = document.getElementById('connect-mode-banner');
    const btn = document.getElementById('btn-toggle-connect');

    if (topologíaState.connectMode) {
        banner.classList.remove('d-none');
        banner.classList.add('d-flex');
        if (btn) {
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-primary');
        }
    } else {
        banner.classList.add('d-none');
        banner.classList.remove('d-flex');
        if (btn) {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        }
    }
    renderNodosHTML();
}

function prepararConectarDesde(nodoId) {
    toggleModoConectar(true);
    topologíaState.origenNodeId = nodoId;
    renderNodosHTML();
}

function onClickNodo(e, nodo) {
    if (!topologíaState.connectMode) return;

    if (!topologíaState.origenNodeId) {
        topologíaState.origenNodeId = nodo.id;
        renderNodosHTML();
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: `Origen: ${nodo.nombre}. Ahora haz clic en el destino.`,
            showConfirmButton: false,
            timer: 2500
        });
    } else if (topologíaState.origenNodeId === nodo.id) {
        topologíaState.origenNodeId = null;
        renderNodosHTML();
    } else {
        const origenId = topologíaState.origenNodeId;
        const destinoId = nodo.id;
        toggleModoConectar(false);

        abrirModalCrearEnlace(origenId, destinoId);
    }
}

/* --- Pings & Polling --- */
function ejecutarPingBatch() {
    const lastUpdateBadge = document.getElementById('topologia-last-update');
    if (lastUpdateBadge) {
        lastUpdateBadge.innerHTML = '<i class="bi bi-arrow-repeat spin me-1"></i> Verificando...';
    }

    fetch('controllers/TopologiaController.php?action=ping_batch')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                topologíaState.pingMap = res.data || {};
                renderNodosHTML();
                renderEnlacesSVG();
                actualizarEstadisticas();

                if (lastUpdateBadge) {
                    const hora = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    lastUpdateBadge.innerHTML = `<i class="bi bi-check-circle-fill text-success me-1"></i> Actualizado ${hora}`;
                }
            }
        })
        .catch(err => {
            console.error('Error al ejecutar ping batch:', err);
            if (lastUpdateBadge) {
                lastUpdateBadge.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> Error Ping';
            }
        });
}

function cambiarIntervaloPing(valMs) {
    topologíaState.intervalMs = parseInt(valMs);
    if (topologíaState.pingInterval) {
        clearInterval(topologíaState.pingInterval);
        topologíaState.pingInterval = null;
    }

    if (topologíaState.intervalMs > 0) {
        topologíaState.pingInterval = setInterval(ejecutarPingBatch, topologíaState.intervalMs);
    }
}

function actualizarEstadisticas() {
    const total = topologíaState.nodos.length;
    const enlacesTotal = topologíaState.enlaces.length;
    let online = 0;
    let offline = 0;

    topologíaState.nodos.forEach(n => {
        if (topologíaState.pingMap[n.id]?.status === 'online') {
            online++;
        } else if (topologíaState.pingMap[n.id]?.status === 'offline') {
            offline++;
        }
    });

    const elTotal = document.getElementById('stat-total-nodos');
    const elOnline = document.getElementById('stat-online-nodos');
    const elOffline = document.getElementById('stat-offline-nodos');
    const elEnlaces = document.getElementById('stat-total-enlaces');

    if (elTotal) elTotal.textContent = total;
    if (elOnline) elOnline.textContent = online;
    if (elOffline) elOffline.textContent = offline;
    if (elEnlaces) elEnlaces.textContent = enlacesTotal;
}

/* --- Modales & Operaciones CRUD --- */
function abrirModalNuevoNodo() {
    document.getElementById('formNodo').reset();
    document.getElementById('nodo_id').value = '0';
    document.getElementById('modalNodoLabel').innerHTML = '<i class="bi bi-hdd-rack me-2 text-primary"></i> Registrar Equipo en Topología';
    
    const modal = new bootstrap.Modal(document.getElementById('modalNodo'));
    modal.show();
}

function editarNodo(id) {
    const nodo = topologíaState.nodos.find(n => n.id == id);
    if (!nodo) return;

    document.getElementById('nodo_id').value = nodo.id;
    document.getElementById('nodo_nombre').value = nodo.nombre;
    document.getElementById('nodo_ip').value = nodo.ip_address;
    document.getElementById('nodo_tipo').value = nodo.tipo;
    document.getElementById('nodo_pos_x').value = nodo.pos_x;
    document.getElementById('nodo_pos_y').value = nodo.pos_y;

    document.getElementById('modalNodoLabel').innerHTML = '<i class="bi bi-pencil-square me-2 text-primary"></i> Editar Equipo de Topología';
    const modal = new bootstrap.Modal(document.getElementById('modalNodo'));
    modal.show();
}

function guardarNodo(e) {
    e.preventDefault();
    const form = document.getElementById('formNodo');
    const formData = new FormData(form);
    formData.append('action', 'guardar_nodo');

    fetch('controllers/TopologiaController.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            bootstrap.Modal.getInstance(document.getElementById('modalNodo')).hide();
            cargarTopologiaData();
            Swal.fire('¡Éxito!', res.message, 'success');
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    })
    .catch(err => console.error('Error:', err));
}

function eliminarNodo(id) {
    Swal.fire({
        title: '¿Eliminar este equipo?',
        text: 'Se eliminarán también las conexiones vinculadas a este equipo.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'eliminar_nodo');
            formData.append('id', id);

            fetch('controllers/TopologiaController.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    cargarTopologiaData();
                    Swal.fire('Eliminado', res.message, 'success');
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        }
    });
}

window.abrirModalCrearEnlace = function(origenId = null, destinoId = null) {
    const selOrigen = document.getElementById('enlace_origen');
    const selDestino = document.getElementById('enlace_destino');

    if (!selOrigen || !selDestino) return;

    selOrigen.innerHTML = '<option value="">Selecciona Equipo Origen</option>';
    selDestino.innerHTML = '<option value="">Selecciona Equipo Destino</option>';

    topologíaState.nodos.forEach(n => {
        selOrigen.innerHTML += `<option value="${n.id}" ${origenId == n.id ? 'selected' : ''}>${escapeHtml(n.nombre)} (${n.ip_address})</option>`;
        selDestino.innerHTML += `<option value="${n.id}" ${destinoId == n.id ? 'selected' : ''}>${escapeHtml(n.nombre)} (${n.ip_address})</option>`;
    });

    const modal = new bootstrap.Modal(document.getElementById('modalEnlace'));
    modal.show();
};

function guardarEnlace(e) {
    e.preventDefault();
    const form = document.getElementById('formEnlace');
    const formData = new FormData(form);
    formData.append('action', 'crear_enlace');

    fetch('controllers/TopologiaController.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            bootstrap.Modal.getInstance(document.getElementById('modalEnlace')).hide();
            cargarTopologiaData();
            Swal.fire('¡Conectado!', res.message, 'success');
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    })
    .catch(err => console.error('Error:', err));
}

function eliminarEnlace(id) {
    Swal.fire({
        title: '¿Eliminar este enlace?',
        text: 'Se removerá la línea de conexión entre ambos equipos.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'eliminar_enlace');
            formData.append('id', id);

            fetch('controllers/TopologiaController.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    cargarTopologiaData();
                    Swal.fire('Enlace Eliminado', res.message, 'success');
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        }
    });
}

function importarDispositivos() {
    Swal.fire({
        title: 'Importar Equipos Existentes',
        text: 'Se añadirán automáticamente al mapa todos los MikroTiks y Equipos registrados en el sistema.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, importar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            fetch('controllers/TopologiaController.php?action=importar')
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        cargarTopologiaData();
                        Swal.fire('Importación Completada', res.message, 'success');
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

/* --- Buscador Interactivo --- */
function onBuscarNodo(query) {
    query = query.trim().toLowerCase();
    const cards = document.querySelectorAll('.topology-node-card');
    const links = document.querySelectorAll('.topology-link-group');

    if (query === '') {
        // Restaurar estado normal
        cards.forEach(c => {
            c.classList.remove('topology-node-fade');
            c.classList.remove('search-pulse');
        });
        links.forEach(l => l.classList.remove('topology-link-fade'));
        return;
    }

    let exactMatchNodo = null;

    topologíaState.nodos.forEach(nodo => {
        const card = document.getElementById(`node-card-${nodo.id}`);
        if (!card) return;

        const match = nodo.nombre.toLowerCase().includes(query) || nodo.ip_address.toLowerCase().includes(query);
        
        if (match) {
            card.classList.remove('topology-node-fade');
            if (nodo.nombre.toLowerCase() === query || nodo.ip_address.toLowerCase() === query) {
                exactMatchNodo = nodo;
            }
        } else {
            card.classList.add('topology-node-fade');
            card.classList.remove('search-pulse');
        }
    });

    // Atenuar enlaces que no estén conectados a nodos coincidentes
    topologíaState.enlaces.forEach(enlace => {
        const linkEl = document.querySelector(`.topology-link-group[data-enlace-id="${enlace.id}"]`);
        if (!linkEl) return;

        const nodoO = topologíaState.nodos.find(n => n.id == enlace.nodo_origen_id);
        const nodoD = topologíaState.nodos.find(n => n.id == enlace.nodo_destino_id);

        const matchO = nodoO && (nodoO.nombre.toLowerCase().includes(query) || nodoO.ip_address.toLowerCase().includes(query));
        const matchD = nodoD && (nodoD.nombre.toLowerCase().includes(query) || nodoD.ip_address.toLowerCase().includes(query));

        if (matchO || matchD) {
            linkEl.classList.remove('topology-link-fade');
        } else {
            linkEl.classList.add('topology-link-fade');
        }
    });

    // Escuchar el evento keydown en el input de búsqueda para autocompletar en enter
    const input = document.getElementById('search-topology-input');
    if (input && !input.dataset.hasEnterEvent) {
        input.dataset.hasEnterEvent = 'true';
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const queryVal = input.value.trim().toLowerCase();
                const matched = topologíaState.nodos.find(n => n.nombre.toLowerCase().includes(queryVal) || n.ip_address.toLowerCase().includes(queryVal));
                if (matched) {
                    enfocarNodoEnMapa(matched);
                }
            }
        });
    }
}

function enfocarNodoEnMapa(nodo) {
    const card = document.getElementById(`node-card-${nodo.id}`);
    if (!card) return;

    // Quitar pulsos previos
    document.querySelectorAll('.topology-node-card').forEach(c => c.classList.remove('search-pulse'));

    // Calcular el desplazamiento necesario para centrar el nodo
    const workspace = document.getElementById('topology-workspace');
    if (!workspace) return;

    const wWidth = workspace.clientWidth;
    const wHeight = workspace.clientHeight;

    const nodeWidth = card.offsetWidth || 140;
    const nodeHeight = card.offsetHeight || 110;

    // Zoom a 1.2 para ver detalles claramente
    topologíaState.zoom = 1.2;

    // Calcular coordenadas del paneo para centrar el nodo
    topologíaState.pan.x = (wWidth / 2) - (parseFloat(nodo.pos_x) + nodeWidth / 2) * topologíaState.zoom;
    topologíaState.pan.y = (wHeight / 2) - (parseFloat(nodo.pos_y) + nodeHeight / 2) * topologíaState.zoom;

    applyViewportTransform();

    // Activar animación de parpadeo temporal
    card.classList.add('search-pulse');
    setTimeout(() => {
        card.classList.remove('search-pulse');
    }, 4500);
}

/* --- Consola en Tiempo Real (SSE) --- */
let sseConnection = null;

function ejecutarHerramientaRed(tipo, ip, nombre) {
    detenerHerramientaRed();

    const modalEl = document.getElementById('modalTerminal');
    const titleEl = document.getElementById('terminal-modal-title');
    const bodyEl = document.getElementById('terminal-output-body');

    if (!modalEl || !bodyEl) return;

    titleEl.textContent = `${tipo.toUpperCase()} A: ${nombre} [${ip}]`;
    bodyEl.innerHTML = `Conectando con el servidor para iniciar ${tipo}...\n`;

    // Inicializar e instanciar el modal con Bootstrap
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    // Crear conexión Server-Sent Events (SSE)
    const url = `controllers/HerramientasController.php?action=${tipo}_stream&target=${encodeURIComponent(ip)}`;
    sseConnection = new EventSource(url);

    sseConnection.onmessage = function(event) {
        try {
            const data = JSON.parse(event.data);
            const rawLine = data.line || '';

            // Quitar el cursor viejo
            const cursor = bodyEl.querySelector('.terminal-cursor');
            if (cursor) cursor.remove();

            // Insertar línea y reinstalar el cursor al final
            bodyEl.appendChild(document.createTextNode(rawLine + "\n"));
            
            const newCursor = document.createElement('span');
            newCursor.className = 'terminal-cursor';
            bodyEl.appendChild(newCursor);

            // Auto-scroll al final
            bodyEl.scrollTop = bodyEl.scrollHeight;
        } catch (e) {
            console.error('Error al procesar línea de consola:', e);
        }
    };

    sseConnection.onerror = function() {
        // Quitar cursor
        const cursor = bodyEl.querySelector('.terminal-cursor');
        if (cursor) cursor.remove();

        bodyEl.appendChild(document.createTextNode("\n--- Proceso finalizado o conexión cerrada ---\n"));
        if (sseConnection) {
            sseConnection.close();
            sseConnection = null;
        }
    };
}

function detenerHerramientaRed() {
    if (sseConnection) {
        sseConnection.close();
        sseConnection = null;
    }

    const modalEl = document.getElementById('modalTerminal');
    if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) {
            modalInstance.hide();
        }
    }
}
