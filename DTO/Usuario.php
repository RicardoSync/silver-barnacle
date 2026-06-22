<?php
class Usuario {
    private $id;
    private $nombre;
    private $correo;
    private $password;
    private $rol;

    public function __construct() {}

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getNombre() { return $this->nombre; }
    public function setNombre($nombre) { $this->nombre = $nombre; }

    public function getCorreo() { return $this->correo; }
    public function setCorreo($correo) { $this->correo = $correo; }

    public function getPassword() { return $this->password; }
    public function setPassword($password) { $this->password = $password; }

    public function getRol() { return $this->rol; }
    public function setRol($rol) { $this->rol = $rol; }
}
