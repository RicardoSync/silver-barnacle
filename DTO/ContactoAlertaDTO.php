<?php
class ContactoAlertaDTO {
    private $id;
    private $nombre;
    private $telefono;
    private $estado;

    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getTelefono() { return $this->telefono; }
    public function getEstado() { return $this->estado; }

    public function setId($id) { $this->id = $id; }
    public function setNombre($nombre) { $this->nombre = $nombre; }
    public function setTelefono($telefono) { $this->telefono = $telefono; }
    public function setEstado($estado) { $this->estado = $estado; }
}
?>
