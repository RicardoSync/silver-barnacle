<?php
class Conexion {
    private $host = "172.16.20.203";
    private $port = 3306;
    private $user = "mango";
    private $pass = "zerocuatro04";
    private $db = "elissa";
    private $conexion;

    public function conectar() {
        try {
            $this->conexion = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset=utf8mb4",
                $this->user,
                $this->pass
            );

            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conexion;

        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
}
?>