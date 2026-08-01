<?php
require_once __DIR__ . '/../DAO/MikrotikDAO.php';
require_once __DIR__ . '/../DAO/EquipoDAO.php';

$mikrotiks = (new MikrotikDAO())->listarActivos();
$equipos = (new EquipoDAO())->listarActivos();
?>
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2 class="h4 mb-0"><i class="bi bi-signpost-split text-primary me-2"></i> Herramienta Traceroute</h2>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
            <i class="bi bi-diagram-3 me-1"></i> Mapa de Ruta de Red
        </span>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form id="formTraceroute" onsubmit="runTraceroute(event)">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="tracerouteTarget" class="form-label text-muted small text-uppercase fw-bold">IP o Dominio Destino</label>
                    <input type="text" class="form-control" id="tracerouteTarget" placeholder="Ej. 8.8.8.8 o google.com" required>
                </div>
                <div class="col-md-5">
                    <label for="selectQuickDevice" class="form-label text-muted small text-uppercase fw-bold">O Seleccionar Dispositivo Registrado</label>
                    <select class="form-select" id="selectQuickDevice" onchange="onSelectQuickDevice(this.value)">
                        <option value="">-- Seleccionar Dispositivo --</option>
                        <optgroup label="MikroTiks">
                            <?php foreach ($mikrotiks as $m): 
                                $nombreM = !empty($m['alias']) ? $m['alias'] : (!empty($m['nombre']) ? $m['nombre'] : 'MikroTik #' . $m['id']);
                            ?>
                                <option value="<?php echo htmlspecialchars($m['ip_address']); ?>"><?php echo htmlspecialchars($nombreM . ' (' . $m['ip_address'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Equipos">
                            <?php foreach ($equipos as $e): ?>
                                <option value="<?php echo htmlspecialchars($e['ip_address']); ?>"><?php echo htmlspecialchars($e['nombre'] . ' (' . $e['ip_address'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold" id="btnRunTraceroute">
                        <i class="bi bi-play-fill me-1"></i> Rastrear Ruta
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Canvas Interactivo del Mapa de Ruta (Estilo Topología) -->
<div class="card border-0 shadow-sm" id="tracerouteCanvasCard" style="display: none;">
    <div class="card-header bg-white border-bottom-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title fs-6 fw-bold mb-0">
                <i class="bi bi-diagram-3-fill text-primary me-2"></i>Mapa Topológico de Saltos
            </h5>
            <small class="text-muted" id="tracerouteTargetBadge">Destino: --</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div id="tracerouteStatusBadge">
                <span class="badge bg-info text-dark"><i class="bi bi-arrow-repeat spin me-1"></i> Rastreando saltos...</span>
            </div>
            <button class="btn btn-dark btn-sm shadow-sm ms-2" onclick="toggleFullScreenTraceroute()" title="Pantalla Completa">
                <i class="bi bi-arrows-fullscreen"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0 position-relative">
        <div id="traceroute-workspace" class="topology-clean-workspace position-relative overflow-hidden" style="min-height: 480px; background-color: #f8f9fa; background-image: radial-gradient(#cbd5e1 1px, transparent 1px); background-size: 20px 20px;">
            
            <div id="traceroute-viewport" class="position-absolute top-0 start-0 w-100 h-100" style="transform-origin: 0 0; transition: transform 0.05s linear;">
                <svg id="traceroute-svg" class="position-absolute top-0 start-0 w-100 h-100" style="z-index: 1; pointer-events: none; overflow: visible;">
                    <g id="traceroute-links-layer"></g>
                </svg>

                <div id="traceroute-nodes-layer" class="position-absolute top-0 start-0 w-100 h-100" style="z-index: 2;"></div>
            </div>

            <!-- Controles Flotantes de Zoom -->
            <div class="position-absolute bottom-0 end-0 m-3 d-flex gap-2 align-items-center" style="z-index: 10;">
                <div class="btn-group shadow-sm bg-white rounded border">
                    <button class="btn btn-light btn-sm" onclick="zoomTracerouteCanvas(1.2)" title="Acercar">
                        <i class="bi bi-zoom-in"></i>
                    </button>
                    <button class="btn btn-light btn-sm border-start border-end" onclick="zoomTracerouteCanvas(0.8)" title="Alejar">
                        <i class="bi bi-zoom-out"></i>
                    </button>
                    <span id="traceroute-zoom-level" class="btn btn-light btn-sm disabled text-dark fw-bold" style="font-size: 0.75rem;">100%</span>
                </div>
                <button class="btn btn-light border btn-sm shadow-sm" onclick="resetTracerouteCanvas()" title="Restablecer Vista">
                    <i class="bi bi-arrows-angle-contract me-1"></i> Restablecer
                </button>
            </div>

        </div>
    </div>
</div>
