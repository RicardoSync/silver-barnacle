<?php
class EquipoDTO {
    private $id;
    private $nombre;
    private $usuario;
    private $password;
    private $ip_address;
    private $comunidad_snmp;
    private $contacto_snmp;
    private $estado;

    // Getters
    public function getId() { return $this->id; }
    public function getNombre() { return $this->nombre; }
    public function getUsuario() { return $this->usuario; }
    public function getPassword() { return $this->password; }
    public function getIpAddress() { return $this->ip_address; }
    public function getComunidadSnmp() { return $this->comunidad_snmp; }
    public function getContactoSnmp() { return $this->contacto_snmp; }
    public function getEstado() { return $this->estado; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setNombre($nombre) { $this->nombre = $nombre; }
    public function setUsuario($usuario) { $this->usuario = $usuario; }
    public function setPassword($password) { $this->password = $password; }
    public function setIpAddress($ip_address) { $this->ip_address = $ip_address; }
    public function setComunidadSnmp($comunidad_snmp) { $this->comunidad_snmp = $comunidad_snmp; }
    public function setContactoSnmp($contacto_snmp) { $this->contacto_snmp = $contacto_snmp; }
    public function setEstado($estado) { $this->estado = $estado; }
}
?>
