<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!doctype html>
<html lang="es">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Elissa Monitor | Dashboard</title>

    <!-- Theme Init (prevents flash of incorrect theme) -->
    <script>
      (() => {
        'use strict';
        const root = document.documentElement;
        if (root.getAttribute('data-lte-color-mode') === 'off') return;

        const STORAGE_KEY = 'lte-theme';
        let stored = null;
        try { stored = localStorage.getItem(STORAGE_KEY); } catch {}
        const authored = root.getAttribute('data-bs-theme');
        let resolved = 'light';
        if (stored === 'dark' || stored === 'light') {
          resolved = stored;
        } else if (authored === 'dark' || authored === 'light') {
          resolved = authored;
        } else if (globalThis.matchMedia('(prefers-color-scheme: dark)').matches) {
          resolved = 'dark';
        }
        root.setAttribute('data-bs-theme', resolved);
        root.style.colorScheme = resolved;
        if (resolved !== authored) {
          root.setAttribute('data-lte-theme-resolved', '');
        }
      })();
    </script>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="title" content="Elissa Monitor" />
    <meta name="description" content="Elissa Monitoreo de Red" />

    <!-- Fonts -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
      crossorigin="anonymous"
    />
    <!-- OverlayScrollbars -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
      crossorigin="anonymous"
    />
    <!-- Bootstrap Icons -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
      crossorigin="anonymous"
    />

    <!-- AdminLTE 4 CSS -->
    <link rel="stylesheet" href="dist/css/adminlte.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>" />

    <!-- SweetAlert2, Chart.js & ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css" crossorigin="anonymous" />
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js" crossorigin="anonymous"></script>
  </head>
  <body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse bg-body-tertiary">
    <!-- Loader -->
    <div id="loader-wrapper">
        <div class="loader"></div>
    </div>

    <!-- App Wrapper -->
    <div class="app-wrapper">
      <!-- Header -->
      <nav class="app-header navbar navbar-expand bg-body shadow-sm">
        <div class="container-fluid">
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
                <i class="bi bi-list"></i>
              </a>
            </li>
            <li class="nav-item d-none d-md-block">
              <span class="navbar-text fw-bold text-primary fs-5 ms-2">
                <img src="assets/img/logo.png" alt="Logo" style="height: 24px;" class="me-2">
                Elissa Monitor
              </span>
            </li>
          </ul>

          <ul class="navbar-nav ms-auto align-items-center">
            <!-- Weather Dropdown -->
            <li class="nav-item dropdown me-2" id="weatherDropdownWrapper">
              <a href="#" class="nav-link d-flex align-items-center" id="weatherDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="display: none;">
                <span id="weather-icon-nav" class="me-1 fs-5"></span>
                <span id="weather-temp-nav" class="fw-bold" style="font-size: 0.85rem;">--°C</span>
              </a>
              <div class="dropdown-menu dropdown-menu-end shadow p-3" aria-labelledby="weatherDropdown" style="width: 300px; border-radius: 10px;">
                <div class="text-center py-2" id="weather-loading">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                  <span class="ms-2 text-muted small">Cargando clima...</span>
                </div>
                <div id="weather-content" style="display: none;">
                  <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <span class="fw-bold text-dark"><i class="bi bi-cloud-sun me-1"></i> Clima Local</span>
                    <small class="text-muted" id="weather-location" style="font-size: 0.75rem;"></small>
                  </div>
                  <div class="d-flex align-items-center justify-content-between my-2">
                    <div class="d-flex align-items-center">
                      <span id="weather-large-icon" class="fs-1 me-3"></span>
                      <div>
                        <h3 class="mb-0 fw-bold" id="weather-temp-detail">--°C</h3>
                        <small class="text-muted text-capitalize" id="weather-desc">--</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </li>

            <!-- Bell Notifications Dropdown -->
            <li class="nav-item dropdown me-2">
              <a class="nav-link position-relative" data-bs-toggle="dropdown" href="#" aria-label="Notifications">
                <i class="bi bi-bell-fill"></i>
                <span class="navbar-badge badge text-bg-danger" id="bell-badge" style="display:none;">0</span>
              </a>
              <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow" style="width: 320px;" id="bell-dropdown-menu">
                <span class="dropdown-item dropdown-header d-flex justify-content-between align-items-center">
                  Notificaciones
                  <span class="badge bg-secondary" style="cursor:pointer" onclick="marcarTodasLeidas()">Limpiar</span>
                </span>
                <div class="dropdown-divider"></div>
                <div id="bell-items">
                  <a href="#" class="dropdown-item text-muted text-center py-3">Sin notificaciones</a>
                </div>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item dropdown-footer text-center text-primary fw-bold" data-view="alertas" onclick="loadView('alertas')">Ver todas las alertas</a>
              </div>
            </li>

            <!-- Fullscreen Toggle -->
            <li class="nav-item">
              <a class="nav-link" href="#" data-lte-toggle="fullscreen" aria-label="Toggle fullscreen">
                <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none"></i>
              </a>
            </li>

            <!-- Color Mode Toggle -->
            <li class="nav-item dropdown">
              <a class="nav-link" href="#" id="bd-theme" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-sun-fill" data-lte-theme-icon="light"></i>
                <i class="bi bi-moon-fill d-none" data-lte-theme-icon="dark"></i>
                <i class="bi bi-circle-half d-none" data-lte-theme-icon="auto"></i>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bd-theme" style="--bs-dropdown-min-width: 8rem">
                <li>
                  <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light">
                    <i class="bi bi-sun-fill me-2"></i> Claro
                  </button>
                </li>
                <li>
                  <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark">
                    <i class="bi bi-moon-fill me-2"></i> Oscuro
                  </button>
                </li>
                <li>
                  <button type="button" class="dropdown-item d-flex align-items-center active" data-bs-theme-value="auto">
                    <i class="bi bi-circle-half me-2"></i> Auto
                  </button>
                </li>
              </ul>
            </li>

            <!-- User Menu -->
            <li class="nav-item dropdown user-menu">
              <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <img src="assets/img/logo.png" class="user-image rounded-circle shadow" alt="User Image" />
                <span class="d-none d-md-inline fw-semibold"><?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow">
                <li class="user-header text-bg-primary">
                  <img src="assets/img/logo.png" class="rounded-circle shadow" alt="User Image" />
                  <p>
                    <?php echo htmlspecialchars($_SESSION['user_nombre']); ?>
                    <small>Rol: <?php echo htmlspecialchars($_SESSION['user_rol']); ?></small>
                  </p>
                </li>
                <li class="user-footer">
                  <a href="logout.php" class="btn btn-outline-danger float-end">Cerrar Sesión</a>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </nav>

      <!-- Sidebar (Collapsible AdminLTE 4 Sidebar Mini) -->
      <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
          <a href="index.php" class="brand-link">
            <img src="assets/img/logo.png" alt="Elissa Logo" class="brand-image opacity-75 shadow" />
            <span class="brand-text fw-light">Elissa</span>
          </a>
        </div>
        <div class="sidebar-wrapper">
          <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false" id="sidebar-menu-list">
              <li class="nav-header">MONITOREO</li>
              <li class="nav-item">
                <a href="#" class="nav-link active" data-view="dashboard">
                  <i class="nav-icon bi bi-speedometer2"></i>
                  <p>Dashboard</p>
                </a>
              </li>

              <li class="nav-header">INFRAESTRUCTURA</li>
              <li class="nav-item">
                <a href="#" class="nav-link" data-view="mikrotiks">
                  <i class="nav-icon bi bi-router"></i>
                  <p>MikroTiks</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link" data-view="analiticas">
                  <i class="nav-icon bi bi-bar-chart-fill"></i>
                  <p>Estadísticas</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link" data-view="equipos/lista">
                  <i class="nav-icon bi bi-hdd-network"></i>
                  <p>Equipos</p>
                </a>
              </li>

              <li class="nav-item">
                <a href="#" class="nav-link" data-view="historial_caidas">
                  <i class="nav-icon bi bi-activity"></i>
                  <p>Monitor Caídas</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link" data-view="topologia">
                  <i class="nav-icon bi bi-diagram-3-fill"></i>
                  <p>Topología</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link" data-view="servicios">
                  <i class="nav-icon bi bi-globe"></i>
                  <p>DNS y Servicios</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link" data-view="ping_multi">
                  <i class="nav-icon bi bi-grid-3x3-gap-fill"></i>
                  <p>NOC Multigráfica</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link" data-view="traceroute">
                  <i class="nav-icon bi bi-signpost-split"></i>
                  <p>Traceroute</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link" data-view="speedtest">
                  <i class="nav-icon bi bi-speedometer"></i>
                  <p>Speedtest</p>
                </a>
              </li>

              <li class="nav-header">ADMINISTRACIÓN</li>
              <li class="nav-item">
                <a href="#" class="nav-link" data-view="usuarios">
                  <i class="nav-icon bi bi-people-fill"></i>
                  <p>Usuarios</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link" data-view="whatsapp_config">
                  <i class="nav-icon bi bi-whatsapp"></i>
                  <p>WhatsApp (WAHA)</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link" data-view="contactos_alerta">
                  <i class="nav-icon bi bi-telephone-fill"></i>
                  <p>Alertas Contactos</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link" data-view="plantillas_alerta">
                  <i class="nav-icon bi bi-chat-text-fill"></i>
                  <p>Plantillas Alerta</p>
                </a>
              </li>

              <li class="nav-item mt-3">
                <a href="logout.php" class="nav-link text-danger">
                  <i class="nav-icon bi bi-box-arrow-left text-danger"></i>
                  <p class="fw-bold">Cerrar Sesión</p>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      </aside>

      <!-- App Main Content Area -->
      <main class="app-main">
        <div class="app-content py-3">
          <div class="container-fluid" id="main-content">
            <!-- Vistas cargadas dinámicamente -->
          </div>
        </div>
      </main>
    </div>

    <!-- Scripts -->
    <script
      src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
      crossorigin="anonymous"
    ></script>
    <script
      src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
      crossorigin="anonymous"
    ></script>
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"
      crossorigin="anonymous"
    ></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="dist/js/adminlte.js"></script>

    <!-- Application Modules -->
    <script src="js/app.js?v=<?php echo time(); ?>"></script>
    <script src="js/dashboard.js?v=<?php echo time(); ?>"></script>
    <script src="js/mikrotik.js?v=<?php echo time(); ?>"></script>
    <script src="js/detalles.js?v=<?php echo time(); ?>"></script>
    <script src="js/recursos.js?v=<?php echo time(); ?>"></script>
    <script src="js/usuarios.js?v=<?php echo time(); ?>"></script>
    <script src="js/alertas.js?v=<?php echo time(); ?>"></script>
    <script src="js/equipos.js?v=<?php echo time(); ?>"></script>
    <script src="js/equipos_detalles.js?v=<?php echo time(); ?>"></script>
    <script src="js/whatsapp_config.js?v=<?php echo time(); ?>"></script>
    <script src="js/contactos_alerta.js?v=<?php echo time(); ?>"></script>
    <script src="js/plantillas_alerta.js?v=<?php echo time(); ?>"></script>
    <script src="js/historial_caidas.js?v=<?php echo time(); ?>"></script>
    <script src="js/analiticas.js?v=<?php echo time(); ?>"></script>
    <script src="js/noc.js?v=<?php echo time(); ?>"></script>
    <script src="js/topologia.js?v=<?php echo time(); ?>"></script>
    <script src="js/servicios.js?v=<?php echo time(); ?>"></script>
    <script src="js/traceroute.js?v=<?php echo time(); ?>"></script>
    <script src="js/ping_multi.js?v=<?php echo time(); ?>"></script>
    <script src="js/speedtest.js?v=<?php echo time(); ?>"></script>
  </body>
</html>
