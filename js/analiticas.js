$(document).ready(function() {
    let trafficCharts = {}; // Para manejar múltiples gráficas de interfaces
    let chartPing, chartRecursos, chartCaidas, chartPingEquipo;

    // Inicializar gráficas estáticas vacías
    function initCharts() {
        // Chart Ping (Mikrotik)
        const ctxPing = document.getElementById('chartPing').getContext('2d');
        chartPing = new Chart(ctxPing, {
            type: 'line',
            data: { datasets: [] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { type: 'time', time: { tooltipFormat: 'dd MMM yyyy HH:mm' } },
                    y: { title: { display: true, text: 'Milisegundos (ms)' }, beginAtZero: true }
                }
            }
        });

        // Chart Recursos
        const ctxRecursos = document.getElementById('chartRecursos').getContext('2d');
        chartRecursos = new Chart(ctxRecursos, {
            type: 'line',
            data: { datasets: [] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { type: 'time', time: { tooltipFormat: 'dd MMM yyyy HH:mm' } },
                    y: { title: { display: true, text: 'Porcentaje / Bytes' }, beginAtZero: true }
                }
            }
        });

        // Chart Caídas
        const ctxCaidas = document.getElementById('chartCaidas').getContext('2d');
        chartCaidas = new Chart(ctxCaidas, {
            type: 'bar',
            data: { labels: [], datasets: [] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: { title: { display: true, text: 'Cantidad' }, beginAtZero: true }
                }
            }
        });

        // Chart Ping Equipo
        const ctxPingEquipo = document.getElementById('chartPingEquipo').getContext('2d');
        chartPingEquipo = new Chart(ctxPingEquipo, {
            type: 'line',
            data: { datasets: [] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { type: 'time', time: { tooltipFormat: 'dd MMM yyyy HH:mm' } },
                    y: { title: { display: true, text: 'Milisegundos (ms)' }, beginAtZero: true }
                }
            }
        });

        window.chartPingInstance = chartPing;
        window.chartRecursosInstance = chartRecursos;
        window.chartCaidasInstance = chartCaidas;
        window.chartPingEquipoInstance = chartPingEquipo;
        window.trafficChartsInstance = trafficCharts;
    }

    function cargarDatosGraficas() {
        const mikrotik_id = $('#selectAnaliticasMikrotik').val();
        const horas = $('#selectAnaliticasHoras').val();
        
        cargarCaidas(horas);

        if (!mikrotik_id) {
            $('#analiticasChartsContainer').hide();
            return;
        }

        $('#analiticasChartsContainer').show();
        
        if ($('#selectAnaliticasEquipo').val() !== "") {
            $('#selectAnaliticasEquipo').val('');
        }

        $('#contenedorInterfaces').show();
        $('#contenedorChartPingMikrotik, #contenedorChartRecursos, #contenedorChartCaidas').show();
        $('#contenedorChartPingEquipo, #contenedorKpiEquipoAnaliticas').hide();

        cargarTraficoDynamic(mikrotik_id, horas);
        cargarPing(mikrotik_id, horas);
        cargarRecursos(mikrotik_id, horas);
    }

    function cargarTraficoDynamic(mikrotik_id, horas) {
        $.ajax({
            url: 'controllers/AnaliticasController.php',
            type: 'GET',
            data: { action: 'getInterfaces', mikrotik_id: mikrotik_id },
            dataType: 'json',
            success: function(interfaces) {
                for (let key in trafficCharts) {
                    trafficCharts[key].destroy();
                }
                trafficCharts = {};
                $('#contenedorInterfaces').empty();
                $('#contenedorFiltroInterfaces').empty();
                $('#contenedorFiltroInterfacesWrapper').hide();

                if (!interfaces || interfaces.length === 0) {
                    $('#contenedorInterfaces').html('<div class="alert alert-info">No hay datos de tráfico para este Mikrotik.</div>');
                } else {
                    $('#contenedorFiltroInterfacesWrapper').show();
                    let filterPillsHtml = `<button class="btn btn-xs btn-primary active me-1 btn-filter-iface" data-iface="all"><i class="bi bi-check2-all me-1"></i>Todas</button>`;
                    interfaces.forEach(iface => {
                        filterPillsHtml += `<button class="btn btn-xs btn-outline-secondary me-1 btn-filter-iface" data-iface="${iface}">${iface}</button>`;
                    });
                    $('#contenedorFiltroInterfaces').html(filterPillsHtml);

                    $('.btn-filter-iface').off('click').on('click', function() {
                        const targetIface = $(this).data('iface');
                        if (targetIface === 'all') {
                            $('.btn-filter-iface').removeClass('btn-primary active').addClass('btn-outline-secondary');
                            $(this).removeClass('btn-outline-secondary').addClass('btn-primary active');
                            $('.card-interface-wrapper').show();
                        } else {
                            $('.btn-filter-iface[data-iface="all"]').removeClass('btn-primary active').addClass('btn-outline-secondary');
                            $(this).toggleClass('btn-primary active btn-outline-secondary');
                            
                            const activeIfaces = [];
                            $('.btn-filter-iface.active:not([data-iface="all"])').each(function() {
                                activeIfaces.push($(this).data('iface'));
                            });

                            if (activeIfaces.length === 0) {
                                $('.btn-filter-iface[data-iface="all"]').removeClass('btn-outline-secondary').addClass('btn-primary active');
                                $('.card-interface-wrapper').show();
                            } else {
                                $('.card-interface-wrapper').each(function() {
                                    const cardIface = $(this).data('iface');
                                    if (activeIfaces.includes(cardIface)) {
                                        $(this).show();
                                    } else {
                                        $(this).hide();
                                    }
                                });
                            }
                        }
                    });

                    $('#contenedorInterfaces').append('<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div> Cargando y renderizando gráficas de tráfico...</div>');

                    $.ajax({
                        url: 'controllers/AnaliticasController.php',
                        type: 'GET',
                        data: { action: 'getTrafico', mikrotik_id: mikrotik_id, horas: horas },
                        dataType: 'json',
                        success: function(resTrafico) {
                            $('#contenedorInterfaces').empty();
                            
                            let dataByIf = {};
                            interfaces.forEach(iface => dataByIf[iface] = {rx:[], tx:[]});
                            
                            resTrafico.forEach(item => {
                                if (dataByIf[item.interface]) {
                                    let timeMs = new Date(item.fecha_registro.replace(' ', 'T')).getTime();
                                    dataByIf[item.interface].rx.push({ x: timeMs, y: parseFloat((item.rx_bits / 1000000).toFixed(2)) });
                                    dataByIf[item.interface].tx.push({ x: timeMs, y: parseFloat((item.tx_bits / 1000000).toFixed(2)) });
                                }
                            });

                            let isFirst = true;
                            interfaces.forEach((iface, index) => {
                                let divId = `chart-trafico-${index}`;
                                let html = `
                                <div class="card border-0 shadow-sm mb-4 card-interface-wrapper" data-iface="${iface}">
                                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                                        <h5 class="card-title fs-6 fw-bold mb-0">Tráfico - Interface: <span class="text-primary">${iface}</span></h5>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size: 10px;">${iface}</span>
                                            <button class="btn btn-xs btn-outline-secondary" onclick="abrirModalGraficaAnaliticas('iface_${iface}', 'Tráfico - Interface: ${iface}')"><i class="bi bi-arrows-fullscreen me-1"></i> Ampliar</button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div style="height: ${isFirst ? '300px' : '200px'};">
                                            <canvas id="${divId}"></canvas>
                                        </div>
                                    </div>
                                </div>`;
                                $('#contenedorInterfaces').append(html);
                                isFirst = false;

                                let ctx = document.getElementById(divId).getContext('2d');
                                trafficCharts[iface] = new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        datasets: [
                                            { label: 'Rx (Mbps)', data: dataByIf[iface].rx, borderColor: '#2c3e50', backgroundColor: 'rgba(44,62,80,0.1)', fill: true, tension: 0.1, borderWidth: 2 },
                                            { label: 'Tx (Mbps)', data: dataByIf[iface].tx, borderColor: '#3498db', backgroundColor: 'rgba(52,152,219,0.1)', fill: true, tension: 0.1, borderWidth: 2 }
                                        ]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        parsing: false,
                                        animation: false,
                                        scales: {
                                            x: { type: 'time', time: { tooltipFormat: 'dd MMM yyyy HH:mm' } },
                                            y: { title: { display: true, text: 'Mbps' }, beginAtZero: true }
                                        },
                                        plugins: { decimation: { enabled: true, algorithm: 'lttb' } }
                                    }
                                });
                            });

                            window.trafficChartsInstance = trafficCharts;
                        }
                    });
                }
            }
        });
    }

    function cargarPing(mikrotik_id, horas) {
        $.ajax({
            url: 'controllers/AnaliticasController.php',
            type: 'GET',
            data: { action: 'getPing', mikrotik_id: mikrotik_id, horas: horas },
            dataType: 'json',
            success: function(res) {
                const googleData = [];
                const serverData = [];
                
                res.forEach(item => {
                    if (item.tipo === 'google') googleData.push({ x: item.fecha_registro, y: item.ms });
                    if (item.tipo === 'servidor') serverData.push({ x: item.fecha_registro, y: item.ms });
                });

                chartPing.data.datasets = [
                    { label: 'Ping Servidor', data: serverData, borderColor: '#3498db', tension: 0.1 },
                    { label: 'Ping Google (8.8.8.8)', data: googleData, borderColor: '#e74c3c', tension: 0.1 }
                ];
                chartPing.options.animation = false;
                chartPing.update();
                window.chartPingInstance = chartPing;
            }
        });
    }

    function cargarRecursos(mikrotik_id, horas) {
        $.ajax({
            url: 'controllers/AnaliticasController.php',
            type: 'GET',
            data: { action: 'getRecursos', mikrotik_id: mikrotik_id, horas: horas },
            dataType: 'json',
            success: function(res) {
                const cpuData = [];
                const ramData = [];
                
                res.forEach(item => {
                    cpuData.push({ x: item.fecha_registro, y: item.cpu_uso });
                    let ramUsadaMB = (item.ram_total - item.ram_libre) / (1024 * 1024);
                    ramData.push({ x: item.fecha_registro, y: ramUsadaMB.toFixed(2) });
                });

                chartRecursos.data.datasets = [
                    { label: 'Uso CPU (%)', data: cpuData, borderColor: '#e67e22', yAxisID: 'y' },
                    { label: 'RAM Usada (MB)', data: ramData, borderColor: '#9b59b6', yAxisID: 'y1' }
                ];
                
                chartRecursos.options.scales.y1 = {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'Megabytes (MB)' },
                    grid: { drawOnChartArea: false }
                };
                chartRecursos.options.animation = false;
                chartRecursos.update();
                window.chartRecursosInstance = chartRecursos;
            }
        });
    }

    function cargarCaidas(horas) {
        $.ajax({
            url: 'controllers/AnaliticasController.php',
            type: 'GET',
            data: { action: 'getTopCaidas', horas: horas },
            dataType: 'json',
            success: function(res) {
                const labels = [];
                const caidasCount = [];
                const duracionTotal = [];
                
                res.forEach(item => {
                    labels.push(item.nombre_nodo);
                    caidasCount.push(item.total_caidas);
                    duracionTotal.push(item.total_minutos);
                });

                chartCaidas.data = {
                    labels: labels,
                    datasets: [
                        { label: 'Número de Caídas', data: caidasCount, backgroundColor: '#e74c3c' },
                        { label: 'Minutos Totales Inactivo', data: duracionTotal, backgroundColor: '#f39c12' }
                    ]
                };
                chartCaidas.update();
                window.chartCaidasInstance = chartCaidas;
            }
        });
    }

    function updateEquipoAnaliticasKPIs(res) {
        if (!res || res.length === 0) {
            $('#stat-an-ping-avg').text('0 ms');
            $('#stat-an-ping-minmax').text('Min: 0 / Max: 0');
            $('#stat-an-uptime').text('0%');
            $('#stat-an-online-time').text('0 min');
            $('#stat-an-offline-time').text('Caído: 0 min');
            $('#stat-an-loss').text('0%');
            $('#stat-an-probes').text('Muestras: 0');
            return;
        }

        let totalMs = 0;
        let validCount = 0;
        let offlineCount = 0;
        let minMs = Infinity;
        let maxMs = -1;

        res.forEach(item => {
            const ms = parseInt(item.ms);
            if (ms > 0) {
                totalMs += ms;
                validCount++;
                if (ms < minMs) minMs = ms;
                if (ms > maxMs) maxMs = ms;
            } else {
                offlineCount++;
            }
        });

        const totalProbes = res.length;
        const avgMs = validCount > 0 ? Math.round(totalMs / validCount) : 0;
        const uptimePercent = Math.round((validCount / totalProbes) * 1000) / 10;
        const lossPercent = Math.round((offlineCount / totalProbes) * 1000) / 10;

        const formatTime = (mins) => {
            if (mins >= 60) {
                const h = Math.floor(mins / 60);
                const m = mins % 60;
                return `${h}h ${m}m`;
            }
            return `${mins} min`;
        };

        $('#stat-an-ping-avg').text(`${avgMs} ms`);
        $('#stat-an-ping-minmax').text(`Min: ${minMs === Infinity ? 0 : minMs}ms / Max: ${maxMs === -1 ? 0 : maxMs}ms`);
        $('#stat-an-uptime').text(`${uptimePercent}%`).attr('class', uptimePercent >= 98 ? 'fs-4 fw-bold text-success' : (uptimePercent >= 90 ? 'fs-4 fw-bold text-warning' : 'fs-4 fw-bold text-danger'));
        $('#stat-an-online-time').text(formatTime(validCount));
        $('#stat-an-offline-time').text(`Caído: ${formatTime(offlineCount)}`);
        $('#stat-an-loss').text(`${lossPercent}%`);
        $('#stat-an-probes').text(`Muestras BD: ${totalProbes}`);
    }

    function cargarPingEquipo(equipo_id, horas) {
        if (!equipo_id) {
            $('#contenedorChartPingEquipo, #contenedorKpiEquipoAnaliticas').hide();
            return;
        }

        if ($('#selectAnaliticasMikrotik').val() !== "") {
            $('#selectAnaliticasMikrotik').val('');
        }

        $('#analiticasChartsContainer').show();
        $('#contenedorInterfaces, #contenedorFiltroInterfacesWrapper').hide();
        $('#contenedorChartPingMikrotik, #contenedorChartRecursos, #contenedorChartCaidas').hide();
        $('#contenedorChartPingEquipo, #contenedorKpiEquipoAnaliticas').show();
        
        $.ajax({
            url: 'controllers/AnaliticasController.php',
            type: 'GET',
            data: { action: 'getPingEquipo', equipo_id: equipo_id, horas: horas },
            dataType: 'json',
            success: function(res) {
                updateEquipoAnaliticasKPIs(res);

                const msData = [];
                res.forEach(item => {
                    msData.push({ x: item.fecha_registro, y: item.ms });
                });

                chartPingEquipo.data.datasets = [
                    { label: 'Latencia a Equipo (ms)', data: msData, borderColor: '#1abc9c', tension: 0.1, fill: true, backgroundColor: '#1abc9c33' }
                ];
                chartPingEquipo.options.animation = false;
                chartPingEquipo.update();
                window.chartPingEquipoInstance = chartPingEquipo;
            }
        });
    }

    // Inicializar todo si estamos en la vista
    if ($('#chartPing').length > 0) {
        initCharts();
        cargarCaidas($('#selectAnaliticasHoras').val());
    }

    // Eventos DOM
    $(document).on('change', '#selectAnaliticasMikrotik', function() {
        if ($(this).val() !== "") {
            cargarDatosGraficas();
        } else {
            $('#analiticasChartsContainer').hide();
        }
    });

    $(document).on('change', '#selectAnaliticasEquipo', function() {
        if ($(this).val() !== "") {
            cargarPingEquipo($(this).val(), $('#selectAnaliticasHoras').val());
        } else {
            $('#contenedorChartPingEquipo, #contenedorKpiEquipoAnaliticas').hide();
            if ($('#selectAnaliticasMikrotik').val() === "") {
                $('#analiticasChartsContainer').hide();
            }
        }
    });

    $(document).on('change', '#selectAnaliticasHoras', function() {
        if ($('#selectAnaliticasMikrotik').val() !== "") {
            cargarDatosGraficas();
        } else if ($('#selectAnaliticasEquipo').val() !== "") {
            cargarPingEquipo($('#selectAnaliticasEquipo').val(), $(this).val());
        } else {
            cargarCaidas($(this).val());
        }
    });

    $(document).on('click', '#btnRefreshCharts', function() {
        if ($('#selectAnaliticasMikrotik').val() !== "") {
            cargarDatosGraficas();
        } else if ($('#selectAnaliticasEquipo').val() !== "") {
            cargarPingEquipo($('#selectAnaliticasEquipo').val(), $('#selectAnaliticasHoras').val());
        }
    });
    
    window.initAnaliticasModule = function() {
        if ($('#chartPing').length > 0) {
            for (let key in trafficCharts) {
                trafficCharts[key].destroy();
            }
            trafficCharts = {};
            
            if (chartPing) chartPing.destroy();
            if (chartRecursos) chartRecursos.destroy();
            if (chartCaidas) chartCaidas.destroy();
            if (chartPingEquipo) chartPingEquipo.destroy();
            
            initCharts();
            if ($('#selectAnaliticasMikrotik').val() !== "") {
                cargarDatosGraficas();
            } else if ($('#selectAnaliticasEquipo').val() !== "") {
                cargarPingEquipo($('#selectAnaliticasEquipo').val(), $('#selectAnaliticasHoras').val());
            } else {
                cargarCaidas($('#selectAnaliticasHoras').val());
            }
        }
    };
});

// Funciones Globales para Zoom Modal de Analíticas
let modalAnaliticasChartInstance = null;

window.abrirModalGraficaAnaliticas = function(chartKey, title) {
    let sourceChart = null;

    if (chartKey === 'ping') sourceChart = window.chartPingInstance || null;
    else if (chartKey === 'recursos') sourceChart = window.chartRecursosInstance || null;
    else if (chartKey === 'caidas') sourceChart = window.chartCaidasInstance || null;
    else if (chartKey === 'pingEquipo') sourceChart = window.chartPingEquipoInstance || null;
    else if (chartKey.startsWith('iface_')) {
        const ifaceName = chartKey.replace('iface_', '');
        sourceChart = (window.trafficChartsInstance && window.trafficChartsInstance[ifaceName]) ? window.trafficChartsInstance[ifaceName] : null;
    }

    if (!sourceChart || !sourceChart.data || !sourceChart.data.datasets) return;

    document.getElementById('modalAnaliticasTitle').innerHTML = `<i class="bi bi-arrows-fullscreen me-2"></i> ${title}`;

    if (modalAnaliticasChartInstance) {
        modalAnaliticasChartInstance.destroy();
        modalAnaliticasChartInstance = null;
    }

    const ctx = document.getElementById('canvasAnaliticasFullScreen').getContext('2d');
    const chartType = sourceChart.config.type || 'line';
    const isLine = (chartType === 'line');

    let clonedData = {
        labels: sourceChart.data.labels ? sourceChart.data.labels.slice() : [],
        datasets: sourceChart.data.datasets.map(ds => ({
            label: ds.label || '',
            data: ds.data ? ds.data.slice() : [],
            borderColor: ds.borderColor,
            backgroundColor: ds.backgroundColor,
            fill: ds.fill,
            tension: ds.tension,
            borderWidth: ds.borderWidth,
            yAxisID: ds.yAxisID
        }))
    };

    let options = {
        responsive: true,
        maintainAspectRatio: false,
        scales: isLine ? {
            x: { type: 'time', time: { tooltipFormat: 'dd MMM yyyy HH:mm' } },
            y: { title: { display: true, text: 'Valor' }, beginAtZero: true }
        } : {
            x: { beginAtZero: true }
        },
        plugins: {
            zoom: {
                pan: { enabled: true, mode: 'x' },
                zoom: { wheel: { enabled: true }, drag: { enabled: true }, mode: 'x' }
            }
        }
    };

    if (chartKey === 'recursos') {
        options.scales.y.title = { display: true, text: 'Uso CPU (%)' };
        options.scales.y1 = {
            type: 'linear',
            display: true,
            position: 'right',
            title: { display: true, text: 'RAM Usada (MB)' },
            grid: { drawOnChartArea: false }
        };
    } else if (chartKey.startsWith('iface_')) {
        options.scales.y.title = { display: true, text: 'Mbps' };
    } else if (chartKey === 'ping' || chartKey === 'pingEquipo') {
        options.scales.y.title = { display: true, text: 'Milisegundos (ms)' };
    }

    modalAnaliticasChartInstance = new Chart(ctx, {
        type: chartType,
        data: clonedData,
        options: options
    });

    let modal = new bootstrap.Modal(document.getElementById('modalAnaliticasFullScreen'));
    modal.show();
};

window.zoomAnaliticasChart = function(factor) {
    if (modalAnaliticasChartInstance && typeof modalAnaliticasChartInstance.zoom === 'function') {
        modalAnaliticasChartInstance.zoom(factor);
    }
};

window.resetAnaliticasChartZoom = function() {
    if (modalAnaliticasChartInstance && typeof modalAnaliticasChartInstance.resetZoom === 'function') {
        modalAnaliticasChartInstance.resetZoom();
    }
};
