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
        if (typeof window.clearPingMultiIntervals === 'function') {
            window.clearPingMultiIntervals();
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
        } else if (viewName === 'plantillas_alerta') {
            if (typeof initPlantillasAlertaModule === 'function') {
                initPlantillasAlertaModule();
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
        } else if (viewName === 'ping_multi') {
            if (typeof initPingMultiModule === 'function') {
                initPingMultiModule();
            }
        } else if (viewName === 'speedtest') {
            if (typeof initSpeedtestModule === 'function') {
                initSpeedtestModule();
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
                                // playAlarmSound = true; // Desactivado por solicitud del usuario
                            }
                        });

                        // Alerta Visual Roja (Req 2) Desactivada por solicitud del usuario
                        window.lastCaidasMasDeUnMinuto = caidasCriticas;
                        // mostrarAlertaVisual(caidasCriticas);
                    } else {
                        window.lastCaidasMasDeUnMinuto = [];
                        // ocultarAlertaVisual();
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
                        try { alertAudio.currentTime = 0; } catch(e) {}
                        alertAudio.play().catch(e => console.log('Autoplay de alarma bloqueado por el navegador.'));
                    }
                    if (playRecoverySound) {
                        try { recoveryAudio.currentTime = 0; } catch(e) {}
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
        // Pausar y reiniciar los audios de alerta al silenciar la pantalla roja
        if (alertAudio) {
            alertAudio.pause();
            alertAudio.currentTime = 0;
        }
        if (recoveryAudio) {
            recoveryAudio.pause();
            recoveryAudio.currentTime = 0;
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

    // === Sistema de Clima Local con Open-Meteo ===
    const WMO_CODES = {
        0: { icon: 'bi-sun-fill', desc: 'despejado', color: 'text-warning' },
        1: { icon: 'bi-cloud-sun-fill', desc: 'despejado parcial', color: 'text-secondary' },
        2: { icon: 'bi-cloud-sun-fill', desc: 'nublado parcial', color: 'text-secondary' },
        3: { icon: 'bi-cloud-fill', desc: 'nublado', color: 'text-secondary' },
        45: { icon: 'bi-cloud-fog-fill', desc: 'niebla', color: 'text-secondary' },
        48: { icon: 'bi-cloud-fog-fill', desc: 'niebla racha', color: 'text-secondary' },
        51: { icon: 'bi-cloud-drizzle-fill', desc: 'llovizna ligera', color: 'text-info' },
        53: { icon: 'bi-cloud-drizzle-fill', desc: 'llovizna moderada', color: 'text-info' },
        55: { icon: 'bi-cloud-drizzle-fill', desc: 'llovizna densa', color: 'text-info' },
        61: { icon: 'bi-cloud-rain-fill', desc: 'lluvia ligera', color: 'text-info' },
        63: { icon: 'bi-cloud-rain-fill', desc: 'lluvia moderada', color: 'text-info' },
        65: { icon: 'bi-cloud-rain-heavy-fill', desc: 'lluvia fuerte', color: 'text-info' },
        71: { icon: 'bi-cloud-snow-fill', desc: 'nieve ligera', color: 'text-primary' },
        73: { icon: 'bi-cloud-snow-fill', desc: 'nieve moderada', color: 'text-primary' },
        75: { icon: 'bi-cloud-snow-fill', desc: 'nieve fuerte', color: 'text-primary' },
        77: { icon: 'bi-cloud-hail', desc: 'granizo', color: 'text-primary' },
        80: { icon: 'bi-cloud-rain-fill', desc: 'chubascos ligeros', color: 'text-info' },
        81: { icon: 'bi-cloud-rain-fill', desc: 'chubascos moderados', color: 'text-info' },
        82: { icon: 'bi-cloud-rain-heavy-fill', desc: 'chubascos violentos', color: 'text-info' },
        95: { icon: 'bi-cloud-lightning-rain-fill', desc: 'tormenta eléctrica', color: 'text-warning' },
        96: { icon: 'bi-cloud-lightning-rain-fill', desc: 'tormenta con granizo', color: 'text-warning' },
        99: { icon: 'bi-cloud-lightning-rain-fill', desc: 'tormenta con granizo fuerte', color: 'text-warning' }
    };

    function getWeatherMeta(code, isDay = 1) {
        let meta = WMO_CODES[code] || { icon: 'bi-cloud-fill', desc: 'nublado', color: 'text-secondary' };
        if (isDay === 0) {
            if (meta.icon === 'bi-sun-fill') {
                return { icon: 'bi-moon-stars-fill', desc: 'despejado', color: 'text-primary' };
            } else if (meta.icon === 'bi-cloud-sun-fill') {
                return { icon: 'bi-cloud-moon-fill', desc: 'nublado parcial', color: 'text-primary' };
            }
        }
        return meta;
    }
    window.getWeatherMeta = getWeatherMeta;

    window.requestWeatherLocation = function (force = false) {
        const loadingEl = document.getElementById('weather-loading');
        const contentEl = document.getElementById('weather-content');
        const errorEl = document.getElementById('weather-error');
        const dropdownLink = document.getElementById('weatherDropdown');

        if (dropdownLink) {
            dropdownLink.style.display = 'flex';
        }

        if (loadingEl) loadingEl.style.display = 'block';
        if (contentEl) contentEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';

        if (!navigator.geolocation) {
            showWeatherError('Geolocalización no soportada.');
            return;
        }

        const options = {
            enableHighAccuracy: false,
            timeout: 10000,
            maximumAge: 600000
        };

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude.toFixed(4);
                const lon = position.coords.longitude.toFixed(4);
                localStorage.setItem('weather_coords', JSON.stringify({ lat, lon }));
                fetchWeatherData(lat, lon);
            },
            (error) => {
                console.warn('Error al obtener ubicación:', error);
                // Si ya tenemos coordenadas guardadas, usarlas como fallback
                const cachedCoords = localStorage.getItem('weather_coords');
                if (cachedCoords) {
                    const { lat, lon } = JSON.parse(cachedCoords);
                    fetchWeatherData(lat, lon);
                } else if (force) {
                    showWeatherError('No se pudo acceder a tu ubicación.');
                } else {
                    // Si no fue forzado y falló, ocultamos el widget silenciosamente
                    if (dropdownLink) dropdownLink.style.display = 'none';
                }
            },
            options
        );
    };

    function showWeatherError(msg) {
        const loadingEl = document.getElementById('weather-loading');
        const contentEl = document.getElementById('weather-content');
        const errorEl = document.getElementById('weather-error');
        if (loadingEl) loadingEl.style.display = 'none';
        if (contentEl) contentEl.style.display = 'none';
        if (errorEl) {
            errorEl.style.display = 'block';
            const span = errorEl.querySelector('span');
            if (span) span.innerText = msg;
        }
    }

    function fetchWeatherData(lat, lon) {
        const nominatimUrl = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=10`;
        const weatherUrl = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,relative_humidity_2m,is_day,weather_code,wind_speed_10m&hourly=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&forecast_days=1`;

        Promise.all([
            fetch(weatherUrl).then(r => { if (!r.ok) throw new Error('API clima error'); return r.json(); }),
            fetch(nominatimUrl, { headers: { 'Accept-Language': 'es' } }).then(r => r.json()).catch(() => null)
        ])
        .then(([weatherData, geoData]) => {
            let locationName = `Lat: ${lat}, Lon: ${lon}`;
            if (geoData && geoData.address) {
                locationName = geoData.address.city || geoData.address.town || geoData.address.village || geoData.address.municipality || geoData.address.county || locationName;
            }

            const cacheData = {
                timestamp: Date.now(),
                weather: weatherData,
                location: locationName
            };
            localStorage.setItem('weather_cache', JSON.stringify(cacheData));
            renderWeather(weatherData, locationName);
        })
        .catch(err => {
            console.error('Error al consultar clima:', err);
            showWeatherError('Error de red al consultar clima.');
        });
    }

    function renderWeather(weatherData, locationName) {
        const current = weatherData.current;
        const meta = getWeatherMeta(current.weather_code, current.is_day);

        // Actualizar navbar widget
        const iconNav = document.getElementById('weather-icon-nav');
        const tempNav = document.getElementById('weather-temp-nav');
        const dropdownLink = document.getElementById('weatherDropdown');

        if (dropdownLink) dropdownLink.style.display = 'flex';
        
        if (iconNav) {
            iconNav.className = `bi ${meta.icon} ${meta.color} me-2`;
        }
        if (tempNav) {
            tempNav.innerText = `${Math.round(current.temperature_2m)}°C`;
        }

        // Actualizar dropdown detallado
        const loadingEl = document.getElementById('weather-loading');
        const contentEl = document.getElementById('weather-content');
        const errorEl = document.getElementById('weather-error');

        if (loadingEl) loadingEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';
        if (contentEl) contentEl.style.display = 'block';

        const locEl = document.getElementById('weather-location');
        const largeIconEl = document.getElementById('weather-large-icon');
        const tempDetailEl = document.getElementById('weather-temp-detail');
        const descEl = document.getElementById('weather-desc');
        const windEl = document.getElementById('weather-wind');
        const humEl = document.getElementById('weather-humidity');

        if (locEl) locEl.innerText = locationName;
        if (largeIconEl) {
            largeIconEl.className = `bi ${meta.icon} ${meta.color} fs-1 me-3`;
        }
        if (tempDetailEl) tempDetailEl.innerText = `${Math.round(current.temperature_2m)}°C`;
        if (descEl) descEl.innerText = meta.desc;
        if (windEl) windEl.innerText = `${current.wind_speed_10m.toFixed(1)} km/h`;
        if (humEl) humEl.innerText = `${current.relative_humidity_2m}%`;

        // Renderizar pronóstico por horas (siguientes 4 horas)
        const hourlyEl = document.getElementById('weather-hourly-forecast');
        if (hourlyEl) {
            hourlyEl.innerHTML = '';
            const hourly = weatherData.hourly;
            
            const nowIso = new Date().toISOString().substring(0, 13) + ':00';
            let startIndex = hourly.time.findIndex(t => t.startsWith(nowIso.substring(0, 13)));
            if (startIndex === -1) startIndex = 0;
            
            for (let i = 1; i <= 4; i++) {
                const index = (startIndex + i) % hourly.time.length;
                const timeStr = hourly.time[index];
                const timeObj = new Date(timeStr);
                const hourFormatted = timeObj.toLocaleTimeString('es-MX', { hour: '2-digit', hour12: false }) + 'h';
                const temp = Math.round(hourly.temperature_2m[index]);
                const code = hourly.weather_code[index];
                const hourIsDay = timeObj.getHours() >= 6 && timeObj.getHours() < 19 ? 1 : 0;
                const hourMeta = getWeatherMeta(code, hourIsDay);

                const itemHtml = `
                    <div class="weather-hourly-item text-center">
                        <span class="weather-hourly-time">${hourFormatted}</span>
                        <i class="bi ${hourMeta.icon} ${hourMeta.color} weather-hourly-icon"></i>
                        <span class="weather-hourly-temp">${temp}°C</span>
                    </div>
                `;
                hourlyEl.insertAdjacentHTML('beforeend', itemHtml);
            }
        }

        // Si existe la función del dashboard para actualizar su tarjeta, llamarla
        if (typeof window.updateDashboardWeatherCard === 'function') {
            window.updateDashboardWeatherCard(weatherData, locationName);
        }
    }

    window.initWeatherSystem = function() {
        const cached = localStorage.getItem('weather_cache');
        if (cached) {
            try {
                const data = JSON.parse(cached);
                if (Date.now() - data.timestamp < 900000) {
                    renderWeather(data.weather, data.location);
                    return;
                }
            } catch (e) {
                console.error('Error al leer caché del clima:', e);
            }
        }

        const cachedCoords = localStorage.getItem('weather_coords');
        if (cachedCoords) {
            try {
                const { lat, lon } = JSON.parse(cachedCoords);
                fetchWeatherData(lat, lon);
                return;
            } catch (e) {}
        }

        window.requestWeatherLocation(false);
    };

    window.initWeatherSystem();

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
