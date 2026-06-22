function initRecursosModule() {
    loadInventarioRecursos();
}

window.refreshRecursos = function() {
    if ($.fn.DataTable.isDataTable('#tablaRecursos')) {
        $('#tablaRecursos').DataTable().destroy();
    }
    loadInventarioRecursos();
}

function loadInventarioRecursos() {
    fetch('controllers/MikrotikController.php?action=api_inventario_recursos')
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            const tbody = document.querySelector('#tablaRecursos tbody');
            tbody.innerHTML = '';
            
            data.data.forEach(n => {
                const cpu = n.cpu_uso !== null ? parseInt(n.cpu_uso) : 0;
                const cpuColor = cpu > 80 ? 'danger' : (cpu > 50 ? 'warning' : 'success');
                
                // RAM
                let ramBar = '<span class="text-muted small">Sin Datos</span>';
                if (n.ram_total && n.ram_libre) {
                    const total = parseInt(n.ram_total);
                    const libre = parseInt(n.ram_libre);
                    const usado = total - libre;
                    const pct = Math.round((usado / total) * 100);
                    const color = pct > 90 ? 'danger' : (pct > 75 ? 'warning' : 'primary');
                    const mbTotal = (total / 1048576).toFixed(1);
                    const mbUsado = (usado / 1048576).toFixed(1);
                    
                    ramBar = `
                        <div class="d-flex justify-content-between small text-muted mb-1" style="font-size: 11px;">
                            <span>${mbUsado} MB</span>
                            <span>${mbTotal} MB</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-${color}" role="progressbar" style="width: ${pct}%" title="${pct}%"></div>
                        </div>
                    `;
                }

                // Disco
                let diskBar = '<span class="text-muted small">Sin Datos</span>';
                if (n.disco_total && n.disco_libre) {
                    const total = parseInt(n.disco_total);
                    const libre = parseInt(n.disco_libre);
                    const usado = total - libre;
                    const pct = Math.round((usado / total) * 100);
                    const color = pct > 90 ? 'danger' : (pct > 75 ? 'warning' : 'info');
                    const mbTotal = (total / 1048576).toFixed(1);
                    const mbUsado = (usado / 1048576).toFixed(1);
                    
                    diskBar = `
                        <div class="d-flex justify-content-between small text-muted mb-1" style="font-size: 11px;">
                            <span>${mbUsado} MB</span>
                            <span>${mbTotal} MB</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-${color}" role="progressbar" style="width: ${pct}%" title="${pct}%"></div>
                        </div>
                    `;
                }

                tbody.innerHTML += `
                    <tr>
                        <td class="fw-bold text-primary" style="cursor:pointer;" onclick="loadView('mikrotik/detalles', {id: ${n.id}})">${n.nombre}</td>
                        <td><i class="bi bi-hdd-network text-muted"></i> ${n.ip_address}</td>
                        <td><span class="badge bg-secondary">${n.version_ros || '--'}</span></td>
                        <td class="text-muted" style="font-family: monospace;">${n.uptime || '--'}</td>
                        <td><span class="badge bg-${cpuColor} fs-6">${cpu}%</span></td>
                        <td style="width: 20%;">${ramBar}</td>
                        <td style="width: 20%;">${diskBar}</td>
                    </tr>
                `;
            });
            
            $('#tablaRecursos').DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                order: [[0, 'asc']],
                pageLength: 25
            });
        }
    }).catch(e => console.error("Error al cargar Inventario:", e));
}
