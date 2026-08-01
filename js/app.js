document.addEventListener('DOMContentLoaded', () => {

    // Configurar la carga inicial y el Loader
    const loader = document.getElementById('loader-wrapper');
    const mainContainer = document.getElementById('main-content');

    function hideLoader() {
        if (loader) {
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.display = 'none';
            }, 500);
        }
    }

    function showLoader() {
        if (loader) {
            loader.style.display = 'flex';
            setTimeout(() => {
                loader.style.opacity = '1';
            }, 10);
        }
    }

    setTimeout(hideLoader, 600);

    // Lógica del Sidebar Collapse
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const sidebarCollapseBtn = document.getElementById('sidebarCollapse');

    if (sidebarCollapseBtn) {
        sidebarCollapseBtn.addEventListener('click', function () {
            sidebar.classList.toggle('active');
            if (window.innerWidth <= 768 && overlay) {
                overlay.classList.toggle('active');
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }

    // Navegación dinámica
    document.querySelectorAll('[data-view]').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();

            const viewName = e.currentTarget.getAttribute('data-view');
            if (viewName) {
                // Si la vista es detalles, necesitamos extraer el data-id
                const id = e.currentTarget.getAttribute('data-id');
                const params = id ? { id: id } : null;

                loadView(viewName, params);

                // Actualizar clase activa en el menú lateral
                document.querySelectorAll('#sidebar ul li').forEach(li => li.classList.remove('active'));
                e.currentTarget.parentElement.classList.add('active');

                // Cerrar en móviles si se hace click
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                }
            }
        });
    });

    // Cargar Vista
    window.loadView = function (viewName, params = null) {
        showLoader();

        if (typeof window.clearDashboardIntervals === 'function') {
            window.clearDashboardIntervals();
        }
        if (typeof window.clearEquiposDetallesIntervals === 'function') {
            window.clearEquiposDetallesIntervals();
        }

        let url = 'views/' + viewName + '.php';
        if (params) {
            const queryParams = new URLSearchParams(params).toString();
            url += '?' + queryParams;
        }

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Código HTTP ' + response.status);
                }
                return response.text();
            })
            .then(html => {
                mainContainer.innerHTML = html;
                initPlugins(viewName);
                hideLoader();
            })
            .catch(error => {
                mainContainer.innerHTML = '<div class="alert alert-danger">Error al cargar la vista solicitada (' + error.message + ').</div>';
                console.error('Error al cargar vista:', error);
                hideLoader();
            });
    }

    function initPlugins(viewName) {
        if (viewName === 'mikrotiks') {
            if (typeof initMikrotikModule === 'function') {
                initMikrotikModule();
            }
        } else if (viewName === 'mikrotik/detalles') {
            if (typeof initDetallesModule === 'function') {
                initDetallesModule();
            }
        } else if (viewName === 'dashboard') {
            if (typeof initDashboardModule === 'function') {
                initDashboardModule();
            }
        } else if (viewName === 'recursos') {
            if (typeof initRecursosModule === 'function') {
                initRecursosModule();
            }
        } else if (viewName === 'usuarios') {
            if (typeof initUsuariosModule === 'function') {
                initUsuariosModule();
            }
        } else if (viewName === 'alertas') {
            if (typeof initAlertasModule === 'function') {
                initAlertasModule();
            }
        } else if (viewName === 'equipos/lista') {
            if (typeof initEquiposModule === 'function') {
                initEquiposModule();
            }
        } else if (viewName === 'equipos/detalles') {
            if (typeof initEquiposDetallesModule === 'function') {
                initEquiposDetallesModule();
            }
        } else if (viewName === 'whatsapp_config') {
            if (typeof initWhatsappConfigModule === 'function') {
                initWhatsappConfigModule();
            }
        } else if (viewName === 'contactos_alerta') {
            if (typeof initContactosAlertaModule === 'function') {
                initContactosAlertaModule();
            }
        } else if (viewName === 'historial_caidas') {
            if (typeof initHistorialCaidasModule === 'function') {
                initHistorialCaidasModule();
            }
        } else if (viewName === 'analiticas') {
            if (typeof initAnaliticasModule === 'function') {
                initAnaliticasModule();
            }
        } else if (viewName === 'noc') {
            if (typeof initNocModule === 'function') {
                initNocModule();
            }
        } else if (viewName === 'topologia') {
            if (typeof initTopologiaModule === 'function') {
                initTopologiaModule();
            }
        } else if (viewName === 'servicios') {
            if (typeof initServiciosModule === 'function') {
                initServiciosModule();
            }
        } else if (viewName === 'traceroute') {
            if (typeof initTracerouteModule === 'function') {
                initTracerouteModule();
            }
        } else {
            // Initialize Default DataTables for other views
            if ($.fn.DataTable.isDataTable('.datatable')) {
                $('.datatable').DataTable().destroy();
            }
            $('.datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                }
            });
        }

        // Initialize SweetAlert2 for delete buttons
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "¡No podrás revertir esto!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire(
                            '¡Eliminado!',
                            'El registro ha sido eliminado exitosamente.',
                            'success'
                        )
                    }
                })
            });
        });
    }

    // Engine de Alertas en Vivo (Audios, Pantalla Roja & Toasts)
    let lastAlertaId = 0;
    let lastCaidaId = 0;
    const alertAudio = new Audio('assets/audio/alert.mp3');
    const recoveryAudio = new Audio('assets/audio/correct.mp3');
    
    // Rastrear audios y alertas silenciadas por caída
    const soundPlayedForCaida = {};
    const notifiedRecuperaciones = {};
    const visualAlertDismissedFor = {};
    window.lastCaidasMasDeUnMinuto = [];

    function initAlertPolling() {
        $.ajax({
            url: 'controllers/NotificacionController.php',
            type: 'GET',
            data: { action: 'getNuevasAlertasWeb', last_alerta_id: 0, last_caida_id: 0 },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'init') {
                    lastAlertaId = res.last_alerta_id;
                    lastCaidaId = res.last_caida_id;
                    setInterval(checkNuevasAlertas, 15000);
                }
            }
        });
    }

    function checkNuevasAlertas() {
        $.ajax({
            url: 'controllers/NotificacionController.php',
            type: 'GET',
            data: { action: 'getNuevasAlertasWeb', last_alerta_id: lastAlertaId, last_caida_id: lastCaidaId },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    let playAlarmSound = false;
                    let playRecoverySound = false;
                    
                    // 1. Filtrar caídas que llevan MÁS DE 1 MINUTO (>= 60 segundos) sin responder
                    let caidasCriticas = [];
                    if (res.caidas && res.caidas.length > 0) {
                        caidasCriticas = res.caidas.filter(c => parseInt(c.segundos_caida || 0) >= 60);
                        
                        // Sonido de alerta (Req 1): SOLO cuando lleva más de 1 minuto sin que el equipo responda
                        caidasCriticas.forEach(c => {
                            if (!soundPlayedForCaida[c.id]) {
                                soundPlayedForCaida[c.id] = true;
                                mostrarToastAlerta(`🚨 CRÍTICO: Nodo ${c.nombre_nodo} lleva +1 min CAÍDO.`, 'error');
                                playAlarmSound = true;
                            }
                        });

                        // Alerta Visual Roja (Req 2): SOLO cuando un nodo supere más de 1 minuto sin responder
                        window.lastCaidasMasDeUnMinuto = caidasCriticas;
                        mostrarAlertaVisual(caidasCriticas);
                    } else {
                        window.lastCaidasMasDeUnMinuto = [];
                        ocultarAlertaVisual();
                    }

                    // 2. Procesar recuperaciones
                    if (res.recuperaciones && res.recuperaciones.length > 0) {
                        res.recuperaciones.forEach(r => {
                            if (!notifiedRecuperaciones[r.id]) {
                                notifiedRecuperaciones[r.id] = true;
                                let dur = r.duracion_minutos > 0 ? ` (Estuvo fuera ${r.duracion_minutos} min)` : '';
                                mostrarToastAlerta(`✅ RESTABLECIDO: Nodo ${r.nombre_nodo} volvió a estar EN LÍNEA${dur}.`, 'success');
                                playRecoverySound = true;
                            }
                        });
                    }

                    // 3. Procesar otras alertas registradas (logs, cpu, etc.)
                    if (res.alertas && res.alertas.length > 0) {
                        res.alertas.forEach(a => {
                            if (a.id > lastAlertaId) {
                                lastAlertaId = a.id;
                                let icon = a.tipo === 'offline' ? 'error' : (a.tipo === 'latencia' || a.tipo === 'cpu' ? 'warning' : 'info');
                                mostrarToastAlerta(`${a.router}: ${a.mensaje}`, icon);
                            }
                        });
                    }
                    
                    // Reproducir audios según corresponda
                    if (playAlarmSound) {
                        alertAudio.play().catch(e => console.log('Autoplay de alarma bloqueado por el navegador.'));
                    }
                    if (playRecoverySound) {
                        recoveryAudio.play().catch(e => console.log('Autoplay de recuperación bloqueado por el navegador.'));
                    }
                }
            }
        });
    }

    function mostrarAlertaVisual(caidasCriticas) {
        const overlay = document.getElementById('critical-alert-overlay');
        if (!overlay) return;

        // Deduplicar caídas estrictamente por el nombre del nodo
        const caidasUnicasMap = {};
        caidasCriticas.forEach(c => {
            const key = (c.nombre_nodo || '').trim().toLowerCase();
            if (!key) return;
            if (!caidasUnicasMap[key] || parseInt(c.segundos_caida || 0) > parseInt(caidasUnicasMap[key].segundos_caida || 0)) {
                caidasUnicasMap[key] = c;
            }
        });
        const caidasUnicas = Object.values(caidasUnicasMap);

        // Mostrar solo caídas de +1 min que no hayan sido silenciadas manualmente por el usuario
        const pendientes = caidasUnicas.filter(c => !visualAlertDismissedFor[c.id]);

        if (pendientes.length > 0) {
            const listEl = document.getElementById('critical-alert-list');
            if (listEl) {
                const nodosText = pendientes.map(c => {
                    let mins = Math.floor(parseInt(c.segundos_caida || 0) / 60);
                    let segs = parseInt(c.segundos_caida || 0) % 60;
                    let tiempoStr = mins > 0 ? `${mins}m ${segs}s` : `${segs}s`;
                    return `
                        <div class="d-flex justify-content-between align-items-center py-2 px-3 border-bottom border-white-50">
                            <span class="fw-bold fs-5 text-white text-truncate me-3">
                                <i class="bi bi-wifi-off me-2 text-warning"></i>${c.nombre_nodo}
                            </span>
                            <span class="badge bg-white text-danger fw-bold fs-6">
                                Sin responder: ${tiempoStr}
                            </span>
                        </div>
                    `;
                }).join('');
                listEl.innerHTML = nodosText;
            }
            overlay.classList.add('active');
        } else {
            ocultarAlertaVisual();
        }
    }

    function ocultarAlertaVisual() {
        const overlay = document.getElementById('critical-alert-overlay');
        if (overlay) {
            overlay.classList.remove('active');
        }
    }

    window.silenciarAlertaVisual = function() {
        if (window.lastCaidasMasDeUnMinuto) {
            window.lastCaidasMasDeUnMinuto.forEach(c => {
                visualAlertDismissedFor[c.id] = true;
            });
        }
        ocultarAlertaVisual();
    };

    window.showCriticalAlert = function(msg) {
        // Función global de respaldo para dashboard.js
        if (window.lastCaidasMasDeUnMinuto && window.lastCaidasMasDeUnMinuto.length > 0) {
            mostrarAlertaVisual(window.lastCaidasMasDeUnMinuto);
        }
    };

    function mostrarToastAlerta(mensaje, icon) {
        let bgColor = '#ffffff';
        if (icon === 'error') bgColor = '#fdecea';
        if (icon === 'success') bgColor = '#e8f5e9';
        if (icon === 'warning') bgColor = '#fff8e1';

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon,
            title: mensaje,
            showConfirmButton: false,
            timer: 8000,
            timerProgressBar: true,
            background: bgColor,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
    }

    initAlertPolling();

    // Event listener nativo de Pantalla Completa (Ocultar Sidebar en Fullscreen)
    const handleFullscreenChange = () => {
        const isFS = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
        if (isFS) {
            document.body.classList.add('is-fullscreen');
        } else {
            document.body.classList.remove('is-fullscreen');
        }
    };

    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('mozfullscreenchange', handleFullscreenChange);
    document.addEventListener('MSFullscreenChange', handleFullscreenChange);

    // Cargar vista inicial
    loadView('dashboard');
});
