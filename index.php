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
    <link rel="stylesheet" href="css/style.css?v=2">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
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
    <div class="main-wrapper">
        
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header text-center">
                <!-- <img src="assets/img/logo.png" alt="Logo" style="max-height: 40px; margin-bottom: 10px;"> -->
                <h4 class="mb-0 mt-3">Elissa</h4>
                <small class="text-white-50 mt-1 d-block text-truncate px-2" title="<?php echo htmlspecialchars($_SESSION['user_rol']); ?>">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_nombre']); ?>
                </small>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <small class="text-uppercase fw-bold px-3 pt-3 pb-1 d-block text-muted" style="font-size: 10px; letter-spacing: 1px; color: #95a5a6 !important;">Navegación</small>
                </li>
                <li class="active">
                    <a href="#" data-view="dashboard">
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

                <li class="mt-4">
                    <hr class="text-white-50 my-2">
                    <a href="logout.php" class="text-danger fw-bold text-decoration-none px-3 py-2 d-block hover-danger">
                        <i class="bi bi-box-arrow-left me-2"></i> Cerrar Sesión
                    </a>
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

            <div class="container-fluid p-4" id="main-content">
                <!-- Vistas cargadas dinámicamente -->
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.min.js"></script>
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
<script src="js/historial_caidas.js?v=<?php echo time(); ?>"></script>

</body>
</html>
