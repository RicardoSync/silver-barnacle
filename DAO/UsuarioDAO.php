<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../DTO/Usuario.php';

class UsuarioDAO {
    private $con;

    public function __construct() {
        $this->con = (new Conexion())->conectar();
    }

    public function listar() {
        try {
            $stmt = $this->con->prepare("SELECT id, nombre, correo, rol, created_at FROM usuarios ORDER BY id DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function obtener($id) {
        try {
            $stmt = $this->con->prepare("SELECT id, nombre, correo, rol FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function registrar(Usuario $u) {
        try {
            // Comprobación de correo único
            $stmt = $this->con->prepare("SELECT id FROM usuarios WHERE correo = ?");
            $stmt->execute([$u->getCorreo()]);
            if ($stmt->fetch()) {
                throw new Exception("Este correo esta en uso");
            }

            $stmt = $this->con->prepare("INSERT INTO usuarios (nombre, correo, password, rol) VALUES (?, ?, ?, ?)");
            $hash = password_hash($u->getPassword(), PASSWORD_DEFAULT);
            $stmt->execute([
                $u->getNombre(),
                $u->getCorreo(),
                $hash,
                $u->getRol()
            ]);
            return true;
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function editar(Usuario $u) {
        try {
            // Comprobación de correo único
            $stmt = $this->con->prepare("SELECT id FROM usuarios WHERE correo = ? AND id != ?");
            $stmt->execute([$u->getCorreo(), $u->getId()]);
            if ($stmt->fetch()) {
                throw new Exception("Este correo esta en uso");
            }

            if (!empty($u->getPassword())) {
                $stmt = $this->con->prepare("UPDATE usuarios SET nombre=?, correo=?, password=?, rol=? WHERE id=?");
                $hash = password_hash($u->getPassword(), PASSWORD_DEFAULT);
                $stmt->execute([
                    $u->getNombre(),
                    $u->getCorreo(),
                    $hash,
                    $u->getRol(),
                    $u->getId()
                ]);
            } else {
                $stmt = $this->con->prepare("UPDATE usuarios SET nombre=?, correo=?, rol=? WHERE id=?");
                $stmt->execute([
                    $u->getNombre(),
                    $u->getCorreo(),
                    $u->getRol(),
                    $u->getId()
                ]);
            }
            return true;
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function eliminar($id) {
        try {
            $stmt = $this->con->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            return true;
        } catch(PDOException $e) {
            throw $e;
        }
    }
}
