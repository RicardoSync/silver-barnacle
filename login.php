<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elissa WISP Monitor - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            box-sizing: border-box;
        }

        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            overflow-x: hidden;
            background-color: #f4f6f9;
        }

        .login-wrapper {
            display: flex;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
        }

        /* Panel Izquierdo: Formulario (20% del ancho, 100% de alto - Fondo Gris) */
        .login-panel {
            width: 20%;
            min-width: 320px;
            height: 100vh;
            background: #e9ecef;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3rem 2rem;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08);
            border-right: 1px solid #dee2e6;
            z-index: 10;
            position: relative;
        }

        .brand-badge {
            font-size: 1.35rem;
            font-weight: 300;
            color: #333;
        }

        .brand-badge b {
            font-weight: 700;
            color: #3c8dbc;
        }

        .login-form-container {
            width: 100%;
            max-width: 340px;
            margin: 0 auto;
        }

        .form-control {
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border-radius: 8px 0 0 8px;
            border: 1px solid #ced4da;
            background-color: #ffffff;
        }

        .input-group-text {
            background-color: #ffffff;
            border-radius: 0 8px 8px 0;
            border: 1px solid #ced4da;
            border-left: 0;
        }

        .form-control:focus {
            border-color: #3c8dbc;
            box-shadow: 0 0 0 0.25rem rgba(60, 141, 188, 0.15);
            background-color: #ffffff;
        }

        .form-control:focus + .input-group-text {
            border-color: #3c8dbc;
        }

        .btn-primary {
            background-color: #3c8dbc;
            border-color: #367fa9;
            padding: 0.8rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.25s ease;
        }

        .btn-primary:hover {
            background-color: #367fa9;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(54, 127, 169, 0.3) !important;
        }

        .login-footer {
            text-align: center;
            font-size: 12px;
            color: #777;
        }

        /* Panel Derecho: Logotipo (80% del ancho, 100% de alto - Fondo Blanco) */
        .logo-panel {
            flex: 1;
            height: 100vh;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .logo-panel::before {
            content: '';
            position: absolute;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(60, 141, 188, 0.08) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .logo-showcase-container {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            padding: 4rem;
            z-index: 2;
        }

        .main-hero-logo {
            max-width: 70%;
            max-height: 75vh;
            width: auto;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 15px 30px rgba(0, 0, 0, 0.12)) brightness(1.02) contrast(1.04);
            transition: transform 0.4s ease, filter 0.4s ease;
        }

        .main-hero-logo:hover {
            transform: scale(1.02);
            filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.18)) brightness(1.05) contrast(1.06);
        }

        /* Responsivo para pantallas pequeñas */
        @media (max-width: 992px) {
            .login-wrapper {
                flex-direction: column-reverse;
                height: auto;
                min-height: 100vh;
            }
            .login-panel {
                width: 100%;
                min-width: 100%;
                height: auto;
                min-height: 500px;
            }
            .logo-panel {
                width: 100%;
                height: 350px;
            }
            .main-hero-logo {
                max-height: 250px;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <!-- Lado Izquierdo: Formulario (20% ancho, 100% alto) -->
    <div class="login-panel">
        <div class="login-header">
            <div class="brand-badge mb-3">
                <b>Elissa</b> Monitor
            </div>
            <h4 class="fw-bold text-dark mb-1">Iniciar Sesión</h4>
            <p class="text-muted small">Ingresa tus credenciales para continuar</p>
        </div>

        <div class="login-form-container">
            <form id="loginForm">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Correo</label>
                    <div class="input-group">
                        <input type="email" class="form-control" name="correo" placeholder="ejemplo@elissa.com" required>
                        <span class="input-group-text"><i class="bi bi-envelope text-muted"></i></span>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary">Contraseña</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                        <span class="input-group-text"><i class="bi bi-lock text-muted"></i></span>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-block shadow-sm" id="btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Ingresar
                    </button>
                </div>
            </form>
        </div>

        <div class="login-footer">
            <small class="text-muted">Desarrollador por Ricardo Escobedo - 2026 v2</small>
        </div>
    </div>

    <!-- Lado Derecho: Logotipo Proporcional (80% ancho, 100% alto) -->
    <div class="logo-panel">
        <div class="logo-showcase-container">
            <img src="assets/img/fondo.png" alt="Elissa Logo">
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-login');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Verificando...';

    const formData = new FormData(this);
    
    fetch('controllers/LoginController.php?action=login', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.href = 'index.php';
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i> Ingresar';
            Swal.fire({
                icon: 'error',
                title: 'Acceso Denegado',
                text: data.message,
                confirmButtonColor: '#3c8dbc'
            });
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i> Ingresar';
        Swal.fire('Error', 'No se pudo conectar al servidor.', 'error');
    });
});
</script>

</body>
</html>
