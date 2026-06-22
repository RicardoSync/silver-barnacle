<?php
require_once __DIR__ . '/../DAO/UsuarioDAO.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$dao = new UsuarioDAO();

header('Content-Type: application/json');

switch($action) {
    case 'listar':
        try {
            $data = $dao->listar();
            echo json_encode(array("data" => $data));
        } catch (Exception $e) {
            echo json_encode(array("data" => []));
        }
        break;

    case 'obtener':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        try {
            $data = $dao->obtener($id);
            echo json_encode($data);
        } catch (Exception $e) {
            echo json_encode(array("error" => $e->getMessage()));
        }
        break;

    case 'guardar':
        $u = new Usuario();
        $u->setId(isset($_POST['id']) ? intval($_POST['id']) : 0);
        $u->setNombre($_POST['nombre']);
        $u->setCorreo($_POST['correo']);
        $u->setPassword(isset($_POST['password']) ? $_POST['password'] : '');
        $u->setRol($_POST['rol']);

        try {
            if ($u->getId() > 0) {
                $dao->editar($u);
                echo json_encode(array("status" => "success", "message" => "Usuario actualizado correctamente"));
            } else {
                if(empty($u->getPassword())) {
                    throw new Exception("La contraseña es obligatoria para nuevos usuarios");
                }
                $dao->registrar($u);
                echo json_encode(array("status" => "success", "message" => "Usuario registrado correctamente"));
            }
        } catch (Exception $e) {
            echo json_encode(array("status" => "error", "message" => $e->getMessage()));
        }
        break;

    case 'eliminar':
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        try {
            $dao->eliminar($id);
            echo json_encode(array("status" => "success", "message" => "Usuario eliminado"));
        } catch (Exception $e) {
            echo json_encode(array("status" => "error", "message" => $e->getMessage()));
        }
        break;

    default:
        echo json_encode(array("status" => "error", "message" => "Acción no válida"));
        break;
}
