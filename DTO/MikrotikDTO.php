<?php
class MikrotikDTO {
    public $id;
    public $nombre;
    public $ip_address;
    public $puerto_api;
    public $usuario;
    public $password;
    public $latitud;
    public $longitud;
    public $estado_actual;

    public function __construct() {}

    // Getters
    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getIpAddress() { return $this->ip_address; }
    public function getPuertoApi() { return $this->puerto_api; }
    public function getUsuario() { return $this->usuario; }
    public function getPassword() { return $this->password; }
    public function getLatitud() { return $this->latitud; }
    public function getLongitud() { return $this->longitud; }
    public function getEstadoActual() { return $this->estado_actual; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setNombre($nombre) { $this->nombre = $nombre; }
    public function setIpAddress($ip_address) { $this->ip_address = $ip_address; }
    public function setPuertoApi($puerto_api) { $this->puerto_api = $puerto_api; }
    public function setUsuario($usuario) { $this->usuario = $usuario; }
    public function setPassword($password) { $this->password = $password; }
    public function setLatitud($latitud) { $this->latitud = $latitud; }
    public function setLongitud($longitud) { $this->longitud = $longitud; }
    public function setEstadoActual($estado_actual) { $this->estado_actual = $estado_actual; }
}
?>
