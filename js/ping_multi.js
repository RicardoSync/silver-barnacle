(function () {
    let multiState = {
        gridCount: 4,
        intervalMs: 2000,
        timer: null,
        isPaused: false,
        devicesList: [],
        charts: {}, // cardIndex -> Chart instance
        dataHistory: {}, // cardIndex -> array of { time, ms, online }
        selectedTargets: {}, // cardIndex -> IP
        customIps: {} // cardIndex -> custom IP string
    };

    window.initPingMultiModule = function () {
        clearPingMultiIntervals();
        cargarListaDispositivos();

        // Event listener para cambio de cantidad de gráficas
        const selGrid = document.getElementById('select-grid-count');
        if (selGrid) {
            selGrid.value = multiState.gridCount;
            selGrid.onchange = function () {
                multiState.gridCount = parseInt(this.value);
                renderizarCuadricula();
                guardarConfiguracionMulti(true);
            };
        }

        // Event listener para cambio de intervalo
        const selInt = document.getElementById('select-interval-multi');
        if (selInt) {
            selInt.value = multiState.intervalMs;
            selInt.onchange = function () {
                multiState.intervalMs = parseInt(this.value);
                reiniciarIntervalo();
                guardarConfiguracionMulti(true);
            };
        }
    };

    window.clearPingMultiIntervals = function () {
        if (multiState.timer) {
            clearInterval(multiState.timer);
            multiState.timer = null;
        }
        // Destruir charts existentes
        Object.keys(multiState.charts).forEach(key => {
            if (multiState.charts[key]) {
                multiState.charts[key].destroy();
            }
        });
        multiState.charts = {};
    };

    function cargarListaDispositivos() {
        fetch('api.php?action=obtener_dispositivos_ping')
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && Array.isArray(res.data)) {
                    multiState.devicesList = res.data;
                } else {
                    multiState.devicesList = [
                        { id: 'preset_google', nombre: 'Google DNS (8.8.8.8)', ip: '8.8.8.8', categoria: 'Predefinidos' },
                        { id: 'preset_cloudflare', nombre: 'Cloudflare DNS (1.1.1.1)', ip: '1.1.1.1', categoria: 'Predefinidos' }
                    ];
                }
                cargarConfiguracionGuardada();
                renderizarCuadricula();
                iniciarMonitoreo();
            })
            .catch(err => {
                console.error('Error al cargar lista de dispositivos ping:', err);
                multiState.devicesList = [
                    { id: 'preset_google', nombre: 'Google DNS (8.8.8.8)', ip: '8.8.8.8', categoria: 'Predefinidos' },
                    { id: 'preset_cloudflare', nombre: 'Cloudflare DNS (1.1.1.1)', ip: '1.1.1.1', categoria: 'Predefinidos' }
                ];
                renderizarCuadricula();
                iniciarMonitoreo();
            });
    }

    function cargarConfiguracionGuardada() {
        try {
            const raw = localStorage.getItem('elissa_ping_multi_config');
            if (raw) {
                const conf = JSON.parse(raw);
                if (conf.gridCount) multiState.gridCount = conf.gridCount;
                if (conf.intervalMs) multiState.intervalMs = conf.intervalMs;
                if (conf.selectedTargets) multiState.selectedTargets = conf.selectedTargets;
                if (conf.customIps) multiState.customIps = conf.customIps;
            }
        } catch (e) {
            console.warn('No se pudo leer la configuración previa de multi ping:', e);
        }

        // Asignar defaults si no hay nada guardado
        const defaultIps = ['8.8.8.8', '1.1.1.1', '192.168.1.1', '192.168.88.1', '8.8.4.4', '1.0.0.1', '192.168.0.1', '10.0.0.1'];
        for (let i = 0; i < 8; i++) {
            if (!multiState.selectedTargets[i]) {
                // Asignar según dispositivos disponibles o defaults
                if (multiState.devicesList[i]) {
                    multiState.selectedTargets[i] = multiState.devicesList[i].ip;
                } else {
                    multiState.selectedTargets[i] = defaultIps[i] || '8.8.8.8';
                }
            }
        }
    }

    window.guardarConfiguracionMulti = function (silencioso = false) {
        try {
            const conf = {
                gridCount: multiState.gridCount,
                intervalMs: multiState.intervalMs,
                selectedTargets: multiState.selectedTargets,
                customIps: multiState.customIps
            };
            localStorage.setItem('elissa_ping_multi_config', JSON.stringify(conf));

            if (!silencioso && typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Configuración guardada',
                    text: 'Se han guardado las IPs y disposición seleccionada.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        } catch (e) {
            console.error('Error al guardar configuración multi ping:', e);
        }
    };

    function renderizarCuadricula() {
        const grid = document.getElementById('grid-ping-multi');
        if (!grid) return;

        // Limpiar charts anteriores
        Object.keys(multiState.charts).forEach(key => {
            if (multiState.charts[key]) {
                multiState.charts[key].destroy();
            }
        });
        multiState.charts = {};
        grid.innerHTML = '';

        // Determinar clase de columnas según cantidad de gráficas
        let colClass = 'col-12 col-md-6'; // 2 o 4 vistas
        if (multiState.gridCount === 6) {
            colClass = 'col-12 col-md-6 col-lg-4';
        } else if (multiState.gridCount === 8) {
            colClass = 'col-12 col-md-6 col-lg-3';
        }

        for (let i = 0; i < multiState.gridCount; i++) {
            if (!multiState.dataHistory[i]) {
                multiState.dataHistory[i] = [];
            }

            const currentIp = multiState.selectedTargets[i] || '8.8.8.8';

            const cardHtml = `
                <div class="${colClass}">
                    <div class="card shadow-sm border-0 h-100 rounded-3 overflow-hidden">
                        <div class="card-header bg-white py-2 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center flex-grow-1 me-2" style="min-width: 0;">
                                <span class="badge bg-primary bg-opacity-10 text-primary me-2 fw-bold" style="font-size: 11px;">#${i + 1}</span>
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <select class="form-select form-select-sm fw-semibold select-target" data-index="${i}" style="font-size: 12px; height: 31px;">
                                        ${generarOpcionesSelect(currentIp)}
                                    </select>
                                </div>
                            </div>
                            <div>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fs-6 font-monospace" id="badge-ping-val-${i}">-- ms</span>
                            </div>
                        </div>

                        <div class="px-3 pt-2 pb-0 bg-light border-bottom" id="container-custom-ip-${i}" style="display: ${currentIp === 'custom' ? 'block' : 'none'};">
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text bg-white"><i class="bi bi-globe"></i></span>
                                <input type="text" class="form-control input-custom-ip" data-index="${i}" placeholder="Escribe IP o Host..." value="${multiState.customIps[i] || ''}">
                                <button class="btn btn-outline-primary" onclick="aplicarCustomIp(${i})">Aplicar</button>
                            </div>
                        </div>

                        <div class="card-body p-3">
                            <!-- Métricas Rápidas -->
                            <div class="row text-center g-1 mb-2 py-1 px-2 rounded bg-light border">
                                <div class="col-3">
                                    <small class="text-muted d-block text-uppercase" style="font-size: 9px; font-weight: 700;">Actual</small>
                                    <span class="fw-bold fs-7 text-dark" id="stat-current-${i}">-- ms</span>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted d-block text-uppercase" style="font-size: 9px; font-weight: 700;">Mín</small>
                                    <span class="fw-bold fs-7 text-success" id="stat-min-${i}">--</span>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted d-block text-uppercase" style="font-size: 9px; font-weight: 700;">Prom</small>
                                    <span class="fw-bold fs-7 text-primary" id="stat-avg-${i}">--</span>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted d-block text-uppercase" style="font-size: 9px; font-weight: 700;">Máx</small>
                                    <span class="fw-bold fs-7 text-danger" id="stat-max-${i}">--</span>
                                </div>
                            </div>

                            <!-- Canvas Gráfica -->
                            <div style="position: relative; height: 140px; width: 100%;">
                                <canvas id="chart-ping-multi-${i}"></canvas>
                            </div>
                        </div>

                        <div class="card-footer bg-white py-1 px-3 border-top text-muted d-flex justify-content-between align-items-center" style="font-size: 10px;">
                            <span id="lbl-ip-info-${i}" class="text-truncate me-2 fw-semibold"><i class="bi bi-wifi me-1"></i> IP: ${obtenerIpEfectiva(i)}</span>
                            <span id="lbl-last-check-${i}" class="text-nowrap"><i class="bi bi-clock me-1"></i> Esperando...</span>
                        </div>
                    </div>
                </div>
            `;
            grid.insertAdjacentHTML('beforeend', cardHtml);

            // Crear Chart Instance
            crearChartInstancia(i);
        }

        // Listener para selects
        document.querySelectorAll('.select-target').forEach(sel => {
            sel.addEventListener('change', function () {
                const idx = parseInt(this.getAttribute('data-index'));
                const val = this.value;
                const containerCustom = document.getElementById(`container-custom-ip-${idx}`);

                if (val === 'custom') {
                    if (containerCustom) containerCustom.style.display = 'block';
                    multiState.selectedTargets[idx] = 'custom';
                } else {
                    if (containerCustom) containerCustom.style.display = 'none';
                    multiState.selectedTargets[idx] = val;
                }

                // Reset de historial para esta gráfica al cambiar objetivo
                multiState.dataHistory[idx] = [];
                actualizarInformacionGrafica(idx, null);
                guardarConfiguracionMulti(true);
                ejecutarBatchPingAhora();
            });
        });
    }

    window.aplicarCustomIp = function (idx) {
        const input = document.querySelector(`.input-custom-ip[data-index="${idx}"]`);
        if (input && input.value.trim()) {
            const val = input.value.trim();
            multiState.customIps[idx] = val;
            multiState.selectedTargets[idx] = 'custom';
            multiState.dataHistory[idx] = [];
            actualizarInformacionGrafica(idx, null);
            guardarConfiguracionMulti(true);
            ejecutarBatchPingAhora();
        }
    };

    function generarOpcionesSelect(selectedVal) {
        // Agrupar por categoría
        const categorias = {};
        multiState.devicesList.forEach(dev => {
            const cat = dev.categoria || 'Otros';
            if (!categorias[cat]) categorias[cat] = [];
            categorias[cat].push(dev);
        });

        let html = '';
        Object.keys(categorias).forEach(cat => {
            html += `<optgroup label="${cat}">`;
            categorias[cat].forEach(dev => {
                const isSel = (selectedVal === dev.ip || selectedVal === dev.id) ? 'selected' : '';
                html += `<option value="${dev.ip}" ${isSel}>${dev.nombre}</option>`;
            });
            html += `</optgroup>`;
        });

        const isCustomSel = selectedVal === 'custom' ? 'selected' : '';
        html += `<optgroup label="Personalizado"><option value="custom" ${isCustomSel}>⚙️ Dirección IP Personalizada...</option></optgroup>`;
        return html;
    }

    function obtenerIpEfectiva(idx) {
        const target = multiState.selectedTargets[idx];
        if (target === 'custom') {
            return multiState.customIps[idx] || 'Sin IP';
        }
        return target || '8.8.8.8';
    }

    function crearChartInstancia(idx) {
        const canvas = document.getElementById(`chart-ping-multi-${idx}`);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 140);
        gradient.addColorStop(0, 'rgba(13, 110, 253, 0.25)');
        gradient.addColorStop(1, 'rgba(13, 110, 253, 0.0)');

        multiState.charts[idx] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Ping (ms)',
                    data: [],
                    borderColor: '#0d6efd',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#0d6efd',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function (ctx) {
                                return ctx.parsed.y !== null ? ` Ping: ${ctx.parsed.y} ms` : ' Offline';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9 }, maxTicksLimit: 6 }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 50,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: {
                            font: { size: 9 },
                            callback: function (val) { return val + 'ms'; }
                        }
                    }
                }
            }
        });
    }

    function iniciarMonitoreo() {
        ejecutarBatchPingAhora();
        reiniciarIntervalo();
    }

    function reiniciarIntervalo() {
        if (multiState.timer) {
            clearInterval(multiState.timer);
            multiState.timer = null;
        }

        if (!multiState.isPaused) {
            multiState.timer = setInterval(() => {
                ejecutarBatchPingAhora();
            }, multiState.intervalMs);
        }
    }

    window.toggleMonitoreoMulti = function () {
        multiState.isPaused = !multiState.isPaused;

        const icon = document.getElementById('icon-toggle-multi');
        const text = document.getElementById('text-toggle-multi');
        const btn = document.getElementById('btn-toggle-multi');
        const lbl = document.getElementById('lbl-status-batch');

        if (multiState.isPaused) {
            if (multiState.timer) clearInterval(multiState.timer);
            multiState.timer = null;

            if (icon) icon.className = 'bi bi-play-fill me-1';
            if (text) text.textContent = 'Reanudar';
            if (btn) { btn.className = 'btn btn-sm btn-warning shadow-sm'; }
            if (lbl) {
                lbl.className = 'badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25';
                lbl.innerHTML = '<i class="bi bi-pause-circle me-1"></i> Monitoreo Pausado';
            }
        } else {
            if (icon) icon.className = 'bi bi-pause-fill me-1';
            if (text) text.textContent = 'Pausar';
            if (btn) { btn.className = 'btn btn-sm btn-success shadow-sm'; }
            if (lbl) {
                lbl.className = 'badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25';
                lbl.innerHTML = '<i class="bi bi-activity text-success me-1"></i> Monitoreando activo';
            }
            iniciarMonitoreo();
        }
    };

    window.ejecutarBatchPingAhora = function () {
        const ipsToQuery = [];
        for (let i = 0; i < multiState.gridCount; i++) {
            const ip = obtenerIpEfectiva(i);
            if (ip && !ipsToQuery.includes(ip)) {
                ipsToQuery.push(ip);
            }
        }

        if (ipsToQuery.length === 0) return;

        const formData = new FormData();
        formData.append('ips', JSON.stringify(ipsToQuery));

        fetch('api.php?action=ping_batch', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    const horaStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

                    for (let i = 0; i < multiState.gridCount; i++) {
                        const ip = obtenerIpEfectiva(i);
                        const pingData = res.data[ip] || { ms: -1, online: false, status: 'offline' };

                        procesarNuevoPing(i, ip, pingData, horaStr);
                    }
                }
            })
            .catch(err => {
                console.error('Error al ejecutar batch ping:', err);
            });
    };

    function procesarNuevoPing(idx, ip, pingData, horaStr) {
        if (!multiState.dataHistory[idx]) multiState.dataHistory[idx] = [];
        const history = multiState.dataHistory[idx];

        history.push({
            time: horaStr,
            ms: pingData.online ? pingData.ms : null,
            online: pingData.online
        });

        // Mantener máx 30 puntos en la historia
        if (history.length > 30) {
            history.shift();
        }

        actualizarInformacionGrafica(idx, pingData);
    }

    function actualizarInformacionGrafica(idx, latestPingData) {
        const history = multiState.dataHistory[idx] || [];
        const badgePing = document.getElementById(`badge-ping-val-${idx}`);
        const statCurrent = document.getElementById(`stat-current-${idx}`);
        const statMin = document.getElementById(`stat-min-${idx}`);
        const statMax = document.getElementById(`stat-max-${idx}`);
        const statAvg = document.getElementById(`stat-avg-${idx}`);
        const lblIp = document.getElementById(`lbl-ip-info-${idx}`);
        const lblCheck = document.getElementById(`lbl-last-check-${idx}`);

        const currentIp = obtenerIpEfectiva(idx);
        if (lblIp) lblIp.innerHTML = `<i class="bi bi-wifi me-1"></i> IP: ${currentIp}`;

        if (latestPingData) {
            const nowTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            if (lblCheck) lblCheck.innerHTML = `<i class="bi bi-clock me-1"></i> ${nowTime}`;

            if (latestPingData.online) {
                const msVal = latestPingData.ms;
                if (badgePing) {
                    let colorBg = 'bg-success';
                    if (msVal > 100 && msVal <= 200) colorBg = 'bg-warning text-dark';
                    else if (msVal > 200) colorBg = 'bg-danger';

                    badgePing.className = `badge ${colorBg} font-monospace fs-6 shadow-sm`;
                    badgePing.textContent = `${msVal} ms`;
                }
                if (statCurrent) statCurrent.textContent = `${msVal} ms`;
            } else {
                if (badgePing) {
                    badgePing.className = 'badge bg-danger font-monospace fs-6 shadow-sm';
                    badgePing.textContent = 'OFFLINE';
                }
                if (statCurrent) statCurrent.textContent = 'DOWN';
            }
        }

        // Calular estadísticas de la historia
        const validValues = history.map(h => h.ms).filter(v => v !== null && v !== undefined);
        if (validValues.length > 0) {
            const min = Math.min(...validValues);
            const max = Math.max(...validValues);
            const avg = Math.round(validValues.reduce((a, b) => a + b, 0) / validValues.length);

            if (statMin) statMin.textContent = `${min} ms`;
            if (statMax) statMax.textContent = `${max} ms`;
            if (statAvg) statAvg.textContent = `${avg} ms`;
        } else {
            if (statMin) statMin.textContent = '--';
            if (statMax) statMax.textContent = '--';
            if (statAvg) statAvg.textContent = '--';
        }

        // Actualizar datos en Chart.js
        const chart = multiState.charts[idx];
        if (chart) {
            chart.data.labels = history.map(h => h.time);
            chart.data.datasets[0].data = history.map(h => h.ms);

            // Ajustar color de la línea según el último valor
            const lastVal = validValues.length > 0 ? validValues[validValues.length - 1] : null;
            if (lastVal === null) {
                chart.data.datasets[0].borderColor = '#dc3545';
                chart.data.datasets[0].pointBackgroundColor = '#dc3545';
            } else if (lastVal > 200) {
                chart.data.datasets[0].borderColor = '#dc3545';
                chart.data.datasets[0].pointBackgroundColor = '#dc3545';
            } else if (lastVal > 100) {
                chart.data.datasets[0].borderColor = '#ffc107';
                chart.data.datasets[0].pointBackgroundColor = '#ffc107';
            } else {
                chart.data.datasets[0].borderColor = '#0d6efd';
                chart.data.datasets[0].pointBackgroundColor = '#0d6efd';
            }

            chart.update();
        }
    }
})();
