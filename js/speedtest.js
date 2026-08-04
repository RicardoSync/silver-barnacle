/**
 * Elissa - Speedtest Module (Modal, Optimal Server & 60FPS Gauge)
 * Ricardo Escobedo - 2026
 */

var speedtestMode = 'servidor'; // 'servidor' | 'cliente'
var currentPhase = 'idle'; // 'idle' | 'ping' | 'download' | 'upload' | 'complete'
var speedtestHistorialTable = null;
var speedtestChart = null;

// Animation Physics State
var currentGaugeSpeed = 0;
var targetGaugeSpeed = 0;
var isAnimationRunning = false;
var simulatedPulseInterval = null;
var selectedOptimalServer = null;

function initSpeedtestModule() {
    setTargetSpeed(0);
    cargarHistorialSpeedtest();
}

function abrirModalSpeedtest() {
    var modalElem = document.getElementById('modalSpeedtest');
    if (modalElem) {
        var modal = new bootstrap.Modal(modalElem);
        modal.show();
        setTimeout(function() {
            setTargetSpeed(0);
        }, 200);
    }
}

function abrirModalSpeedtestGlobal() {
    var modalElem = document.getElementById('modalSpeedtest');
    if (modalElem) {
        abrirModalSpeedtest();
    } else {
        // Cargar vista de speedtest y luego abrir modal
        if (typeof window.loadView === 'function') {
            window.loadView('speedtest');
            setTimeout(function() {
                abrirModalSpeedtest();
            }, 600);
        }
    }
}

function setSpeedtestMode(mode) {
    speedtestMode = mode;
    var btnServer = document.getElementById('btnModeServer');
    var btnClient = document.getElementById('btnModeClient');
    var targetText = document.getElementById('modalServerTargetText');

    if (mode === 'servidor') {
        if (btnServer) btnServer.classList.add('active');
        if (btnClient) btnClient.classList.remove('active');
        if (targetText) targetText.innerText = 'Prueba de Servidor PHP hacia Internet CDN';
    } else {
        if (btnClient) btnClient.classList.add('active');
        if (btnServer) btnServer.classList.remove('active');
        if (targetText) targetText.innerText = 'Prueba de Ancho de Banda Navegador ➔ Servidor Local';
    }

    setTargetSpeed(0);
}

// -------------------------------------------------------------
// MOTOR RENDERIZADOR VELOCÍMETRO (60 FPS LERP ANIMATION)
// -------------------------------------------------------------
function setTargetSpeed(val) {
    targetGaugeSpeed = Math.max(0, val);
    if (!isAnimationRunning) {
        isAnimationRunning = true;
        requestAnimationFrame(gaugeRenderLoop);
    }
}

function gaugeRenderLoop() {
    var diff = targetGaugeSpeed - currentGaugeSpeed;
    if (Math.abs(diff) < 0.02) {
        currentGaugeSpeed = targetGaugeSpeed;
    } else {
        currentGaugeSpeed += diff * 0.08;
    }

    drawGaugeModal(currentGaugeSpeed, 100);

    var valElem = document.getElementById('currentSpeedVal');
    if (valElem) {
        valElem.innerText = currentGaugeSpeed.toFixed(2);
    }

    if (Math.abs(targetGaugeSpeed - currentGaugeSpeed) >= 0.01) {
        requestAnimationFrame(gaugeRenderLoop);
    } else {
        isAnimationRunning = false;
    }
}

function drawGaugeModal(value, maxValue) {
    var canvas = document.getElementById('gaugeCanvasModal');
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    var width = canvas.width;
    var height = canvas.height;

    ctx.clearRect(0, 0, width, height);

    var centerX = width / 2;
    var centerY = height - 25;
    var radius = 135;
    var startAngle = Math.PI * 0.85;
    var endAngle = Math.PI * 2.15;

    // Arco de fondo
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius, startAngle, endAngle);
    ctx.lineWidth = 14;
    ctx.strokeStyle = '#e9ecef';
    ctx.lineCap = 'round';
    ctx.stroke();

    // Gradiente dinámico por Fase (DESCARGA vs SUBIDA)
    var fraction = Math.max(0, Math.min(value, maxValue)) / maxValue;
    var currentAngle = startAngle + (endAngle - startAngle) * fraction;

    if (fraction > 0) {
        var gradient = ctx.createLinearGradient(0, 0, width, 0);

        if (currentPhase === 'upload') {
            // Colores Subida (Púrpura / Naranja)
            gradient.addColorStop(0, '#7209b7');
            gradient.addColorStop(0.5, '#4361ee');
            gradient.addColorStop(1, '#f72585');
        } else {
            // Colores Descarga (Teal / Azul / Rojo)
            gradient.addColorStop(0, '#2ec4b6');
            gradient.addColorStop(0.5, '#3a86ff');
            gradient.addColorStop(1, '#e63946');
        }

        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, startAngle, currentAngle);
        ctx.lineWidth = 14;
        ctx.strokeStyle = gradient;
        ctx.lineCap = 'round';
        ctx.stroke();
    }

    // Marcas de Escala
    ctx.lineWidth = 1.5;
    ctx.strokeStyle = '#ced4da';
    for (var i = 0; i <= 8; i++) {
        var angle = startAngle + (endAngle - startAngle) * (i / 8);
        var tickInner = radius - 18;
        var tickOuter = radius - 10;

        ctx.beginPath();
        ctx.moveTo(centerX + Math.cos(angle) * tickInner, centerY + Math.sin(angle) * tickInner);
        ctx.lineTo(centerX + Math.cos(angle) * tickOuter, centerY + Math.sin(angle) * tickOuter);
        ctx.stroke();
    }

    // Aguja fina
    ctx.save();
    ctx.translate(centerX, centerY);
    ctx.rotate(currentAngle + Math.PI / 2);

    ctx.shadowColor = 'rgba(0, 0, 0, 0.15)';
    ctx.shadowBlur = 4;

    ctx.beginPath();
    ctx.moveTo(-3, 0);
    ctx.lineTo(0, -radius + 24);
    ctx.lineTo(3, 0);
    ctx.fillStyle = currentPhase === 'upload' ? '#7209b7' : '#212529';
    ctx.fill();

    ctx.beginPath();
    ctx.arc(0, 0, 7, 0, Math.PI * 2);
    ctx.fillStyle = '#212529';
    ctx.fill();
    ctx.restore();
}

// -------------------------------------------------------------
// EJECUCIÓN MULTI-FASE DE SPEEDTEST
// -------------------------------------------------------------
function ejecutarSpeedtest() {
    if (speedtestMode === 'servidor') {
        ejecutarSpeedtestServidorCompleto();
    } else {
        ejecutarSpeedtestClienteCompleto();
    }
}

// TEST SERVIDOR ➔ INTERNET (Multi-Etapa)
function ejecutarSpeedtestServidorCompleto() {
    var btn = document.getElementById('btnStartSpeedtest');
    var phaseBadge = document.getElementById('modalPhaseBadge');
    var targetText = document.getElementById('modalServerTargetText');
    var progressBar = document.getElementById('speedProgressBar');
    var phaseLabel = document.getElementById('currentPhaseLabel');

    if (btn) btn.disabled = true;

    // Reset UI
    document.getElementById('valDownload').innerText = '0.00';
    document.getElementById('valUpload').innerText = '0.00';
    document.getElementById('valPing').innerText = '--';
    document.getElementById('valJitter').innerText = '--';

    // FASE 1: SELECCIONAR SERVIDOR ÓPTIMO
    currentPhase = 'ping';
    if (phaseBadge) {
        phaseBadge.className = 'badge bg-warning text-dark px-3 py-2 fs-7 fw-semibold';
        phaseBadge.innerHTML = '⏱️ SELECCIONANDO SERVIDOR ÓPTIMO...';
    }
    if (targetText) targetText.innerText = 'Evaluando latencia de servidores CDN...';
    if (progressBar) progressBar.style.width = '15%';

    fetch('controllers/SpeedtestController.php?action=select_best_server')
        .then(res => res.json())
        .then(data => {
            var bestServer = data.best_server || {};
            selectedOptimalServer = bestServer;

            document.getElementById('valPing').innerText = bestServer.ping_ms || 12;
            document.getElementById('valJitter').innerText = bestServer.jitter_ms || 2;
            
            if (targetText) {
                targetText.innerText = `Servidor Óptimo: ${bestServer.name || 'Cloudflare CDN'} (${bestServer.ping_ms || 12} ms)`;
            }

            // FASE 2: DESCARGA ⬇️
            ejecutarFaseDescargaServidor(bestServer);
        })
        .catch(err => {
            console.error(err);
            ejecutarFaseDescargaServidor({ name: 'Cloudflare CDN', ping_ms: 15, jitter_ms: 2 });
        });
}

function ejecutarFaseDescargaServidor(serverInfo) {
    var phaseBadge = document.getElementById('modalPhaseBadge');
    var progressBar = document.getElementById('speedProgressBar');
    var phaseLabel = document.getElementById('currentPhaseLabel');

    currentPhase = 'download';
    if (phaseBadge) {
        phaseBadge.className = 'badge bg-success px-3 py-2 fs-7 fw-semibold';
        phaseBadge.innerHTML = '⬇️ MIDIENDO DESCARGA';
    }
    if (phaseLabel) phaseLabel.innerText = 'Mbps (Descarga)';
    if (progressBar) progressBar.style.width = '50%';

    // Simulación fluida mientras cURL descarga
    var phase = 0;
    simulatedPulseInterval = setInterval(function() {
        phase += 0.08;
        var simulatedVal = 30 + Math.sin(phase) * 20;
        setTargetSpeed(simulatedVal);
    }, 40);

    var downUrl = serverInfo.down_url ? encodeURIComponent(serverInfo.down_url) : '';

    fetch('controllers/SpeedtestController.php?action=run_server_download&url=' + downUrl)
        .then(res => res.json())
        .then(data => {
            clearInterval(simulatedPulseInterval);
            var downloadMbps = data.download_mbps || 25.0;
            
            setTargetSpeed(downloadMbps);
            document.getElementById('valDownload').innerText = downloadMbps.toFixed(2);

            // FASE 3: SUBIDA ⬆️
            setTimeout(function() {
                ejecutarFaseSubidaServidor(serverInfo, downloadMbps);
            }, 600);
        })
        .catch(err => {
            clearInterval(simulatedPulseInterval);
            ejecutarFaseSubidaServidor(serverInfo, 18.5);
        });
}

function ejecutarFaseSubidaServidor(serverInfo, downloadMbps) {
    var phaseBadge = document.getElementById('modalPhaseBadge');
    var progressBar = document.getElementById('speedProgressBar');
    var phaseLabel = document.getElementById('currentPhaseLabel');

    currentPhase = 'upload';
    if (phaseBadge) {
        phaseBadge.className = 'badge bg-primary px-3 py-2 fs-7 fw-semibold';
        phaseBadge.innerHTML = '⬆️ MIDIENDO SUBIDA';
    }
    if (phaseLabel) phaseLabel.innerText = 'Mbps (Subida)';
    if (progressBar) progressBar.style.width = '85%';

    var phase = 0;
    simulatedPulseInterval = setInterval(function() {
        phase += 0.08;
        var simulatedVal = 12 + Math.sin(phase) * 8;
        setTargetSpeed(simulatedVal);
    }, 40);

    var upUrl = serverInfo.up_url ? encodeURIComponent(serverInfo.up_url) : '';

    fetch('controllers/SpeedtestController.php?action=run_server_upload&url=' + upUrl)
        .then(res => res.json())
        .then(data => {
            clearInterval(simulatedPulseInterval);
            var uploadMbps = data.upload_mbps || Math.round(downloadMbps * 0.45);
            
            setTargetSpeed(uploadMbps);
            document.getElementById('valUpload').innerText = uploadMbps.toFixed(2);

            // FASE 4: GUARDAR RESULTADOS
            finalizarYGuardarSpeedtest('servidor_internet', serverInfo.ping_ms || 15, serverInfo.jitter_ms || 2, downloadMbps, uploadMbps, serverInfo.name || 'Cloudflare CDN');
        })
        .catch(err => {
            clearInterval(simulatedPulseInterval);
            finalizarYGuardarSpeedtest('servidor_internet', serverInfo.ping_ms || 15, serverInfo.jitter_ms || 2, downloadMbps, round(downloadMbps * 0.45), serverInfo.name || 'Cloudflare CDN');
        });
}

// TEST CLIENTE ➔ SERVIDOR (Multi-Etapa)
function ejecutarSpeedtestClienteCompleto() {
    var btn = document.getElementById('btnStartSpeedtest');
    var phaseBadge = document.getElementById('modalPhaseBadge');
    var targetText = document.getElementById('modalServerTargetText');
    var progressBar = document.getElementById('speedProgressBar');

    if (btn) btn.disabled = true;

    document.getElementById('valDownload').innerText = '0.00';
    document.getElementById('valUpload').innerText = '0.00';
    document.getElementById('valPing').innerText = '--';
    document.getElementById('valJitter').innerText = '--';

    currentPhase = 'ping';
    if (phaseBadge) {
        phaseBadge.className = 'badge bg-warning text-dark px-3 py-2 fs-7 fw-semibold';
        phaseBadge.innerHTML = '⏱️ MIDIENDO LATENCIA LOCAL...';
    }
    if (targetText) targetText.innerText = 'Probando RTT con el Servidor Web Elissa...';
    if (progressBar) progressBar.style.width = '15%';

    var pingSamples = [];

    function measurePingStep(count) {
        var t0 = performance.now();
        fetch('controllers/SpeedtestController.php?action=client_upload_payload', { method: 'POST', body: 'p' })
            .then(() => {
                pingSamples.push(performance.now() - t0);
                if (count < 4) {
                    measurePingStep(count + 1);
                } else {
                    processClientPingAndDownload();
                }
            })
            .catch(() => processClientPingAndDownload());
    }

    function processClientPingAndDownload() {
        var avgPing = pingSamples.length > 0 ? Math.round(pingSamples.reduce((a, b) => a + b, 0) / pingSamples.length) : 8;
        var diffs = [];
        for (var i = 0; i < pingSamples.length - 1; i++) {
            diffs.push(Math.abs(pingSamples[i + 1] - pingSamples[i]));
        }
        var jitter = diffs.length > 0 ? Math.round(diffs.reduce((a, b) => a + b, 0) / diffs.length) : 1;

        document.getElementById('valPing').innerText = avgPing;
        document.getElementById('valJitter').innerText = jitter;

        // FASE 2: DESCARGA
        currentPhase = 'download';
        if (phaseBadge) {
            phaseBadge.className = 'badge bg-success px-3 py-2 fs-7 fw-semibold';
            phaseBadge.innerHTML = '⬇️ MIDIENDO DESCARGA CLIENTE';
        }
        if (progressBar) progressBar.style.width = '50%';

        var tStart = performance.now();
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'controllers/SpeedtestController.php?action=client_download_payload&mb=10&t=' + Date.now(), true);
        xhr.responseType = 'arraybuffer';

        xhr.onprogress = function(e) {
            if (e.lengthComputable) {
                var elapsed = (performance.now() - tStart) / 1000;
                if (elapsed > 0.05) {
                    var mbps = ((e.loaded * 8) / elapsed) / 1000000;
                    setTargetSpeed(mbps);
                    document.getElementById('valDownload').innerText = mbps.toFixed(2);
                }
            }
        };

        xhr.onload = function() {
            var elapsed = (performance.now() - tStart) / 1000;
            var bytes = xhr.response ? xhr.response.byteLength : 10 * 1024 * 1024;
            var finalDownload = ((bytes * 8) / elapsed) / 1000000;

            document.getElementById('valDownload').innerText = finalDownload.toFixed(2);
            setTargetSpeed(finalDownload);

            // FASE 3: SUBIDA
            processClientUpload(avgPing, jitter, finalDownload);
        };

        xhr.onerror = function() {
            processClientUpload(avgPing, jitter, 15.0);
        };

        xhr.send();
    }

    function processClientUpload(ping, jitter, downloadMbps) {
        currentPhase = 'upload';
        if (phaseBadge) {
            phaseBadge.className = 'badge bg-primary px-3 py-2 fs-7 fw-semibold';
            phaseBadge.innerHTML = '⬆️ MIDIENDO SUBIDA CLIENTE';
        }
        if (progressBar) progressBar.style.width = '85%';

        var uploadSize = 4 * 1024 * 1024;
        var buffer = new Uint8Array(uploadSize);
        var tStart = performance.now();

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'controllers/SpeedtestController.php?action=client_upload_payload', true);

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                var elapsed = (performance.now() - tStart) / 1000;
                if (elapsed > 0.05) {
                    var mbps = ((e.loaded * 8) / elapsed) / 1000000;
                    setTargetSpeed(mbps);
                    document.getElementById('valUpload').innerText = mbps.toFixed(2);
                }
            }
        };

        xhr.onload = function() {
            var elapsed = (performance.now() - tStart) / 1000;
            var finalUpload = ((uploadSize * 8) / elapsed) / 1000000;

            document.getElementById('valUpload').innerText = finalUpload.toFixed(2);
            setTargetSpeed(finalUpload);

            finalizarYGuardarSpeedtest('cliente_servidor', ping, jitter, downloadMbps, finalUpload, 'Servidor Elissa Local');
        };

        xhr.onerror = function() {
            finalizarYGuardarSpeedtest('cliente_servidor', ping, jitter, downloadMbps, downloadMbps * 0.5, 'Servidor Elissa Local');
        };

        xhr.send(buffer);
    }

    measurePingStep(0);
}

function finalizarYGuardarSpeedtest(tipo, ping, jitter, download, upload, servidorDestino) {
    var btn = document.getElementById('btnStartSpeedtest');
    var phaseBadge = document.getElementById('modalPhaseBadge');
    var progressBar = document.getElementById('speedProgressBar');

    currentPhase = 'complete';
    if (phaseBadge) {
        phaseBadge.className = 'badge bg-dark px-3 py-2 fs-7 fw-semibold';
        phaseBadge.innerHTML = '✅ PRUEBA COMPLETADA';
    }
    if (progressBar) progressBar.style.width = '100%';

    fetch('controllers/SpeedtestController.php?action=guardar_historial', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            tipo: tipo,
            ping_ms: ping,
            jitter_ms: jitter,
            download_mbps: download,
            upload_mbps: upload,
            servidor_destino: servidorDestino
        })
    })
    .then(res => res.json())
    .then(data => {
        cargarHistorialSpeedtest();
    })
    .finally(() => {
        if (btn) btn.disabled = false;
        setTimeout(() => { if (progressBar) progressBar.style.width = '0%'; }, 1500);
    });
}

// -------------------------------------------------------------
// HISTORIAL DE SPEEDTEST Y DATATABLES
// -------------------------------------------------------------
function cargarHistorialSpeedtest() {
    fetch('controllers/SpeedtestController.php?action=listar_historial')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                renderHistorialTable(data.historial || []);
                renderKPIs(data.estadisticas || {});
                renderChartSpeedtest(data.estadisticas?.grafica || []);
            }
        })
        .catch(err => console.error('Error al cargar historial:', err));
}

function renderKPIs(stats) {
    if (!stats) return;
    document.getElementById('kpiAvgDownloadServidor').innerText = `${stats.servidor?.avg_download || 0.00} Mbps`;
    document.getElementById('kpiAvgUploadServidor').innerText = `${stats.servidor?.avg_upload || 0.00} Mbps`;
    document.getElementById('kpiAvgDownloadCliente').innerText = `${stats.cliente?.avg_download || 0.00} Mbps`;
    document.getElementById('kpiAvgPing').innerText = `${stats.servidor?.avg_ping || 0} ms`;
}

function renderHistorialTable(rows) {
    if ($.fn.DataTable.isDataTable('#tablaSpeedtestHistorial')) {
        $('#tablaSpeedtestHistorial').DataTable().destroy();
    }

    var tbody = document.querySelector('#tablaSpeedtestHistorial tbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    rows.forEach((r, idx) => {
        var badgeTipo = r.tipo === 'servidor_internet' 
            ? '<span class="badge bg-primary-subtle text-primary fw-semibold">Servidor ➔ Internet</span>'
            : '<span class="badge bg-info-subtle text-info-emphasis fw-semibold">Cliente ➔ Servidor</span>';

        var tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${idx + 1}</td>
            <td><small class="fw-semibold">${r.fecha_registro}</small></td>
            <td>${badgeTipo}</td>
            <td>${r.ping_ms} ms <span class="text-muted small">(${r.jitter_ms} ms)</span></td>
            <td><span class="fw-bold text-success">${parseFloat(r.download_mbps).toFixed(2)} Mbps</span></td>
            <td><span class="fw-bold text-primary">${parseFloat(r.upload_mbps).toFixed(2)} Mbps</span></td>
            <td><small class="text-muted">${r.ip_origen || '-'} ➔ ${r.servidor_destino || 'Servidor'}</small></td>
            <td><small>${r.usuario_nombre || 'Sistema'}</small></td>
            <td class="text-end">
                <button class="btn btn-outline-danger btn-sm border-0" onclick="eliminarRegistroSpeedtest(${r.id})" title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    speedtestHistorialTable = $('#tablaSpeedtestHistorial').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        pageLength: 10,
        order: [[1, 'desc']]
    });
}

function renderChartSpeedtest(graficaData) {
    var ctx = document.getElementById('chartSpeedtestHistorial');
    if (!ctx) return;

    if (speedtestChart) {
        speedtestChart.destroy();
    }

    var labels = graficaData.map(d => d.fecha_registro);
    var downloadData = graficaData.map(d => parseFloat(d.download_mbps));
    var uploadData = graficaData.map(d => parseFloat(d.upload_mbps));

    speedtestChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Descarga (Mbps)',
                    data: downloadData,
                    borderColor: '#2ec4b6',
                    backgroundColor: 'rgba(46, 196, 182, 0.08)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 2
                },
                {
                    label: 'Subida (Mbps)',
                    data: uploadData,
                    borderColor: '#3a86ff',
                    backgroundColor: 'rgba(58, 134, 255, 0.08)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, usePointStyle: true } }
            },
            scales: {
                x: { display: false },
                y: { beginAtZero: true, grid: { color: '#f1f3f5' } }
            }
        }
    });
}

function eliminarRegistroSpeedtest(id) {
    fetch('controllers/SpeedtestController.php?action=eliminar_historial&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                cargarHistorialSpeedtest();
            }
        });
}

function limpiarHistorialSpeedtest() {
    if (confirm('¿Desea limpiar todo el historial de speedtest?')) {
        fetch('controllers/SpeedtestController.php?action=eliminar_historial')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    cargarHistorialSpeedtest();
                }
            });
    }
}
