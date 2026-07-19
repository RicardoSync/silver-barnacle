let nocInterval;
let nocClockInterval;

window.initNocModule = function() {
    // Activar modo oscuro aislando estilos
    document.body.classList.add('noc-mode');
    
    // Iniciar reloj
    actualizarRelojNoc();
    if(nocClockInterval) clearInterval(nocClockInterval);
    nocClockInterval = setInterval(actualizarRelojNoc, 1000);
    
    // Cargar datos inmediatos
    cargarDatosNoc();
    
    // Polling específico para NOC cada 10 segundos
    if(nocInterval) clearInterval(nocInterval);
    nocInterval = setInterval(cargarDatosNoc, 10000);
};

window.exitNocMode = function() {
    document.body.classList.remove('noc-mode');
    clearInterval(nocInterval);
    clearInterval(nocClockInterval);
    loadView('dashboard'); // Regresa al dashboard normal
};

function actualizarRelojNoc() {
    const now = new Date();
    const el = document.getElementById('noc-clock');
    if (el) {
        el.innerText = now.toLocaleTimeString('es-ES', { hour12: false });
    }
}

function cargarDatosNoc() {
    // Llamar a HistorialCaidaController activas
    $.ajax({
        url: 'controllers/HistorialCaidaController.php',
        type: 'GET',
        data: { action: 'activas' },
        dataType: 'json',
        success: function(res) {
            const countEl = $('#noc-offline-count');
            if (countEl.length === 0) return; // Si ya salimos del NOC, no actualizar
            
            countEl.text(res.length);
            
            if (res.length > 0) {
                countEl.parent().addClass('blink');
                
                let tbody = '';
                res.forEach(c => {
                    let tipo = c.tipo_nodo === 'mikrotik' ? 'ROUTER / MIKROTIK' : 'ANTENA / EQUIPO';
                    tbody += `
                        <tr>
                            <td><span class="noc-badge blink">OFFLINE</span></td>
                            <td class="text-secondary">${tipo}</td>
                            <td class="fw-bold">${c.nombre_nodo}</td>
                            <td>${c.fecha_caida}</td>
                        </tr>
                    `;
                });
                $('#noc-incidentes-body').html(tbody);
            } else {
                countEl.parent().removeClass('blink');
                $('#noc-incidentes-body').html('<tr><td colspan="4" class="text-center text-success py-5 fs-5"><i class="bi bi-shield-check me-2"></i> Todos los sistemas operando normalmente. No hay incidentes activos.</td></tr>');
            }
        },
        error: function() {
            // Manejar errores de red silenciosamente en el NOC
        }
    });
}
