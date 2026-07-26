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

        const iconHtml = getNodoIconoHtml(nodo.tipo);

        nodeEl.innerHTML = `
            <div class="node-header">
                <div class="node-icon">${iconHtml}</div>
                <div class="node-status-indicator"></div>
            </div>
            <div class="node-body">
                <div class="node-title" title="${escapeHtml(nodo.nombre)}">${escapeHtml(nodo.nombre)}</div>
                <div class="node-ip"><i class="bi bi-hdd-network me-1"></i>${escapeHtml(nodo.ip_address)}</div>
                <div class="node-ping-info">
                    ${isOnline ? `<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>${ms} ms</span>` :
                      (topologíaState.pingMap[nodo.id] ? `<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Caído</span>` :
                      `<span class="badge bg-warning text-dark"><span class="spinner-border spinner-border-sm me-1" style="width:9px;height:9px;"></span>...</span>`)}
                </div>
            </div>
            <div class="node-actions-menu">
                <button class="btn btn-xs btn-outline-primary" onclick="event.stopPropagation(); prepararConectarDesde(${nodo.id})" title="Conectar"><i class="bi bi-link-45deg"></i></button>
                <button class="btn btn-xs btn-outline-secondary" onclick="event.stopPropagation(); editarNodo(${nodo.id})" title="Editar"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-xs btn-outline-danger" onclick="event.stopPropagation(); eliminarNodo(${nodo.id})" title="Eliminar"><i class="bi bi-trash"></i></button>
            </div>
        `;

        // Eventos de Drag & Drop y Selección
        nodeEl.addEventListener('mousedown', (e) => iniciarArrastreNodo(e, nodo));
        nodeEl.addEventListener('click', (e) => onClickNodo(e, nodo));

        layer.appendChild(nodeEl);
    });
}

function getNodoIconoHtml(tipo) {
    switch (tipo) {
        case 'router':
            return '<i class="bi bi-router"></i>';
        case 'ap':
            return '<i class="bi bi-wifi"></i>';
        case 'switch':
            return '<i class="bi bi-hdd-network"></i>';
        case 'cpe':
            return '<i class="bi bi-reception-4"></i>';
        case 'servidor':
            return '<i class="bi bi-database"></i>';
        case 'pc':
            return '<i class="bi bi-display"></i>';
        case 'iot':
            return '<i class="bi bi-camera-video"></i>';
        default:
            return '<i class="bi bi-hdd"></i>';
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

        const widthOrigen = elOrigen ? elOrigen.offsetWidth : 175;
        const heightOrigen = elOrigen ? elOrigen.offsetHeight : 80;
        const widthDestino = elDestino ? elDestino.offsetWidth : 175;
        const heightDestino = elDestino ? elDestino.offsetHeight : 80;

        // Puntos centrales en las coordenadas locales del viewport
        const x1 = parseFloat(origenNode.pos_x) + widthOrigen / 2;
        const y1 = parseFloat(origenNode.pos_y) + heightOrigen / 2;
        const x2 = parseFloat(destinoNode.pos_x) + widthDestino / 2;
        const y2 = parseFloat(destinoNode.pos_y) + heightDestino / 2;

        // Determinar estado de conectividad entre ambos nodos
        const origenStatus = topologíaState.pingMap[origenNode.id]?.status === 'online';
        const destinoStatus = topologíaState.pingMap[destinoNode.id]?.status === 'online';
        const isLinkOnline = origenStatus && destinoStatus;

        // Grupo SVG para la línea
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.setAttribute('class', 'topology-link-group');
        g.setAttribute('data-enlace-id', enlace.id);

        // Línea principal
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', x1);
        line.setAttribute('y1', y1);
        line.setAttribute('x2', x2);
        line.setAttribute('y2', y2);
        line.setAttribute('class', isLinkOnline ? 'topology-line-active' : 'topology-line-inactive');
        line.setAttribute('stroke', isLinkOnline ? '#198754' : '#dc3545');
        line.setAttribute('stroke-width', '2.5');
        line.setAttribute('stroke-dasharray', isLinkOnline ? '6, 6' : '4, 4');

        g.appendChild(line);

        // Etiqueta en el punto medio del enlace
        const midX = (x1 + x2) / 2;
        const midY = (y1 + y2) / 2;

        const textBg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        textBg.setAttribute('x', midX - 30);
        textBg.setAttribute('y', midY - 9);
        textBg.setAttribute('width', '60');
        textBg.setAttribute('height', '18');
        textBg.setAttribute('rx', '4');
        textBg.setAttribute('fill', '#ffffff');
        textBg.setAttribute('stroke', isLinkOnline ? '#198754' : '#dc3545');
        textBg.setAttribute('stroke-width', '1');

        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', midX);
        text.setAttribute('y', midY + 3);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('fill', '#334155');
        text.setAttribute('font-size', '9');
        text.setAttribute('font-weight', '600');
        text.textContent = enlace.etiqueta || (enlace.tipo_enlace === 'ethernet' ? 'Ethernet' : 'Wi-Fi');

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
