<?php
class SpeedtestDTO {
    private $id;
    private $tipo;
    private $ping_ms;
    private $jitter_ms;
    private $download_mbps;
    private $upload_mbps;
    private $ip_origen;
    private $servidor_destino;
    private $usuario_nombre;
    private $fecha_registro;

    public function getId() { return $this->id; }
    public function getTipo() { return $this->tipo; }
    public function getPingMs() { return $this->ping_ms; }
    public function getJitterMs() { return $this->jitter_ms; }
    public function getDownloadMbps() { return $this->download_mbps; }
    public function getUploadMbps() { return $this->upload_mbps; }
    public function getIpOrigen() { return $this->ip_origen; }
    public function getServidorDestino() { return $this->servidor_destino; }
    public function getUsuarioNombre() { return $this->usuario_nombre; }
    public function getFechaRegistro() { return $this->fecha_registro; }

    public function setId($id) { $this->id = $id; }
    public function setTipo($tipo) { $this->tipo = $tipo; }
    public function setPingMs($ping_ms) { $this->ping_ms = $ping_ms; }
    public function setJitterMs($jitter_ms) { $this->jitter_ms = $jitter_ms; }
    public function setDownloadMbps($download_mbps) { $this->download_mbps = $download_mbps; }
    public function setUploadMbps($upload_mbps) { $this->upload_mbps = $upload_mbps; }
    public function setIpOrigen($ip_origen) { $this->ip_origen = $ip_origen; }
    public function setServidorDestino($servidor_destino) { $this->servidor_destino = $servidor_destino; }
    public function setUsuarioNombre($usuario_nombre) { $this->usuario_nombre = $usuario_nombre; }
    public function setFechaRegistro($fecha_registro) { $this->fecha_registro = $fecha_registro; }
}
?>
