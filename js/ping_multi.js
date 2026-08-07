(function () {
    let multiState = {
        colClass: 'col-12 col-md-6', // Default 2 por fila
        colValue: '6',
        intervalMs: 2000,
        timer: null,
        isPaused: false,
        devicesList: [],
        cards: [], // Array de objetos card: { id, targetId, targetIp, targetType, realId, mode, interface, customIp, title, history: [], chart: null, interfacesList: [] }
    };

    window.initPingMultiModule = function () {
        clearPingMultiIntervals();
        cargarListaDispositivos();

        // Control de disposición (columnas por fila)
        const selCols = document.getElementById('select-columns-count');
        if (selCols) {
            selCols.value = multiState.colValue;
            selCols.onchange = function () {
                multiState.colValue = this.value;
                actualizarClaseColumnas(this.value);
                guardarConfiguracionMulti(true);
            };
        }

        // Control de intervalo de actualización
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
        // Destruir todas las instancias de Chart.js
        multiState.cards.forEach(card => {
            if (card.chart) {
                try { card.chart.destroy(); } catch (e) { }
                card.chart = null;
            }
        });
    };

    function actualizarClaseColumnas(val) {
        if (val === '6') multiState.colClass = 'col-12 col-md-6';
        else if (val === '4') multiState.colClass = 'col-12 col-md-6 col-lg-4';
        else if (val === '3') multiState.colClass = 'col-12 col-md-6 col-lg-3';
        else if (val === '12') multiState.colClass = 'col-12';
        else multiState.colClass = 'col-12 col-md-6 col-lg-4';

        // Actualizar elementos DOM existentes
        document.querySelectorAll('#grid-ping-multi > [id^="card-wrapper-"]').forEach(el => {
            el.className = multiState.colClass;
        });
    }

    function cargarListaDispositivos() {
        fetch('api.php?action=obtener_dispositivos_ping')
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && Array.isArray(res.data)) {
                    multiState.devicesList = res.data;
                } else {
                    multiState.devicesList = [
                        { id: 'preset_google', real_id: null, nombre: 'Google DNS (8.8.8.8)', ip: '8.8.8.8', tipo: 'preset', categoria: 'Predefinidos' },
                        { id: 'preset_cloudflare', real_id: null, nombre: 'Cloudflare DNS (1.1.1.1)', ip: '1.1.1.1', tipo: 'preset', categoria: 'Predefinidos' }
                    ];
                }
                cargarConfiguracionGuardada();
                renderizarCuadriculaCompleta();
                iniciarMonitoreo();
            })
            .catch(err => {
                console.error('Error al cargar dispositivos para NOC Multigráfica:', err);
                multiState.devicesList = [
                    { id: 'preset_google', real_id: null, nombre: 'Google DNS (8.8.8.8)', ip: '8.8.8.8', tipo: 'preset', categoria: 'Predefinidos' },
                    { id: 'preset_cloudflare', real_id: null, nombre: 'Cloudflare DNS (1.1.1.1)', ip: '1.1.1.1', tipo: 'preset', categoria: 'Predefinidos' }
                ];
                cargarConfiguracionGuardada();
                renderizarCuadriculaCompleta();
                iniciarMonitoreo();
            });
    }

    function cargarConfiguracionGuardada() {
        multiState.cards = [];
        try {
            const raw = localStorage.getItem('elissa_ping_multi_config_v2');
            if (raw) {
                const conf = JSON.parse(raw);
                if (conf.colValue) {
                    multiState.colValue = conf.colValue;
                    actualizarClaseColumnas(conf.colValue);
                    const selCols = document.getElementById('select-columns-count');
                    if (selCols) selCols.value = conf.colValue;
                }
                if (conf.intervalMs) {
                    multiState.intervalMs = conf.intervalMs;
                    const selInt = document.getElementById('select-interval-multi');
                    if (selInt) selInt.value = conf.intervalMs;
                }
                if (Array.isArray(conf.cards) && conf.cards.length > 0) {
                    conf.cards.forEach(c => {
                        multiState.cards.push(crearObjetoCard(c));
                    });
                    return;
                }
            }
        } catch (e) {
            console.warn('No se pudo leer la configuración guardada del NOC:', e);
        }

        // Si no hay configuración previa, crear tarjetas por defecto (4 tarjetas)
        const defaults = [
            { targetId: 'preset_google' },
            { targetId: 'preset_cloudflare' }
        ];

        // Agregar MikroTiks o Equipos adicionales si existen en la lista
        const mkDev = multiState.devicesList.find(d => d.tipo === 'mikrotik');
        if (mkDev) defaults.push({ targetId: mkDev.id, mode: 'traffic', interface: 'ether1' });

        const eqDev = multiState.devicesList.find(d => d.tipo === 'equipo');
        if (eqDev) defaults.push({ targetId: eqDev.id });

        defaults.forEach(def => {
            multiState.cards.push(crearObjetoCard(def));
        });
    }

    function crearObjetoCard(data = {}) {
        const id = data.id || ('card_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5));
        const targetId = data.targetId || 'preset_google';

        let foundDev = multiState.devicesList.find(d => d.id === targetId);
        if (!foundDev && targetId !== 'custom') {
            foundDev = multiState.devicesList[0] || { id: 'preset_google', ip: '8.8.8.8', tipo: 'preset', real_id: null, nombre: 'Google DNS' };
        }

        const targetType = targetId === 'custom' ? 'custom' : (foundDev ? foundDev.tipo : 'preset');
        const realId = foundDev ? foundDev.real_id : null;
        const targetIp = targetId === 'custom' ? (data.customIp || '192.168.1.1') : (foundDev ? foundDev.ip : '8.8.8.8');
        const customIp = data.customIp || (targetId === 'custom' ? '192.168.1.1' : '');

        const mode = data.mode || 'ping'; // 'ping' o 'traffic'
        const iface = data.interface || 'ether1';

        return {
            id: id,
            targetId: targetId,
            targetType: targetType,
            realId: realId,
            targetIp: targetIp,
            customIp: customIp,
            mode: mode,
            interface: iface,
            title: foundDev ? foundDev.nombre : (targetId === 'custom' ? 'Personalizado' : 'Dispositivo'),
            history: [],
            chart: null,
            interfacesList: data.interfacesList || []
        };
    }

    window.guardarConfiguracionMulti = function (silencioso = false) {
        try {
            const serializableCards = multiState.cards.map(c => ({
                id: c.id,
                targetId: c.targetId,
                targetType: c.targetType,
                realId: c.realId,
                targetIp: c.targetIp,
                customIp: c.customIp,
                mode: c.mode,
                interface: c.interface
            }));

            const conf = {
                colValue: multiState.colValue,
                intervalMs: multiState.intervalMs,
                cards: serializableCards
            };
            localStorage.setItem('elissa_ping_multi_config_v2', JSON.stringify(conf));

            if (!silencioso && typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Configuración Guardada',
                    text: 'Disposición y monitores guardados correctamente.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        } catch (e) {
            console.error('Error al guardar configuración NOC:', e);
        }
    };

    window.agregarNuevaGrafica = function (configInicial = null) {
        const nuevaCard = crearObjetoCard(configInicial || {});
        multiState.cards.push(nuevaCard);

        const grid = document.getElementById('grid-ping-multi');
        if (grid) {
            const cardHtml = generarHtmlCard(nuevaCard, multiState.cards.length);
            grid.insertAdjacentHTML('beforeend', cardHtml);
            inicializarEventosYChartCard(nuevaCard);

            if (nuevaCard.targetType === 'mikrotik') {
                cargarInterfacesMikrotikCard(nuevaCard);
            }
        }

        guardarConfiguracionMulti(true);
        ejecutarBatchMonitoreo();
    };

    window.eliminarGrafica = function (cardId) {
        const index = multiState.cards.findIndex(c => c.id === cardId);
        if (index > -1) {
            const card = multiState.cards[index];
            if (card.chart) {
                try { card.chart.destroy(); } catch (e) { }
                card.chart = null;
            }

            multiState.cards.splice(index, 1);
            const el = document.getElementById(`card-wrapper-${cardId}`);
            if (el) el.remove();

            // Re-numerar las tarjetas visibles
            multiState.cards.forEach((c, i) => {
                const badgeNum = document.getElementById(`card-number-${c.id}`);
                if (badgeNum) badgeNum.textContent = `#${i + 1}`;
            });

            guardarConfiguracionMulti(true);
        }
    };

    window.restablecerGraficasMulti = function () {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Restablecer gráficas?',
                text: 'Se limpiará el panel y se cargarán las gráficas predeterminadas.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, restablecer',
                cancelButtonText: 'Cancelar'
            }).then((res) => {
                if (res.isConfirmed) {
                    localStorage.removeItem('elissa_ping_multi_config_v2');
                    clearPingMultiIntervals();
                    cargarListaDispositivos();
                }
            });
        } else {
            localStorage.removeItem('elissa_ping_multi_config_v2');
            clearPingMultiIntervals();
            cargarListaDispositivos();
        }
    };

    function renderizarCuadriculaCompleta() {
        const grid = document.getElementById('grid-ping-multi');
        if (!grid) return;

        // Limpiar charts previos
        multiState.cards.forEach(card => {
            if (card.chart) {
                try { card.chart.destroy(); } catch (e) { }
                card.chart = null;
            }
        });

        grid.innerHTML = '';

        if (multiState.cards.length === 0) {
            grid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="card shadow-sm border-0 p-5">
                        <i class="bi bi-bar-chart-line text-muted display-4 mb-3"></i>
                        <h4 class="fw-bold text-dark">No hay gráficas agregadas</h4>
                        <p class="text-muted">Haz clic en el botón "+ Agregar Gráfica" para añadir monitores de Ping o Tráfico MikroTik en vivo.</p>
                        <div>
                            <button class="btn btn-primary px-4" onclick="agregarNuevaGrafica()"><i class="bi bi-plus-circle me-1"></i> Agregar mi primera gráfica</button>
                        </div>
                    </div>
                </div>
            `;
            return;
        }

        multiState.cards.forEach((card, index) => {
            const cardHtml = generarHtmlCard(card, index + 1);
            grid.insertAdjacentHTML('beforeend', cardHtml);
            inicializarEventosYChartCard(card);

            if (card.targetType === 'mikrotik') {
                cargarInterfacesMikrotikCard(card);
            }
        });
    }

    function generarHtmlCard(card, numero) {
        const isMikrotik = card.targetType === 'mikrotik';
        const isTrafficMode = card.mode === 'traffic' && isMikrotik;
        const isCustom = card.targetId === 'custom';

        return `
            <div class="${multiState.colClass}" id="card-wrapper-${card.id}">
                <div class="card shadow-sm border-0 h-100 rounded-3 overflow-hidden">
                    <!-- Cabecera de la Tarjeta -->
                    <div class="card-header bg-white py-2 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center flex-grow-1 me-1" style="min-width: 0;">
                            <span class="badge bg-primary bg-opacity-10 text-primary me-2 fw-bold" id="card-number-${card.id}" style="font-size: 11px;">#${numero}</span>
                            
                            <div class="flex-grow-1" style="min-width: 0;">
                                <select class="form-select form-select-sm fw-semibold select-target" data-card-id="${card.id}" style="font-size: 12px; height: 31px;">
                                    ${generarOpcionesSelectTarget(card.targetId)}
                                </select>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fs-6 font-monospace" id="badge-val-${card.id}">--</span>
                            <button class="btn btn-sm btn-outline-danger border-0 p-1" onclick="eliminarGrafica('${card.id}')" title="Eliminar esta gráfica">
                                <i class="bi bi-x-lg fs-6"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Panel Opciones MikroTik (Modo & Interfaz) -->
                    <div class="px-3 py-2 bg-body-tertiary border-bottom d-flex flex-wrap align-items-center gap-2" id="container-mikrotik-options-${card.id}" style="display: ${isMikrotik ? 'flex' : 'none'};">
                        <div class="d-flex align-items-center gap-1 flex-grow-1">
                            <span class="small fw-bold text-muted" style="font-size: 10px;">MODO:</span>
                            <select class="form-select form-select-sm select-mode" data-card-id="${card.id}" style="font-size: 11px; height: 28px;">
                                <option value="ping" ${card.mode === 'ping' ? 'selected' : ''}>Latencia Ping (ms)</option>
                                <option value="traffic" ${card.mode === 'traffic' ? 'selected' : ''}>Tráfico Interfaz (Mbps)</option>
                            </select>
                        </div>

                        <div class="d-flex align-items-center gap-1 flex-grow-1" id="container-iface-select-${card.id}" style="display: ${isTrafficMode ? 'flex' : 'none'};">
                            <span class="small fw-bold text-muted" style="font-size: 10px;">INTERFAZ:</span>
                            <select class="form-select form-select-sm select-interface" data-card-id="${card.id}" style="font-size: 11px; height: 28px;">
                                ${generarOpcionesInterfaces(card.interfacesList, card.interface)}
                            </select>
                        </div>
                    </div>

                    <!-- Campo IP Personalizada -->
                    <div class="px-3 pt-2 pb-1 bg-light border-bottom" id="container-custom-ip-${card.id}" style="display: ${isCustom ? 'block' : 'none'};">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-globe"></i></span>
                            <input type="text" class="form-control input-custom-ip" data-card-id="${card.id}" placeholder="Escribe IP o Host..." value="${card.customIp || ''}">
                            <button class="btn btn-outline-primary" onclick="aplicarCustomIpCard('${card.id}')">Aplicar</button>
                        </div>
                    </div>

                    <!-- Cuerpo con Métricas Rápidas y Canvas -->
                    <div class="card-body p-3">
                        <div class="row text-center g-1 mb-2 py-1 px-2 rounded bg-light border" id="stats-container-${card.id}">
                            ${generarHtmlStatsCard(card)}
                        </div>

                        <div style="position: relative; height: 240px; width: 100%;">
                            <canvas id="canvas-${card.id}"></canvas>
                        </div>
                    </div>

                    <!-- Pie de Tarjeta -->
                    <div class="card-footer bg-white py-1 px-3 border-top text-muted d-flex justify-content-between align-items-center" style="font-size: 10px;">
                        <span id="lbl-info-${card.id}" class="text-truncate me-2 fw-semibold">
                            ${generarTextoFooter(card)}
                        </span>
                        <span id="lbl-check-${card.id}" class="text-nowrap"><i class="bi bi-clock me-1"></i> Esperando...</span>
                    </div>
                </div>
            </div>
        `;
    }

    function generarOpcionesSelectTarget(selectedVal) {
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
                const isSel = (selectedVal === dev.id || selectedVal === dev.ip) ? 'selected' : '';
                html += `<option value="${dev.id}" ${isSel}>${dev.nombre}</option>`;
            });
            html += `</optgroup>`;
        });

        const isCustomSel = selectedVal === 'custom' ? 'selected' : '';
        html += `<optgroup label="Personalizado"><option value="custom" ${isCustomSel}>⚙️ IP Personalizada...</option></optgroup>`;
        return html;
    }

    function generarOpcionesInterfaces(list, selectedIface) {
        if (!Array.isArray(list) || list.length === 0) {
            return `<option value="${selectedIface || 'ether1'}" selected>${selectedIface || 'ether1'}</option>`;
        }
        let html = '';
        list.forEach(item => {
            const name = typeof item === 'string' ? item : (item.name || item.id || 'ether1');
            const isSel = name === selectedIface ? 'selected' : '';
            html += `<option value="${name}" ${isSel}>${name}</option>`;
        });
        return html;
    }

    function generarHtmlStatsCard(card) {
        if (card.mode === 'traffic') {
            return `
                <div class="col-3">
                    <small class="text-muted d-block text-uppercase" style="font-size: 9px; font-weight: 700;">RX Actual</small>
                    <span class="fw-bold fs-7 text-primary" id="stat-rx-${card.id}">-- Mbps</span>
                </div>
                <div class="col-3">
                    <small class="text-muted d-block text-uppercase" style="font-size: 9px; font-weight: 700;">TX Actual</small>
                    <span class="fw-bold fs-7 text-success" id="stat-tx-${card.id}">-- Mbps</span>
                </div>
                <div class="col-3">
                    <small class="text-muted d-block text-uppercase" style="font-size: 9px; font-weight: 700;">RX Máx</small>
                    <span class="fw-bold fs-7 text-primary" id="stat-rxmax-${card.id}">--</span>
                </div>
                <div class="col-3">
                    <small class="text-muted d-block text-uppercase" style="font-size: 9px; font-weight: 700;">TX Máx</small>
                    <span class="fw-bold fs-7 text-success" id="stat-txmax-${card.id}">--</span>
                </div>
            `;
        } else {
            return `
                <div class="col-3">
                    <small class="text-muted d-block text-uppercase" style="font-size: 9px; font-weight: 700;">Actual</small>
                    <span class="fw-bold fs-7 text-dark" id="stat-current-${card.id}">-- ms</span>
                </div>
                <div class="col-3">
                    <small class="text-muted d-block text-uppercase" style="font-size: 9px; font-weight: 700;">Mín</small>
                    <span class="fw-bold fs-7 text-success" id="stat-min-${card.id}">--</span>
                </div>
                <div class="col-3">
                    <small class="text-muted d-block text-uppercase" style="font-size: 9px; font-weight: 700;">Prom</small>
                    <span class="fw-bold fs-7 text-primary" id="stat-avg-${card.id}">--</span>
                </div>
                <div class="col-3">
                    <small class="text-muted d-block text-uppercase" style="font-size: 9px; font-weight: 700;">Máx</small>
                    <span class="fw-bold fs-7 text-danger" id="stat-max-${card.id}">--</span>
                </div>
            `;
        }
    }

    function generarTextoFooter(card) {
        if (card.mode === 'traffic' && card.targetType === 'mikrotik') {
            return `<i class="bi bi-router me-1 text-primary"></i> ${card.targetIp} | Iface: ${card.interface}`;
        }
        return `<i class="bi bi-wifi me-1 text-primary"></i> Target IP: ${card.targetIp}`;
    }

    function inicializarEventosYChartCard(card) {
        const wrapper = document.getElementById(`card-wrapper-${card.id}`);
        if (!wrapper) return;

        // Listener selector de target/dispositivo
        const selTarget = wrapper.querySelector('.select-target');
        if (selTarget) {
            selTarget.onchange = function () {
                const val = this.value;
                card.targetId = val;
                card.history = [];

                if (val === 'custom') {
                    card.targetType = 'custom';
                    card.realId = null;
                    card.targetIp = card.customIp || '192.168.1.1';
                    card.mode = 'ping';
                } else {
                    const dev = multiState.devicesList.find(d => d.id === val);
                    if (dev) {
                        card.targetType = dev.tipo;
                        card.realId = dev.real_id;
                        card.targetIp = dev.ip;
                        card.title = dev.nombre;
                        if (dev.tipo !== 'mikrotik') {
                            card.mode = 'ping';
                        }
                    }
                }

                // Renderizar cambios de UI en la tarjeta
                const containerMk = document.getElementById(`container-mikrotik-options-${card.id}`);
                if (containerMk) containerMk.style.display = card.targetType === 'mikrotik' ? 'flex' : 'none';

                const containerCustom = document.getElementById(`container-custom-ip-${card.id}`);
                if (containerCustom) containerCustom.style.display = card.targetId === 'custom' ? 'block' : 'none';

                const statsContainer = document.getElementById(`stats-container-${card.id}`);
                if (statsContainer) statsContainer.innerHTML = generarHtmlStatsCard(card);

                const lblInfo = document.getElementById(`lbl-info-${card.id}`);
                if (lblInfo) lblInfo.innerHTML = generarTextoFooter(card);

                if (card.targetType === 'mikrotik') {
                    cargarInterfacesMikrotikCard(card);
                }

                crearChartInstanciaCard(card);
                guardarConfiguracionMulti(true);
                ejecutarBatchMonitoreo();
            };
        }

        // Listener selector de Modo (Ping vs Tráfico)
        const selMode = wrapper.querySelector('.select-mode');
        if (selMode) {
            selMode.onchange = function () {
                card.mode = this.value;
                card.history = [];

                const containerIface = document.getElementById(`container-iface-select-${card.id}`);
                if (containerIface) containerIface.style.display = card.mode === 'traffic' ? 'flex' : 'none';

                const statsContainer = document.getElementById(`stats-container-${card.id}`);
                if (statsContainer) statsContainer.innerHTML = generarHtmlStatsCard(card);

                const lblInfo = document.getElementById(`lbl-info-${card.id}`);
                if (lblInfo) lblInfo.innerHTML = generarTextoFooter(card);

                if (card.mode === 'traffic' && (!card.interfacesList || card.interfacesList.length === 0)) {
                    cargarInterfacesMikrotikCard(card);
                }

                crearChartInstanciaCard(card);
                guardarConfiguracionMulti(true);
                ejecutarBatchMonitoreo();
            };
        }

        // Listener selector de Interfaz
        const selIface = wrapper.querySelector('.select-interface');
        if (selIface) {
            selIface.onchange = function () {
                card.interface = this.value;
                card.history = [];

                const lblInfo = document.getElementById(`lbl-info-${card.id}`);
                if (lblInfo) lblInfo.innerHTML = generarTextoFooter(card);

                guardarConfiguracionMulti(true);
                ejecutarBatchMonitoreo();
            };
        }

        // Crear la instancia de Chart.js
        crearChartInstanciaCard(card);
    }

    window.aplicarCustomIpCard = function (cardId) {
        const card = multiState.cards.find(c => c.id === cardId);
        if (!card) return;

        const input = document.querySelector(`.input-custom-ip[data-card-id="${cardId}"]`);
        if (input && input.value.trim()) {
            const val = input.value.trim();
            card.customIp = val;
            card.targetIp = val;
            card.targetId = 'custom';
            card.targetType = 'custom';
            card.history = [];

            const lblInfo = document.getElementById(`lbl-info-${cardId}`);
            if (lblInfo) lblInfo.innerHTML = generarTextoFooter(card);

            guardarConfiguracionMulti(true);
            ejecutarBatchMonitoreo();
        }
    };

    function cargarInterfacesMikrotikCard(card) {
        if (!card.realId) return;

        const selIface = document.querySelector(`.select-interface[data-card-id="${card.id}"]`);
        if (selIface) {
            selIface.innerHTML = `<option value="">Cargando interfaces...</option>`;
        }

        fetch(`api.php?action=interfaces&id=${card.realId}`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && Array.isArray(res.data) && res.data.length > 0) {
                    card.interfacesList = res.data;
                    if (!card.interface || !card.interfacesList.some(i => (i.name || i) === card.interface)) {
                        card.interface = card.interfacesList[0].name || card.interfacesList[0] || 'ether1';
                    }
                } else {
                    card.interfacesList = [{ name: 'ether1' }, { name: 'ether2' }, { name: 'ether3' }, { name: 'bridge' }];
                }

                if (selIface) {
                    selIface.innerHTML = generarOpcionesInterfaces(card.interfacesList, card.interface);
                }

                const lblInfo = document.getElementById(`lbl-info-${card.id}`);
                if (lblInfo) lblInfo.innerHTML = generarTextoFooter(card);
            })
            .catch(err => {
                console.warn(`Error al obtener interfaces de MikroTik ${card.realId}:`, err);
                card.interfacesList = [{ name: 'ether1' }, { name: 'ether2' }];
                if (selIface) {
                    selIface.innerHTML = generarOpcionesInterfaces(card.interfacesList, card.interface);
                }
            });
    }

    function crearChartInstanciaCard(card) {
        const canvas = document.getElementById(`canvas-${card.id}`);
        if (!canvas) return;

        if (card.chart) {
            try { card.chart.destroy(); } catch (e) { }
            card.chart = null;
        }

        const ctx = canvas.getContext('2d');

        if (card.mode === 'traffic') {
            // Chart dual para Tráfico RX (Descarga) / TX (Carga)
            const gradRx = ctx.createLinearGradient(0, 0, 0, 240);
            gradRx.addColorStop(0, 'rgba(13, 110, 253, 0.3)');
            gradRx.addColorStop(1, 'rgba(13, 110, 253, 0.0)');

            const gradTx = ctx.createLinearGradient(0, 0, 0, 240);
            gradTx.addColorStop(0, 'rgba(32, 201, 151, 0.3)');
            gradTx.addColorStop(1, 'rgba(32, 201, 151, 0.0)');

            card.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'RX (Descarga Mbps)',
                            data: [],
                            borderColor: '#0d6efd',
                            backgroundColor: gradRx,
                            borderWidth: 2,
                            pointRadius: 2,
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'TX (Carga Mbps)',
                            data: [],
                            borderColor: '#20c997',
                            backgroundColor: gradTx,
                            borderWidth: 2,
                            pointRadius: 2,
                            fill: true,
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: { font: { size: 9 }, boxWidth: 10 }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function (ctx) {
                                    return ` ${ctx.dataset.label}: ${ctx.parsed.y} Mbps`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 9 }, maxTicksLimit: 6 } },
                        y: {
                            beginAtZero: true,
                            suggestedMax: 10,
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: {
                                font: { size: 9 },
                                callback: function (val) { return val + ' M'; }
                            }
                        }
                    }
                }
            });
        } else {
            // Chart de Ping / Latencia (ms)
            const gradient = ctx.createLinearGradient(0, 0, 0, 240);
            gradient.addColorStop(0, 'rgba(13, 110, 253, 0.25)');
            gradient.addColorStop(1, 'rgba(13, 110, 253, 0.0)');

            card.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Latencia (ms)',
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
                                    return ctx.parsed.y !== null ? ` Ping: ${ctx.parsed.y} ms` : ' Offline / Sin respuesta';
                                }
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 9 }, maxTicksLimit: 6 } },
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
    }

    function iniciarMonitoreo() {
        ejecutarBatchMonitoreo();
        reiniciarIntervalo();
    }

    function reiniciarIntervalo() {
        if (multiState.timer) {
            clearInterval(multiState.timer);
            multiState.timer = null;
        }

        if (!multiState.isPaused) {
            multiState.timer = setInterval(() => {
                ejecutarBatchMonitoreo();
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
            if (btn) btn.className = 'btn btn-sm btn-warning shadow-sm';
            if (lbl) {
                lbl.className = 'badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1';
                lbl.innerHTML = '<i class="bi bi-pause-circle me-1"></i> Pausado';
            }
        } else {
            if (icon) icon.className = 'bi bi-pause-fill me-1';
            if (text) text.textContent = 'Pausar';
            if (btn) btn.className = 'btn btn-sm btn-success shadow-sm';
            if (lbl) {
                const segs = Math.round(multiState.intervalMs / 1000);
                lbl.className = 'badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1';
                lbl.innerHTML = `<i class="bi bi-activity text-success me-1"></i> Activo (${segs}s)`;
            }
            iniciarMonitoreo();
        }
    };

    function ejecutarBatchMonitoreo() {
        if (multiState.cards.length === 0) return;

        const horaStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        // 1. Recopilar IPs para batch ping (modo 'ping')
        const pingIpsToQuery = [];
        multiState.cards.forEach(c => {
            if (c.mode === 'ping' && c.targetIp && !pingIpsToQuery.includes(c.targetIp)) {
                pingIpsToQuery.push(c.targetIp);
            }
        });

        // 2. Ejecutar Pings en Batch via AJAX
        if (pingIpsToQuery.length > 0) {
            const formData = new FormData();
            formData.append('ips', JSON.stringify(pingIpsToQuery));

            fetch('api.php?action=ping_batch', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success' && res.data) {
                        multiState.cards.forEach(c => {
                            if (c.mode === 'ping') {
                                const pingData = res.data[c.targetIp] || { ms: -1, online: false };
                                procesarRespuestaPing(c, pingData, horaStr);
                            }
                        });
                    }
                })
                .catch(err => console.error('Error al ejecutar batch ping:', err));
        }

        // 3. Procesar monitoreo de Tráfico para MikroTiks (modo 'traffic')
        multiState.cards.forEach(c => {
            if (c.mode === 'traffic' && c.targetType === 'mikrotik' && c.realId) {
                const iface = c.interface || 'ether1';
                fetch(`api.php?action=realtime_traffic&id=${c.realId}&interface=${encodeURIComponent(iface)}`)
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            procesarRespuestaTrafico(c, res, horaStr);
                        }
                    })
                    .catch(err => console.error(`Error al obtener tráfico de card ${c.id}:`, err));
            }
        });
    }

    function procesarRespuestaPing(card, pingData, horaStr) {
        card.history.push({
            time: horaStr,
            pingMs: pingData.online ? pingData.ms : null,
            online: pingData.online
        });

        if (card.history.length > 30) card.history.shift();

        actualizarGraficaEInformacionCard(card, pingData);
    }

    function procesarRespuestaTrafico(card, trafficData, horaStr) {
        card.history.push({
            time: horaStr,
            rxMbps: trafficData.rx_mbps,
            txMbps: trafficData.tx_mbps,
            online: true
        });

        if (card.history.length > 30) card.history.shift();

        actualizarGraficaEInformacionCard(card, trafficData);
    }

    function actualizarGraficaEInformacionCard(card, latestData) {
        const badgeVal = document.getElementById(`badge-val-${card.id}`);
        const lblCheck = document.getElementById(`lbl-check-${card.id}`);

        if (lblCheck) {
            lblCheck.innerHTML = `<i class="bi bi-clock me-1"></i> ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })}`;
        }

        if (card.mode === 'traffic') {
            const rxMbps = latestData ? (latestData.rx_mbps || 0) : 0;
            const txMbps = latestData ? (latestData.tx_mbps || 0) : 0;

            if (badgeVal) {
                badgeVal.className = 'badge bg-primary font-monospace fs-6 shadow-sm';
                badgeVal.innerHTML = `<i class="bi bi-arrow-down me-1"></i>${rxMbps}M | <i class="bi bi-arrow-up me-1"></i>${txMbps}M`;
            }

            const statRx = document.getElementById(`stat-rx-${card.id}`);
            const statTx = document.getElementById(`stat-tx-${card.id}`);
            const statRxMax = document.getElementById(`stat-rxmax-${card.id}`);
            const statTxMax = document.getElementById(`stat-txmax-${card.id}`);

            if (statRx) statRx.textContent = `${rxMbps} Mbps`;
            if (statTx) statTx.textContent = `${txMbps} Mbps`;

            const rxVals = card.history.map(h => h.rxMbps).filter(v => v !== undefined && v !== null);
            const txVals = card.history.map(h => h.txMbps).filter(v => v !== undefined && v !== null);

            if (statRxMax) statRxMax.textContent = rxVals.length > 0 ? `${Math.max(...rxVals)} M` : '--';
            if (statTxMax) statTxMax.textContent = txVals.length > 0 ? `${Math.max(...txVals)} M` : '--';

            if (card.chart) {
                card.chart.data.labels = card.history.map(h => h.time);
                card.chart.data.datasets[0].data = card.history.map(h => h.rxMbps);
                card.chart.data.datasets[1].data = card.history.map(h => h.txMbps);
                card.chart.update();
            }
        } else {
            // Modo Ping
            const statCurrent = document.getElementById(`stat-current-${card.id}`);
            const statMin = document.getElementById(`stat-min-${card.id}`);
            const statMax = document.getElementById(`stat-max-${card.id}`);
            const statAvg = document.getElementById(`stat-avg-${card.id}`);

            if (latestData && latestData.online) {
                const msVal = latestData.ms;
                if (badgeVal) {
                    let colorBg = 'bg-success';
                    if (msVal > 100 && msVal <= 200) colorBg = 'bg-warning text-dark';
                    else if (msVal > 200) colorBg = 'bg-danger';

                    badgeVal.className = `badge ${colorBg} font-monospace fs-6 shadow-sm`;
                    badgeVal.textContent = `${msVal} ms`;
                }
                if (statCurrent) statCurrent.textContent = `${msVal} ms`;
            } else if (latestData && !latestData.online) {
                if (badgeVal) {
                    badgeVal.className = 'badge bg-danger font-monospace fs-6 shadow-sm';
                    badgeVal.textContent = 'OFFLINE';
                }
                if (statCurrent) statCurrent.textContent = 'DOWN';
            }

            const validValues = card.history.map(h => h.pingMs).filter(v => v !== null && v !== undefined);
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

            if (card.chart) {
                card.chart.data.labels = card.history.map(h => h.time);
                card.chart.data.datasets[0].data = card.history.map(h => h.pingMs);

                const lastVal = validValues.length > 0 ? validValues[validValues.length - 1] : null;
                if (lastVal === null || lastVal > 200) {
                    card.chart.data.datasets[0].borderColor = '#dc3545';
                    card.chart.data.datasets[0].pointBackgroundColor = '#dc3545';
                } else if (lastVal > 100) {
                    card.chart.data.datasets[0].borderColor = '#ffc107';
                    card.chart.data.datasets[0].pointBackgroundColor = '#ffc107';
                } else {
                    card.chart.data.datasets[0].borderColor = '#0d6efd';
                    card.chart.data.datasets[0].pointBackgroundColor = '#0d6efd';
                }

                card.chart.update();
            }
        }
    }
})();
