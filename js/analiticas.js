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
    }

    function cargarDatosGraficas() {
        const mikrotik_id = $('#selectAnaliticasMikrotik').val();
        const horas = $('#selectAnaliticasHoras').val();
        
        cargarCaidas(horas); // Caídas es global

        if (!mikrotik_id) {
            $('#analiticasChartsContainer').hide();
            return;
        }

        $('#analiticasChartsContainer').show();
        
        // Limpiamos selección de equipo si se seleccionó mikrotik
        if ($('#selectAnaliticasEquipo').val() !== "") {
            $('#selectAnaliticasEquipo').val('');
        }

        $('#contenedorInterfaces').show();
        $('#contenedorChartPingMikrotik, #contenedorChartRecursos, #contenedorChartCaidas').show();
        $('#contenedorChartPingEquipo').hide();

        cargarTraficoDynamic(mikrotik_id, horas);
        cargarPing(mikrotik_id, horas);
        cargarRecursos(mikrotik_id, horas);
    }

    function cargarTraficoDynamic(mikrotik_id, horas) {
        // 1. Obtener interfaces
        $.ajax({
            url: 'controllers/AnaliticasController.php',
            type: 'GET',
            data: { action: 'getInterfaces', mikrotik_id: mikrotik_id },
            dataType: 'json',
            success: function(interfaces) {
                // Limpiar gráficas anteriores
                for (let key in trafficCharts) {
                    trafficCharts[key].destroy();
                }
                trafficCharts = {};
                $('#contenedorInterfaces').empty();

                if (!interfaces || interfaces.length === 0) {
                    $('#contenedorInterfaces').html('<div class="alert alert-info">No hay datos de tráfico para este Mikrotik.</div>');
                } else {
                    // Mostrar loader
                    $('#contenedorInterfaces').append('<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div> Cargando y renderizando gráficas de tráfico...</div>');

                    // 2. Fetch todo el tráfico
                    $.ajax({
                        url: 'controllers/AnaliticasController.php',
                        type: 'GET',
                        data: { action: 'getTrafico', mikrotik_id: mikrotik_id, horas: horas },
                        dataType: 'json',
                        success: function(resTrafico) {
                            $('#contenedorInterfaces').empty(); // remover loader
                            
                            let dataByIf = {};
                            interfaces.forEach(iface => dataByIf[iface] = {rx:[], tx:[]});
                            
                            resTrafico.forEach(item => {
                                if (dataByIf[item.interface]) {
                                    // Para parsing: false en Chart.js, x debe ser timestamp en ms y y debe ser número
                                    let timeMs = new Date(item.fecha_registro.replace(' ', 'T')).getTime();
                                    dataByIf[item.interface].rx.push({ x: timeMs, y: parseFloat((item.rx_bits / 1000000).toFixed(2)) });
                                    dataByIf[item.interface].tx.push({ x: timeMs, y: parseFloat((item.tx_bits / 1000000).toFixed(2)) });
                                }
                            });

                            let isFirst = true;
                            interfaces.forEach((iface, index) => {
                                let divId = `chart-trafico-${index}`;
                                let html = `
                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                                        <h5 class="card-title fs-6 fw-bold mb-0">Tráfico - Interface: <span class="text-primary">${iface}</span></h5>
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
                                        parsing: false, // Performance
                                        animation: false, // Turn off animation for large datasets
                                        scales: {
                                            x: { type: 'time', time: { tooltipFormat: 'dd MMM yyyy HH:mm' } },
                                            y: { title: { display: true, text: 'Mbps' }, beginAtZero: true }
                                        },
                                        plugins: { decimation: { enabled: true, algorithm: 'lttb' } }
                                    }
                                });
                            });
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
            }
        });
    }

    function cargarPingEquipo(equipo_id, horas) {
        if (!equipo_id) {
            $('#contenedorChartPingEquipo').hide();
            return;
        }

        // Limpiamos el mikrotik si se seleccionó equipo
        if ($('#selectAnaliticasMikrotik').val() !== "") {
            $('#selectAnaliticasMikrotik').val('');
        }

        $('#analiticasChartsContainer').show();
        $('#contenedorInterfaces').hide();
        $('#contenedorChartPingMikrotik, #contenedorChartRecursos, #contenedorChartCaidas').hide();
        $('#contenedorChartPingEquipo').show();
        
        $.ajax({
            url: 'controllers/AnaliticasController.php',
            type: 'GET',
            data: { action: 'getPingEquipo', equipo_id: equipo_id, horas: horas },
            dataType: 'json',
            success: function(res) {
                const msData = [];
                
                res.forEach(item => {
                    msData.push({ x: item.fecha_registro, y: item.ms });
                });

                chartPingEquipo.data.datasets = [
                    { label: 'Latencia a Equipo (ms)', data: msData, borderColor: '#1abc9c', tension: 0.1, fill: true, backgroundColor: '#1abc9c33' }
                ];
                chartPingEquipo.options.animation = false;
                chartPingEquipo.update();
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
            $('#contenedorChartPingEquipo').hide();
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
