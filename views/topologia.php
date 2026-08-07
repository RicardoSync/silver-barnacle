<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!-- Estilos optimizados y ultra limpios para Topología de Red (Solo Iconos) -->
<style>
  #topology-workspace-wrapper {
    position: relative;
    width: 100%;
    height: 720px;
    background-color: #0b0f19;
    background-image: 
      radial-gradient(circle, rgba(255,255,255,0.06) 1px, transparent 1px);
    background-size: 28px 28px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: inset 0 0 30px rgba(0,0,0,0.6);
    user-select: none;
  }

  #topology-workspace-wrapper.is-fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 9999;
    border-radius: 0;
  }

  #topology-workspace {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    cursor: grab;
  }

  #topology-viewport {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    transform-origin: 0 0;
    transition: transform 0.05s ease-out;
  }

  #topology-links-layer {
    position: absolute;
    top: 0;
    left: 0;
    width: 5000px;
    height: 5000px;
    pointer-events: all;
    overflow: visible;
  }

  #topology-nodes-layer {
    position: absolute;
    top: 0;
    left: 0;
    width: 5000px;
    height: 5000px;
    pointer-events: none;
  }

  /* Nodo Minimalista Basado Solo en Iconos */
  .topology-node-card {
    position: absolute;
    pointer-events: auto;
    width: 72px;
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: move;
    transition: transform 0.15s ease-out, opacity 0.2s;
    z-index: 10;
  }

  .topology-node-card:hover {
    z-index: 50;
  }

  .node-icon-circle {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: #1e293b;
    border: 2.5px solid #334155;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    position: relative;
    box-shadow: 0 4px 12px rgba(0,0,0,0.5);
    transition: all 0.2s ease-in-out;
  }

  .topology-node-card:hover .node-icon-circle {
    transform: scale(1.15);
    border-color: #38bdf8;
    box-shadow: 0 0 20px rgba(56, 189, 248, 0.6);
  }

  .topology-node-card.selected-source .node-icon-circle {
    border-color: #f59e0b !important;
    box-shadow: 0 0 25px rgba(245, 158, 11, 0.8) !important;
    animation: pulseSelect 1.2s infinite;
  }

  /* Colores e Iconos por Tipo */
  .device-type-router { color: #f59e0b; }
  .device-type-ap { color: #38bdf8; }
  .device-type-switch { color: #c084fc; }
  .device-type-cpe { color: #34d399; }
  .device-type-servidor { color: #f472b6; }
  .device-type-pc { color: #94a3b8; }
  .device-type-iot { color: #fb7185; }

  /* LED de Estado */
  .status-led-badge {
    position: absolute;
    top: 0;
    right: 0;
    width: 13px;
    height: 13px;
    border-radius: 50%;
    border: 2px solid #0b0f19;
    background-color: #94a3b8;
  }

  .online .status-led-badge { background-color: #22c55e; box-shadow: 0 0 10px #22c55e; }
  .offline .status-led-badge { background-color: #ef4444; box-shadow: 0 0 10px #ef4444; }
  .pending .status-led-badge { background-color: #eab308; }

  /* Etiqueta Nombre Limpia */
  .node-label-pill {
    margin-top: 6px;
    font-size: 0.72rem;
    font-weight: 600;
    color: #f1f5f9;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255,255,255,0.1);
    padding: 2px 8px;
    border-radius: 12px;
    white-space: nowrap;
    max-width: 110px;
    overflow: hidden;
    text-overflow: ellipsis;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.4);
    transition: all 0.15s ease;
  }

  .topology-node-card:hover .node-label-pill {
    background: #0f172a;
    border-color: #38bdf8;
    color: #38bdf8;
  }

  /* Popover / Hover Card Detallado */
  .node-hover-details {
    display: none;
    position: absolute;
    bottom: 115%;
    left: 50%;
    transform: translateX(-50%);
    width: 220px;
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(8px);
    border: 1px solid #38bdf8;
    border-radius: 10px;
    padding: 10px 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.85);
    z-index: 200;
    pointer-events: auto;
  }

  .topology-node-card:hover .node-hover-details {
    display: block;
    animation: fadeInFast 0.15s ease-out;
  }

  @keyframes fadeInFast {
    from { opacity: 0; transform: translate(-50%, 6px); }
    to { opacity: 1; transform: translate(-50%, 0); }
  }

  .hover-details-title {
    font-weight: 700;
    font-size: 0.85rem;
    color: #38bdf8;
    border-bottom: 1px solid #334155;
    padding-bottom: 4px;
    margin-bottom: 6px;
  }

  .hover-stat-item {
    font-size: 0.75rem;
    color: #cbd5e1;
    margin-bottom: 3px;
  }

  .hover-actions-bar {
    display: flex;
    gap: 4px;
    margin-top: 8px;
    padding-top: 6px;
    border-top: 1px solid #334155;
    flex-wrap: wrap;
  }

  .btn-hover-action {
    flex: 1;
    font-size: 0.68rem;
    padding: 2px 4px;
  }

  /* Banner Modo Conexión */
  #connect-mode-banner {
    position: absolute;
    top: 15px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 100;
    background: rgba(245, 158, 11, 0.95);
    color: #0f172a;
    padding: 6px 18px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.85rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.6);
  }

  .search-pulse .node-icon-circle {
    animation: searchPulse 0.8s infinite alternate;
  }

  @keyframes searchPulse {
    from { transform: scale(1); border-color: #38bdf8; }
    to { transform: scale(1.3); border-color: #f59e0b; box-shadow: 0 0 35px #f59e0b; }
  }

  @keyframes pulseSelect {
    0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.8); }
    70% { box-shadow: 0 0 0 14px rgba(245, 158, 11, 0); }
    100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
  }

  .topology-node-fade {
    opacity: 0.2;
  }

  .topology-link-fade {
    opacity: 0.1;
  }
</style>

<!-- Header Principal de Topología -->
<div class="app-content-header">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-md-6 col-12">
        <h3 class="mb-0 fw-bold d-flex align-items-center">
          <i class="bi bi-diagram-3-fill text-primary me-2"></i> Mapa de Topología de Red
        </h3>
        <p class="text-muted small mb-0">Vista simplificada con íconos fluidos, medios de red y diagnóstico interactivo.</p>
      </div>
      <div class="col-md-6 col-12 text-md-end mt-2 mt-md-0 d-flex gap-2 justify-content-md-end flex-wrap">
        <button class="btn btn-sm btn-outline-secondary" onclick="importarDispositivos()">
          <i class="bi bi-arrow-down-square me-1"></i> Importar Equipos
        </button>
        <button class="btn btn-sm btn-success" onclick="abrirModalNuevoNodo()">
          <i class="bi bi-plus-lg me-1"></i> Agregar Nodo
        </button>
      </div>
    </div>
  </div>
</div>

<div class="app-content mt-3">
  <div class="container-fluid">

    <!-- Barra de Herramientas y Filtros -->
    <div class="card card-outline card-primary shadow-sm mb-3">
      <div class="card-body py-2">
        <div class="row align-items-center g-2">
          
          <!-- Búsqueda -->
          <div class="col-md-3 col-12">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
              <input type="text" id="search-topology-input" class="form-control" placeholder="Buscar por nombre o IP..." onkeyup="onBuscarNodo(this.value)">
            </div>
          </div>

          <!-- Controles de Modo Conectar y Zoom -->
          <div class="col-md-6 col-12 text-center">
            <div class="btn-group btn-group-sm me-2">
              <button id="btn-toggle-connect" class="btn btn-outline-primary" onclick="toggleModoConectar()" title="Crear Enlace (Clic Origen -> Clic Destino)">
                <i class="bi bi-diagram-2 me-1"></i> Crear Conexión
              </button>
            </div>

            <div class="btn-group btn-group-sm me-2">
              <button class="btn btn-outline-secondary" onclick="zoomTopologia(1.2)" title="Acercar"><i class="bi bi-zoom-in"></i></button>
              <button class="btn btn-outline-secondary" onclick="zoomTopologia(0.8)" title="Alejar"><i class="bi bi-zoom-out"></i></button>
              <button class="btn btn-outline-secondary" onclick="resetWorkspaceView()" title="Restablecer Vista"><i class="bi bi-arrow-counterclockwise"></i> 100%</button>
            </div>

            <span class="badge bg-dark me-2" id="topology-zoom-level">100%</span>

            <button class="btn btn-sm btn-outline-dark" onclick="toggleFullScreenTopologia()">
              <i class="bi bi-arrows-fullscreen me-1"></i> <span id="btn-fullscreen-text">Pantalla Completa</span>
            </button>
          </div>

          <!-- Estadísticas Rápidas -->
          <div class="col-md-3 col-12 text-md-end">
            <span class="badge bg-primary me-1">Nodos: <strong id="stat-total-nodos">0</strong></span>
            <span class="badge bg-success me-1">Online: <strong id="stat-online-nodos">0</strong></span>
            <span class="badge bg-danger me-1">Caídos: <strong id="stat-offline-nodos">0</strong></span>
            <span class="badge bg-info text-dark">Enlaces: <strong id="stat-total-enlaces">0</strong></span>
          </div>

        </div>
      </div>
    </div>

    <!-- Canvas Principal de Topología -->
    <div id="topology-workspace-wrapper">
      
      <!-- Banner de Modo Conexión Activa -->
      <div id="connect-mode-banner" class="d-none align-items-center">
        <i class="bi bi-cursor-fill me-2"></i>
        <span>MODO CONEXIÓN ACTIVO: Haz clic en el Ícono Origen y luego en el Ícono Destino</span>
        <button class="btn btn-xs btn-dark ms-3" onclick="toggleModoConectar(false)">Cancelar</button>
      </div>

      <div id="topology-workspace">
        <div id="topology-viewport">
          <!-- Capa SVG para Enlaces y Señales Animadas -->
          <svg id="topology-links-layer"></svg>
          <!-- Capa HTML para Tarjetas de Nodos -->
          <div id="topology-nodes-layer"></div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-2 px-1">
      <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Pasa el cursor sobre un ícono para ver sus detalles. Arrastra los íconos para moverlos.</small>
      <small id="topologia-last-update" class="text-muted"><i class="bi bi-clock me-1"></i> Actualizando...</small>
    </div>

  </div>
</div>

<!-- Modal para Crear / Editar Nodo Custom -->
<div class="modal fade" id="modalNodo" tabindex="-1" aria-labelledby="modalNodoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-primary text-white py-2">
        <h5 class="modal-title fs-6 fw-bold" id="modalNodoLabel">
          <i class="bi bi-hdd-rack me-2"></i> Registrar Equipo en Topología
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formNodo" onsubmit="guardarNodo(event)">
        <input type="hidden" id="nodo_id" name="id" value="0">
        <input type="hidden" id="nodo_pos_x" name="pos_x" value="150">
        <input type="hidden" id="nodo_pos_y" name="pos_y" value="150">
        <div class="modal-body">
          <div class="mb-3">
            <label for="nodo_nombre" class="form-label fw-bold">Nombre del Dispositivo <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nodo_nombre" name="nombre" placeholder="ej. Router Central Torre A" required>
          </div>
          <div class="mb-3">
            <label for="nodo_ip" class="form-label fw-bold">Dirección IP <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nodo_ip" name="ip_address" placeholder="ej. 10.0.0.1" required>
          </div>
          <div class="mb-3">
            <label for="nodo_tipo" class="form-label fw-bold">Tipo de Dispositivo / Icono</label>
            <select class="form-select" id="nodo_tipo" name="tipo">
              <option value="router">Router / MikroTik (Icono Router)</option>
              <option value="ap">Access Point / Antena (Icono AP)</option>
              <option value="switch">Switch de Red (Icono Switch)</option>
              <option value="cpe">CPE / Router de Usuario (Icono Cliente)</option>
              <option value="servidor">Servidor / Datacenter (Icono Server)</option>
              <option value="pc">Computadora / Laptop</option>
              <option value="iot">Cámara / IoT</option>
            </select>
          </div>
        </div>
        <div class="modal-footer py-2 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Guardar Equipo</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal para Crear Enlace / Conexión -->
<div class="modal fade" id="modalEnlace" tabindex="-1" aria-labelledby="modalEnlaceLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-primary text-white py-2">
        <h5 class="modal-title fs-6 fw-bold" id="modalEnlaceLabel">
          <i class="bi bi-diagram-2 me-2"></i> Crear Conexión entre Equipos
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEnlace" onsubmit="guardarEnlace(event)">
        <div class="modal-body">
          <div class="mb-3">
            <label for="enlace_origen" class="form-label fw-bold">Equipo Origen <span class="text-danger">*</span></label>
            <select class="form-select" id="enlace_origen" name="nodo_origen_id" required></select>
          </div>
          <div class="mb-3">
            <label for="enlace_destino" class="form-label fw-bold">Equipo Destino <span class="text-danger">*</span></label>
            <select class="form-select" id="enlace_destino" name="nodo_destino_id" required></select>
          </div>
          <div class="mb-3">
            <label for="enlace_tipo" class="form-label fw-bold">Medio / Tipo de Conexión <span class="text-danger">*</span></label>
            <select class="form-select" id="enlace_tipo" name="tipo_enlace" required>
              <option value="inalambrico">Inalámbrico / Wi-Fi (Línea segmentada verde)</option>
              <option value="ethernet">Cable Ethernet / UTP (Línea sólida verde)</option>
              <option value="fibra">Fibra Óptica (Línea brillante cyan/azul)</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="enlace_etiqueta" class="form-label fw-bold">Comentario / Ancho de Banda (Opcional)</label>
            <input type="text" class="form-control" id="enlace_etiqueta" name="etiqueta" placeholder="ej. 1 Gbps / Fibra monomodo">
          </div>
        </div>
        <div class="modal-footer py-2 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-link-45deg me-1"></i> Establecer Conexión</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Terminal para Diagnóstico SSE -->
<div class="modal fade" id="modalTerminal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg bg-dark text-white border-secondary">
      <div class="modal-header border-secondary py-2">
        <h5 class="modal-title fs-6 fw-bold text-info" id="terminal-modal-title">
          <i class="bi bi-terminal me-2"></i> Diagnóstico en Tiempo Real
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="detenerHerramientaRed()"></button>
      </div>
      <div class="modal-body p-0">
        <pre id="terminal-output-body" style="background:#090d16; color:#38bdf8; font-family:monospace; padding:15px; height:320px; overflow-y:auto; margin:0; font-size:0.85rem;"></pre>
      </div>
      <div class="modal-footer border-secondary py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="detenerHerramientaRed()">Cerrar Consola</button>
      </div>
    </div>
  </div>
</div>
