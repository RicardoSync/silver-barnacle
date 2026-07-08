<?php
class HistorialCaidaDTO {
    private $id;
    private $nodo_id;
    private $tipo_nodo;
    private $nombre_nodo;
    private $fecha_caida;
    private $fecha_recuperacion;
    private $duracion_minutos;
    private $estado;
    private $notificado_3m;
    private $notificado_30m;

    public function getId() { return $this->id; }
    public function getNodoId() { return $this->nodo_id; }
    public function getTipoNodo() { return $this->tipo_nodo; }
    public function getNombreNodo() { return $this->nombre_nodo; }
    public function getFechaCaida() { return $this->fecha_caida; }
    public function getFechaRecuperacion() { return $this->fecha_recuperacion; }
    public function getDuracionMinutos() { return $this->duracion_minutos; }
    public function getEstado() { return $this->estado; }
    public function getNotificado3m() { return $this->notificado_3m; }
    public function getNotificado30m() { return $this->notificado_30m; }

    public function setId($id) { $this->id = $id; }
    public function setNodoId($nodo_id) { $this->nodo_id = $nodo_id; }
    public function setTipoNodo($tipo_nodo) { $this->tipo_nodo = $tipo_nodo; }
    public function setNombreNodo($nombre_nodo) { $this->nombre_nodo = $nombre_nodo; }
    public function setFechaCaida($fecha_caida) { $this->fecha_caida = $fecha_caida; }
    public function setFechaRecuperacion($fecha_recuperacion) { $this->fecha_recuperacion = $fecha_recuperacion; }
    public function setDuracionMinutos($duracion_minutos) { $this->duracion_minutos = $duracion_minutos; }
    public function setEstado($estado) { $this->estado = $estado; }
    public function setNotificado3m($notificado_3m) { $this->notificado_3m = $notificado_3m; }
    public function setNotificado30m($notificado_30m) { $this->notificado_30m = $notificado_30m; }
}
?>
