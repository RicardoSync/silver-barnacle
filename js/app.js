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
            overlay.classList.toggle('active');
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

        let url = 'views/' + viewName + '.php';
        if (params) {
            const queryParams = new URLSearchParams(params).toString();
            url += '?' + queryParams;
        }

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error de red');
                }
                return response.text();
            })
            .then(html => {
                mainContainer.innerHTML = html;
                initPlugins(viewName);
                hideLoader();
            })
            .catch(error => {
                mainContainer.innerHTML = '<div class="alert alert-danger">Error al cargar la vista solicitada.</div>';
                console.error('Error:', error);
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

    // Cargar vista inicial
    loadView('dashboard');
});
