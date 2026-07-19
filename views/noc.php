<style>
/* -------------------------------------------
   NOC MODE - SCI-FI / SPACESHIP UI THEME
--------------------------------------------- */
@import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@500;700;900&display=swap');

body.noc-mode {
    background-color: #03070b !important;
    background-image: 
        linear-gradient(rgba(0, 255, 255, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 255, 255, 0.03) 1px, transparent 1px);
    background-size: 30px 30px;
    color: #a0d2eb !important;
    font-family: 'Share Tech Mono', monospace, sans-serif !important;
    margin: 0;
    overflow-x: hidden;
}

body.noc-mode #sidebar, 
body.noc-mode .navbar, 
body.noc-mode .container-fluid.text-end { 
    display: none !important; 
}

body.noc-mode .main-wrapper { padding-left: 0 !important; }
body.noc-mode .content-area { 
    padding: 0 !important; 
    background: transparent !important; 
    min-height: 100vh;
}

body.noc-mode #main-content { 
    padding: 3rem !important; 
    position: relative;
    z-index: 2;
}

/* Efecto de viñeta y escáner CRT sutil */
body.noc-mode::after {
    content: " ";
    display: block;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.1) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
    z-index: 9999;
    background-size: 100% 2px, 3px 100%;
    pointer-events: none;
    opacity: 0.3;
}

/* Header */
.noc-header { 
    border-bottom: 1px solid rgba(0, 255, 255, 0.3);
    padding-bottom: 1.5rem; 
    margin-bottom: 3rem; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    box-shadow: 0 10px 20px -10px rgba(0, 255, 255, 0.1);
}

.noc-header h1 {
    font-family: 'Orbitron', sans-serif;
    color: #fff;
    text-shadow: 0 0 10px rgba(0, 255, 255, 0.8), 0 0 20px rgba(0, 255, 255, 0.5);
    margin-bottom: 0;
    letter-spacing: 4px;
    font-size: 2.5rem;
}

#noc-clock {
    font-family: 'Share Tech Mono', monospace;
    color: #0ff;
    text-shadow: 0 0 8px rgba(0, 255, 255, 0.5);
    font-size: 1.8rem;
    margin-top: 10px;
    letter-spacing: 2px;
}

/* Botón Salir */
.btn-sci-fi {
    background: rgba(0, 255, 255, 0.1);
    border: 1px solid #0ff;
    color: #0ff;
    font-family: 'Orbitron', sans-serif;
    text-transform: uppercase;
    letter-spacing: 2px;
    transition: all 0.3s ease;
    box-shadow: inset 0 0 10px rgba(0, 255, 255, 0.2);
}
.btn-sci-fi:hover {
    background: #0ff;
    color: #000;
    box-shadow: 0 0 20px rgba(0, 255, 255, 0.8);
}

/* Tarjetas (HUD Panels) */
.noc-card { 
    background: rgba(5, 12, 24, 0.8);
    border: 1px solid rgba(0, 255, 255, 0.3);
    border-radius: 4px; 
    padding: 30px; 
    text-align: center; 
    position: relative;
    box-shadow: inset 0 0 30px rgba(0, 255, 255, 0.05), 0 0 15px rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
}

/* Decoraciones en esquinas estilo sci-fi */
.noc-card::before, .noc-card::after {
    content: ''; position: absolute; width: 15px; height: 15px; border: 2px solid transparent;
}
.noc-card::before { top: -1px; left: -1px; border-top-color: #0ff; border-left-color: #0ff; }
.noc-card::after { bottom: -1px; right: -1px; border-bottom-color: #0ff; border-right-color: #0ff; }

.noc-card h3 { 
    color: #8ab4f8; 
    font-size: 1.1rem; 
    text-transform: uppercase; 
    letter-spacing: 3px; 
    font-family: 'Orbitron', sans-serif;
    margin-bottom: 15px;
}

.noc-card .value { 
    font-size: 5rem; 
    font-weight: 700; 
    font-family: 'Share Tech Mono', monospace;
    text-shadow: 0 0 15px currentColor;
}

.noc-card.success { border-color: rgba(46, 213, 115, 0.4); box-shadow: inset 0 0 30px rgba(46, 213, 115, 0.05); }
.noc-card.success::before { border-top-color: #2ed573; border-left-color: #2ed573; }
.noc-card.success::after { border-bottom-color: #2ed573; border-right-color: #2ed573; }
.noc-card.success .value { color: #2ed573; }

.noc-card.danger { border-color: rgba(255, 71, 87, 0.4); box-shadow: inset 0 0 30px rgba(255, 71, 87, 0.1); }
.noc-card.danger::before { border-top-color: #ff4757; border-left-color: #ff4757; }
.noc-card.danger::after { border-bottom-color: #ff4757; border-right-color: #ff4757; }
.noc-card.danger .value { color: #ff4757; text-shadow: 0 0 20px rgba(255, 71, 87, 0.8); }

/* Tabla Sci-Fi */
.table-container {
    background: rgba(5, 12, 24, 0.8);
    border: 1px solid rgba(0, 255, 255, 0.2);
    padding: 20px;
    border-radius: 4px;
    position: relative;
}

.table-container::before {
    content: ''; position: absolute; top: -1px; left: 10%; width: 20%; height: 2px; background: #0ff; box-shadow: 0 0 10px #0ff;
}

.noc-table { 
    width: 100%; 
    color: #a0d2eb; 
    border-collapse: collapse; 
}

.noc-table th { 
    background: rgba(0, 255, 255, 0.05); 
    padding: 18px 15px; 
    text-align: left; 
    border-bottom: 2px solid rgba(0, 255, 255, 0.5); 
    color: #0ff; 
    text-transform: uppercase; 
    font-size: 0.95rem; 
    letter-spacing: 2px; 
    font-family: 'Orbitron', sans-serif;
}

.noc-table td { 
    padding: 18px 15px; 
    border-bottom: 1px solid rgba(0, 255, 255, 0.1); 
    font-size: 1.15rem; 
    transition: background 0.2s;
}

.noc-table tbody tr:hover td {
    background: rgba(0, 255, 255, 0.05);
}

.noc-badge { 
    background: transparent; 
    color: #ff4757; 
    border: 1px solid #ff4757;
    padding: 6px 12px; 
    border-radius: 2px; 
    font-weight: bold; 
    font-size: 0.85rem; 
    letter-spacing: 2px; 
    box-shadow: inset 0 0 10px rgba(255, 71, 87, 0.3);
}

.text-secondary { color: #6a8ea5 !important; }

/* Animaciones */
.blink { animation: blinker 1.5s linear infinite; }
@keyframes blinker { 50% { opacity: 0.2; } }

/* Título de Incidentes */
.incident-title {
    font-family: 'Orbitron', sans-serif;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 3px;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
}
.incident-title i {
    color: #ff4757;
    margin-right: 15px;
    text-shadow: 0 0 15px rgba(255, 71, 87, 0.8);
}

/* Terminal Falsa */
.noc-terminal {
    background: rgba(0, 5, 10, 0.9);
    border: 1px solid rgba(0, 255, 255, 0.3);
    border-radius: 4px;
    padding: 15px;
    height: 185px; /* Igualar altura aprox a las cards */
    overflow: hidden;
    font-family: 'Share Tech Mono', monospace;
    font-size: 0.85rem;
    color: #0ff;
    box-shadow: inset 0 0 20px rgba(0, 255, 255, 0.05);
    position: relative;
    display: flex;
    flex-direction: column;
}
.noc-terminal::before {
    content: "SYS_LOG // TERMINAL";
    display: block;
    border-bottom: 1px solid rgba(0, 255, 255, 0.2);
    padding-bottom: 5px;
    margin-bottom: 10px;
    font-family: 'Orbitron', sans-serif;
    letter-spacing: 2px;
    color: #8ab4f8;
    font-size: 0.9rem;
}
.terminal-content { flex: 1; overflow: hidden; display: flex; flex-direction: column; justify-content: flex-end; }
.terminal-line { margin: 2px 0; opacity: 0.8; word-wrap: break-word; }
.terminal-cursor { display: inline-block; width: 8px; height: 12px; background: #0ff; animation: blinker 1s infinite; vertical-align: middle; margin-left: 5px;}
</style>

<div class="noc-header">
    <div>
        <h1 class="mb-0">Elissa Centro - Software Escobedo</h1>
        <div id="noc-clock">00:00:00</div>
    </div>
    <button class="btn btn-sci-fi btn-lg" onclick="window.exitNocMode()">
        <i class="bi bi-power me-2"></i> ABORT_SEC
    </button>
</div>

<div class="row g-5">
    <div class="col-md-4">
        <div class="noc-card success" style="height: 100%;">
            <h3>STATUS // ONLINE</h3>
            <div class="value" id="noc-online-count"><i class="bi bi-check2-all"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="noc-card danger" style="height: 100%;">
            <h3>CRITICAL // FAILURES</h3>
            <div class="value" id="noc-offline-count">0</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="noc-terminal" style="height: 100%;">
            <div class="terminal-content" id="fake-terminal-content">
                <div class="terminal-line">> INICIANDO PROTOCOLOS DE RED...</div>
            </div>
            <div><span class="terminal-cursor"></span></div>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-12">
        <div class="incident-title mb-4 border-bottom border-secondary pb-3" style="border-color: rgba(0, 255, 255, 0.3) !important;">
            <i class="bi bi-exclamation-triangle-fill blink"></i> 
            TELEMETRY // INCIDENTS LOG
        </div>
        
        <div class="table-container">
            <table class="noc-table">
                <thead>
                    <tr>
                        <th>SYS_STATE</th>
                        <th>TARGET_TYPE</th>
                        <th>NODE_IDENTIFIER</th>
                        <th>TIMESTAMP_LOG</th>
                    </tr>
                </thead>
                <tbody id="noc-incidentes-body">
                    <tr><td colspan="4" class="text-center py-5" style="color: #0ff; letter-spacing: 2px;">FETCHING TELEMETRY...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Script de Terminal Falsa
    (function() {
        const termLines = [
            "Conexión segura establecida en sector principal...",
            "Ping a pasarela 192.168.x.1 ... OK",
            "Cargando flujos de telemetría...",
            "Desencriptando paquetes de red ... 100%",
            "Protocolos de enrutamiento OSPF / BGP inicializados.",
            "Escaneando latencia en nodos perimetrales...",
            "Optimizando tráfico en backbone principal...",
            "Buscando actualizaciones de firmware de antenas...",
            "Ejecutando diagnóstico de memoria RAM...",
            "MemOK. Temperatura CPU estable.",
            "Daemon de Syslog recolectando logs locales.",
            "Sincronizando reloj atómico NTP...",
            "Validando sesiones PPPoE activas...",
            "Comprobando integridad de base de datos SQL...",
            "Firewall bloqueando intentos de acceso externo..."
        ];
        
        let terminalInterval;
        
        // Iniciar el efecto de la terminal cuando estemos en la vista noc
        if (typeof window.initTerminal === 'undefined') {
            window.initTerminal = function() {
                const termContainer = document.getElementById('fake-terminal-content');
                if(!termContainer) return;
                
                if(terminalInterval) clearInterval(terminalInterval);
                
                // Función recursiva para tiempos aleatorios
                function typeLine() {
                    if(!document.body.classList.contains('noc-mode')) return;
                    
                    const randomLine = termLines[Math.floor(Math.random() * termLines.length)];
                    const time = new Date().toISOString().substring(11,19);
                    const p = document.createElement('div');
                    p.className = 'terminal-line';
                    p.innerText = `[${time}] > ${randomLine}`;
                    
                    termContainer.appendChild(p);
                    
                    if (termContainer.childElementCount > 6) {
                        termContainer.removeChild(termContainer.firstChild);
                    }
                    
                    terminalInterval = setTimeout(typeLine, 1500 + Math.random() * 2500);
                }
                
                typeLine();
            };
        }
        
        // Si el DOM ya cargó la terminal, iniciarla
        setTimeout(() => { if(document.getElementById('fake-terminal-content')) window.initTerminal(); }, 500);
    })();
</script>
