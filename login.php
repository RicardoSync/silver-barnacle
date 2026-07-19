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
        body {
            background-color: #e9ecef;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .login-box {
            width: 360px;
        }
        .login-card {
            border: 0;
            border-top: 3px solid #3c8dbc;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            background: #fff;
        }
        .login-header {
            text-align: center;
            padding: 30px 20px 20px;
        }
        .login-header h2 {
            font-weight: 300;
            color: #444;
            margin-bottom: 0;
            font-size: 32px;
        }
        .login-header h2 b {
            font-weight: 700;
        }
        .login-body {
            padding: 20px 30px 30px;
        }
        .login-body p {
            color: #666;
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .input-group-text {
            background-color: #fff;
            border-left: 0;
        }
        .form-control {
            border-right: 0;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #d2d6de;
        }
        .form-control:focus + .input-group-text {
            border-color: #d2d6de;
        }
        .btn-primary {
            background-color: #3c8dbc;
            border-color: #367fa9;
        }
        .btn-primary:hover {
            background-color: #367fa9;
        }
        .credits {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>

<div class="login-box">
    <div class="login-header">
        <img src="assets/img/logo.png" alt="Elissa Logo" style="max-height: 80px; margin-bottom: 15px;">
        <h2><b>Elissa</b> Monitor</h2>
    </div>
    <div class="login-card card">
        <div class="login-body">
            <p>Inicia sesión para continuar</p>
            <form id="loginForm">
                <div class="input-group mb-3">
                    <input type="email" class="form-control" name="correo" placeholder="Correo Electrónico" required>
                    <span class="input-group-text"><i class="bi bi-envelope text-muted"></i></span>
                </div>
                <div class="input-group mb-4">
                    <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
                    <span class="input-group-text"><i class="bi bi-lock text-muted"></i></span>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-block" id="btn-login">Ingresar</button>
                </div>
            </form>
        </div>
    </div>
    <div class="credits">
        Desarrollador por Ricardo Escobedo - 2026
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
            btn.innerHTML = 'Ingresar';
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
        btn.innerHTML = 'Ingresar';
        Swal.fire('Error', 'No se pudo conectar al servidor.', 'error');
    });
});
</script>

</body>
</html>
