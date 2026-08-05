<?php
class PlantillaAlertaDTO {
    private $id;
    private $nombre;
    private $minutos;
    private $mensaje;
    private $estado;

    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getMinutos() { return $this->minutos; }
    public function getMensaje() { return $this->mensaje; }
    public function getEstado() { return $this->estado; }

    public function setId($id) { $this->id = $id; }
    public function setNombre($nombre) { $this->nombre = $nombre; }
    public function setMinutos($minutos) { $this->minutos = $minutos; }
    public function setMensaje($mensaje) { $this->mensaje = $mensaje; }
    public function setEstado($estado) { $this->estado = $estado; }
}
?>
