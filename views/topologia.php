<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit('Acceso no autorizado');
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-diagram-3 text-secondary me-2"></i> Topología de Red</h2>
    <div class="d-flex align-items-center gap-2">
        <!-- Buscador de Equipos -->
        <div class="topology-search-container me-2">
            <i class="bi bi-search topology-search-icon"></i>
            <input type="text" class="form-control topology-search-input" id="search-topology-input" placeholder="Buscar equipo o IP..." oninput="onBuscarNodo(this.value)">
        </div>
        <button class="btn btn-primary shadow-sm" onclick="abrirModalNuevoNodo()">
            <i class="bi bi-plus-circle me-1"></i> Nuevo Equipo
        </button>
        <button class="btn btn-success shadow-sm" onclick="abrirModalCrearEnlace()">
            <i class="bi bi-link-45deg me-1"></i> Nueva Conexión
        </button>
        <button class="btn btn-outline-primary shadow-sm" id="btn-toggle-connect" onclick="toggleModoConectar()">
            <i class="bi bi-diagram-2 me-1"></i> Modo Conectar (Clics)
        </button>
        <button class="btn btn-outline-secondary shadow-sm" onclick="importarDispositivos()">
            <i class="bi bi-download me-1"></i> Importar Equipos
        </button>
        <button class="btn btn-dark shadow-sm" onclick="toggleFullScreenTopologia()" title="Pantalla Completa">
            <i class="bi bi-arrows-fullscreen"></i>
        </button>
    </div>
</div>

<!-- Resumen e Intervalo de Ping -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-3">
            <span><strong>Total:</strong> <span id="stat-total-nodos" class="badge bg-secondary">0</span></span>
            <span><strong>Online:</strong> <span id="stat-online-nodos" class="badge bg-success">0</span></span>
            <span><strong>Caídos:</strong> <span id="stat-offline-nodos" class="badge bg-danger">0</span></span>
            <span><strong>Enlaces:</strong> <span id="stat-total-enlaces" class="badge bg-info text-dark">0</span></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm" id="select-ping-interval" onchange="cambiarIntervaloPing(this.value)" style="width: 160px;">
                <option value="5000" selected>Ping cada 5s</option>
                <option value="10000">Ping cada 10s</option>
                <option value="30000">Ping cada 30s</option>
                <option value="0">Desactivar Ping</option>
            </select>
            <button class="btn btn-sm btn-success shadow-sm" onclick="ejecutarPingBatch()">
                <i class="bi bi-play-fill me-1"></i> Ping Ahora
            </button>
        </div>
    </div>
</div>

<!-- Banner Modo Conectar -->
<div id="connect-mode-banner" class="alert alert-info py-2 px-3 mb-3 d-none align-items-center justify-content-between shadow-sm">
    <div>
        <i class="bi bi-info-circle me-2"></i>
        <strong>Modo Conectar:</strong> Haz clic en el primer equipo (origen) y luego en el segundo equipo (destino).
    </div>
    <button class="btn btn-sm btn-outline-secondary" onclick="toggleModoConectar(false)">Cancelar</button>
</div>

<!-- Contenedor del Mapa Topológico -->
<div class="card shadow-sm border-0 mb-4" id="topology-workspace-wrapper">
    <div class="card-body p-0 position-relative">
        <div id="topology-workspace" class="topology-clean-workspace position-relative overflow-hidden">
            
            <div id="topology-viewport" class="position-absolute top-0 start-0 w-100 h-100" style="transform-origin: 0 0; transition: transform 0.05s linear;">
                <svg id="topology-svg" class="position-absolute top-0 start-0 w-100 h-100" style="z-index: 1; pointer-events: none; overflow: visible;">
                    <g id="topology-links-layer"></g>
                </svg>

                <div id="topology-nodes-layer" class="position-absolute top-0 start-0 w-100 h-100" style="z-index: 2;"></div>
            </div>

            <!-- Controles Flotantes de Zoom y Navegación -->
            <div class="position-absolute bottom-0 end-0 m-3 d-flex gap-2 align-items-center" style="z-index: 10;">
                <div class="btn-group shadow-sm bg-white rounded border">
                    <button class="btn btn-light btn-sm" onclick="zoomTopologia(1.2)" title="Acercar (Zoom In)">
                        <i class="bi bi-zoom-in"></i>
                    </button>
                    <button class="btn btn-light btn-sm border-start border-end" onclick="zoomTopologia(0.8)" title="Alejar (Zoom Out)">
                        <i class="bi bi-zoom-out"></i>
                    </button>
                    <span id="topology-zoom-level" class="btn btn-light btn-sm disabled text-dark fw-bold" style="font-size: 0.75rem;">100%</span>
                </div>
                <button class="btn btn-light border btn-sm shadow-sm" onclick="resetWorkspaceView()" title="Centrar / Restablecer Vista">
                    <i class="bi bi-arrows-angle-contract me-1"></i> Restablecer
                </button>
                <button class="btn btn-dark btn-sm shadow-sm" onclick="toggleFullScreenTopologia()" title="Pantalla Completa">
                    <i class="bi bi-arrows-fullscreen"></i>
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Modal Agregar / Editar Nodo -->
<div class="modal fade" id="modalNodo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalNodoLabel">
                    <i class="bi bi-hdd-rack me-1"></i> Registrar Equipo en Topología
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNodo" onsubmit="guardarNodo(event)">
                <div class="modal-body">
                    <input type="hidden" id="nodo_id" name="id" value="0">
                    <input type="hidden" id="nodo_pos_x" name="pos_x" value="150">
                    <input type="hidden" id="nodo_pos_y" name="pos_y" value="150">

                    <div class="mb-3">
                        <label for="nodo_nombre" class="form-label fw-bold">Nombre del Equipo / Antena</label>
                        <input type="text" class="form-control" id="nodo_nombre" name="nombre" placeholder="Ej. AP Sectorial 1" required>
                    </div>

                    <div class="mb-3">
                        <label for="nodo_ip" class="form-label fw-bold">Dirección IP</label>
                        <input type="text" class="form-control" id="nodo_ip" name="ip_address" placeholder="Ej. 192.168.1.1" required>
                    </div>

                    <div class="mb-3">
                        <label for="nodo_tipo" class="form-label fw-bold">Tipo de Dispositivo</label>
                        <select class="form-select" id="nodo_tipo" name="tipo">
                            <option value="router">MikroTik / Router</option>
                            <option value="ap" selected>AP / Access Point</option>
                            <option value="switch">Switch</option>
                            <option value="cpe">ST / Estación / CPE</option>
                            <option value="servidor">Servidor</option>
                            <option value="pc">PC / Computadora</option>
                            <option value="iot">Dispositivo IP / IoT</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Guardar Equipo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Crear Enlace -->
<div class="modal fade" id="modalEnlace" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalEnlaceLabel">
                    <i class="bi bi-diagram-2 me-1"></i> Crear Enlace
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEnlace" onsubmit="guardarEnlace(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="enlace_origen" class="form-label fw-bold">Equipo Origen</label>
                        <select class="form-select" id="enlace_origen" name="nodo_origen_id" required></select>
                    </div>

                    <div class="mb-3">
                        <label for="enlace_destino" class="form-label fw-bold">Equipo Destino</label>
                        <select class="form-select" id="enlace_destino" name="nodo_destino_id" required></select>
                    </div>

                    <div class="mb-3">
                        <label for="enlace_tipo" class="form-label fw-bold">Tipo de Enlace</label>
                        <select class="form-select" id="enlace_tipo" name="tipo_enlace">
                            <option value="inalambrico" selected>Inalámbrico (Wi-Fi / PtP)</option>
                            <option value="ethernet">Cable Ethernet (UTP / POE)</option>
                            <option value="fibra">Fibra Óptica</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="enlace_etiqueta" class="form-label fw-bold">Etiqueta (Opcional)</label>
                        <input type="text" class="form-control" id="enlace_etiqueta" name="etiqueta" placeholder="Ej. VLAN 100, 5.8 GHz">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-link-45deg me-1"></i> Crear Enlace</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Terminal Diagnóstico -->
<div class="modal fade" id="modalTerminal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg terminal-window">
            <div class="terminal-header">
                <div class="terminal-title">
                    <span class="terminal-dot red"></span>
                    <span class="terminal-dot yellow"></span>
                    <span class="terminal-dot green"></span>
                    <span class="ms-2" id="terminal-modal-title">CONSOLA DE DIAGNÓSTICO ELISSA</span>
                </div>
                <button type="button" class="btn-close btn-close-white" aria-label="Close" onclick="detenerHerramientaRed()"></button>
            </div>
            <div class="modal-body p-0">
                <div class="terminal-body" id="terminal-output-body">Iniciando terminal...<span class="terminal-cursor"></span></div>
            </div>
            <div class="modal-footer bg-dark border-0 d-flex justify-content-between p-3">
                <span class="text-muted small font-monospace">SSE Real-Time Diagnostic Tool</span>
                <button type="button" class="btn btn-sm btn-danger px-3 fw-bold" onclick="detenerHerramientaRed()">
                    <i class="bi bi-x-circle me-1"></i> Detener y Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
