<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elissa</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <div id="loader-wrapper">
        <div class="loader"></div>
    </div>
   
    <div id="overlay" class="sidebar-overlay"></div>

    <!-- Overlay de Alerta Crítica (Pantalla Roja) - Desactivado por solicitud de usuario -->
    <!--
    <div id="critical-alert-overlay">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <h1 id="critical-alert-title">¡ALERTA CRÍTICA DE RED!</h1>
        <p id="critical-alert-msg" class="fs-5 text-center px-3 mb-3 text-white-50">Equipos que han superado más de 1 minuto sin responder:</p>
        <div id="critical-alert-box" style="max-height: 40vh; width: 90%; max-width: 650px; overflow-y: auto; background: rgba(0, 0, 0, 0.35); border-radius: 12px; padding: 12px;" class="mb-4 shadow border border-white-50">
            <div id="critical-alert-list"></div>
        </div>
        <button type="button" class="btn btn-light btn-lg fw-bold shadow text-danger px-4 py-2" onclick="silenciarAlertaVisual()">
            <i class="bi bi-bell-slash me-2"></i> Silenciar / Entendido
        </button>
    </div>
    -->

    <div class="main-wrapper">
        
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header text-center">
                <div class="sidebar-logo-wrapper">
                    <img src="assets/img/logo.png" alt="Elissa Logo" class="sidebar-logo-img">
                </div>
                <h4 class="mb-0 text-white fw-bold" style="letter-spacing: 0.5px;">Elissa</h4>
                <small class="text-white-50 mt-1 d-block text-truncate px-2" title="<?php echo htmlspecialchars($_SESSION['user_rol']); ?>">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_nombre']); ?>
                </small>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <small class="text-uppercase fw-bold px-3 pt-3 pb-1 d-block text-muted" style="font-size: 10px; letter-spacing: 1px; color: #95a5a6 !important;">Navegación</small>
                </li>
                <li>
                    <a href="#" data-view="dashboard" class="active">
                        <i class="bi bi-speedometer2 me-2"></i> Inicio
                    </a>
                </li>

                <li>
                    <small class="text-uppercase fw-bold px-3 pt-3 pb-1 d-block text-muted" style="font-size: 10px; letter-spacing: 1px; color: #95a5a6 !important;">Infraestructura</small>
                </li>
                <li>
                    <a href="#" data-view="mikrotiks">
                        <i class="bi bi-router me-2"></i> MikroTiks
                    </a>
                </li>
                <li>
                    <a href="#" data-view="analiticas">
                        <i class="bi bi-bar-chart-fill me-2"></i> Estadísticas
                    </a>
                </li>
                <li>
                    <a href="#" data-view="equipos/lista">
                        <i class="bi bi-hdd-network me-2"></i> Equipos
                    </a>
                </li>
                <li>
                    <a href="#" data-view="recursos">
                        <i class="bi bi-graph-up me-2"></i> Recursos
                    </a>
                </li>
                <li>
                    <a href="#" data-view="historial_caidas">
                        <i class="bi bi-activity me-2"></i> Monitor de Caídas
                    </a>
                </li>
                <li>
                    <a href="#" data-view="topologia">
                        <i class="bi bi-diagram-3-fill me-2"></i> Topología
                    </a>
                </li>
                <li>
                    <a href="#" data-view="servicios">
                        <i class="bi bi-globe me-2"></i> DNS y Servicios
                    </a>
                </li>
                <li>
                    <a href="#" data-view="ping_multi">
                        <i class="bi bi-grid-3x3-gap-fill me-2"></i> NOC Multigráfica
                    </a>
                </li>
                <li>
                    <a href="#" data-view="traceroute">
                        <i class="bi bi-signpost-split me-2"></i> Traceroute
                    </a>
                </li>
                <li>
                    <a href="#" data-view="speedtest">
                        <i class="bi bi-speedometer me-2"></i> Speedtest (Velocidad)
                    </a>
                </li>

                <li>
                    <small class="text-uppercase fw-bold px-3 pt-3 pb-1 d-block text-muted" style="font-size: 10px; letter-spacing: 1px; color: #95a5a6 !important;">Administración</small>
                </li>
                <li>
                    <a href="#" data-view="usuarios">
                        <i class="bi bi-people-fill me-2"></i> Usuarios
                    </a>
                </li>
                <li>
                    <a href="#" data-view="whatsapp_config">
                        <i class="bi bi-whatsapp me-2"></i> WhatsApp (WAHA)
                    </a>
                </li>
                <li>
                    <a href="#" data-view="contactos_alerta">
                        <i class="bi bi-telephone-fill me-2"></i> Números de Alerta
                    </a>
                </li>
                <li>
                    <a href="#" data-view="plantillas_alerta">
                        <i class="bi bi-chat-text-fill me-2"></i> Plantillas de Alertas
                    </a>
                </li>

                <li class="mt-4">
                    <hr class="text-white-50 my-2">
                    <a href="logout.php" class="text-danger fw-bold text-decoration-none px-3 py-2 d-block hover-danger">
                        <i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión
                    </a>
                </li>
                <li class="mt-auto pt-5 pb-3 text-center">
                    <small class="text-white-50" style="font-size: 11px;">Desarrollador por Ricardo Escobedo - 2026</small>
                </li>
            </ul>
        </nav>

        <!-- Content Area -->
        <div class="content-area">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-3">
                <div class="container-fluid p-0">
                    <button type="button" id="sidebarCollapse" class="btn btn-outline-secondary me-3">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="fw-bold fs-4 me-auto">Panel de Control</div>
                    
                    <!-- Botón Global de Speedtest -->
                    <button type="button" class="btn btn-outline-primary btn-sm me-3 fw-bold shadow-sm" onclick="abrirModalSpeedtestGlobal()" title="Prueba de Velocidad de Red">
                        <i class="bi bi-speedometer2 me-1"></i> Speedtest
                    </button>

                    <!-- Weather Widget -->
                    <div class="dropdown me-3" id="weatherDropdownWrapper">
                        <a href="#" class="text-secondary d-flex align-items-center text-decoration-none" id="weatherDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="display: none;">
                            <span id="weather-icon-nav" class="me-2 fs-5"></span>
                            <span id="weather-temp-nav" class="fw-bold" style="font-size: 0.9rem;">--°C</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow p-3" aria-labelledby="weatherDropdown" style="width: 320px; border: none; border-radius: 12px;" id="weather-dropdown-menu">
                            <div class="text-center py-3" id="weather-loading">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <span class="ms-2 text-muted small">Detectando clima...</span>
                            </div>
                            <div id="weather-content" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <span class="fw-bold text-dark"><i class="bi bi-cloud-sun me-1"></i> Clima Local</span>
                                    <small class="text-muted" id="weather-location" style="font-size: 0.75rem;">Detectando ubicación...</small>
                                </div>
                                <div class="d-flex align-items-center justify-content-between my-3">
                                    <div class="d-flex align-items-center">
                                        <span id="weather-large-icon" class="fs-1 me-3"></span>
                                        <div>
                                            <h2 class="mb-0 fw-bold" id="weather-temp-detail">--°C</h2>
                                            <small class="text-muted text-capitalize" id="weather-desc">--</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="row text-center border-top pt-2 mt-2 g-2">
                                    <div class="col-6 border-end">
                                        <small class="text-muted d-block" style="font-size: 0.75rem;"><i class="bi bi-wind"></i> Viento</small>
                                        <span class="fw-bold text-dark" id="weather-wind" style="font-size: 0.85rem;">-- km/h</span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block" style="font-size: 0.75rem;"><i class="bi bi-droplet-half"></i> Humedad</small>
                                        <span class="fw-bold text-dark" id="weather-humidity" style="font-size: 0.85rem;">--%</span>
                                    </div>
                                </div>
                                <div class="mt-3 border-top pt-2">
                                    <small class="text-muted fw-bold d-block mb-2" style="font-size: 0.75rem;">Pronóstico Próximas Horas</small>
                                    <div class="d-flex justify-content-between text-center" id="weather-hourly-forecast">
                                        <!-- Horas -->
                                    </div>
                                </div>
                            </div>
                            <div id="weather-error" style="display: none;" class="text-center py-2 text-danger small">
                                <i class="bi bi-geo-alt-fill d-block fs-4 mb-1"></i>
                                <span>Permiso de ubicación denegado o no disponible.</span>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2 w-100" onclick="window.requestWeatherLocation(true)">
                                    Reintentar / Activar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Bell -->
                    <div class="dropdown me-3">
                        <a href="#" class="text-secondary position-relative text-decoration-none" id="bellDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell-fill fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="bell-badge" style="display:none; font-size: 0.6rem;">
                                0
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="bellDropdown" style="width: 320px; max-height: 400px; overflow-y: auto;" id="bell-dropdown-menu">
                            <li><h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                Notificaciones 
                                <span class="badge bg-secondary" style="cursor:pointer" onclick="marcarTodasLeidas()">Marcar todas leídas</span>
                            </h6></li>
                            <div id="bell-items">
                                <li><a class="dropdown-item text-muted text-center py-3" href="#">No hay notificaciones nuevas</a></li>
                            </div>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center fw-bold text-primary" href="#" data-view="alertas" onclick="document.querySelector('[data-view=\\'alertas\\']').click()">Ver Historial Completo</a></li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="container-fluid px-4 pb-4 pt-2" id="main-content">
                <!-- Vistas cargadas dinámicamente -->
            </div>
            
            <div class="container-fluid px-4 pb-3 pt-2 text-end">
                <small class="text-muted fw-bold" style="font-size: 12px;">Desarrollador por Ricardo Escobedo - 2026</small>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="js/app.js?v=<?php echo time(); ?>"></script>
<script src="js/mikrotik.js?v=<?php echo time(); ?>"></script>
<script src="js/detalles.js?v=<?php echo time(); ?>"></script>
<script src="js/dashboard.js?v=<?php echo time(); ?>"></script>
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
