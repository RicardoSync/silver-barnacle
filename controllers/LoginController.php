<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'login') {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check if currently locked out
    if (isset($_SESSION['lockout_time'])) {
        $time_left = $_SESSION['lockout_time'] - time();
        if ($time_left > 0) {
            echo json_encode(['status' => 'error', 'message' => "Demasiados intentos. Intente nuevamente en $time_left segundos."]);
            exit;
        } else {
            // Lockout period expired
            unset($_SESSION['lockout_time']);
            $_SESSION['login_attempts'] = 0;
        }
    }

    if (empty($correo) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, ingrese correo y contraseña.']);
        exit;
    }

    try {
        $con = (new Conexion())->conectar();
        $stmt = $con->prepare("SELECT id, nombre, correo, password, rol FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nombre'] = $user['nombre'];
            $_SESSION['user_correo'] = $user['correo'];
            $_SESSION['user_rol'] = $user['rol'];
            
            // Reset attempts
            unset($_SESSION['login_attempts']);
            unset($_SESSION['lockout_time']);

            echo json_encode(['status' => 'success']);
        } else {
            // Fail
            if (!isset($_SESSION['login_attempts'])) {
                $_SESSION['login_attempts'] = 0;
            }
            $_SESSION['login_attempts']++;

            if ($_SESSION['login_attempts'] >= 3) {
                $_SESSION['lockout_time'] = time() + 60; // Block for 60 seconds
                echo json_encode(['status' => 'error', 'message' => "Demasiados intentos fallidos. Cuenta bloqueada por 1 minuto."]);
            } else {
                $intentos_restantes = 3 - $_SESSION['login_attempts'];
                echo json_encode(['status' => 'error', 'message' => "Credenciales incorrectas. Te quedan $intentos_restantes intento(s)."]);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error interno del servidor.']);
    }
}
