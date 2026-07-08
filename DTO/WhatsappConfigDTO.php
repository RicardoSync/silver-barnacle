<?php
class WhatsappConfigDTO {
    private $id;
    private $waha_api_key;
    private $waha_url;
    private $url_sistema;
    private $api_secret;
    private $enlaces_publicos_activos;
    private $activo;

    public function getId() { return $this->id; }
    public function getWahaApiKey() { return $this->waha_api_key; }
    public function getWahaUrl() { return $this->waha_url; }
    public function getUrlSistema() { return $this->url_sistema; }
    public function getApiSecret() { return $this->api_secret; }
    public function getEnlacesPublicosActivos() { return $this->enlaces_publicos_activos; }
    public function getActivo() { return $this->activo; }

    public function setId($id) { $this->id = $id; }
    public function setWahaApiKey($waha_api_key) { $this->waha_api_key = $waha_api_key; }
    public function setWahaUrl($waha_url) { $this->waha_url = $waha_url; }
    public function setUrlSistema($url_sistema) { $this->url_sistema = $url_sistema; }
    public function setApiSecret($api_secret) { $this->api_secret = $api_secret; }
    public function setEnlacesPublicosActivos($enlaces_publicos_activos) { $this->enlaces_publicos_activos = $enlaces_publicos_activos; }
    public function setActivo($activo) { $this->activo = $activo; }
}
?>
