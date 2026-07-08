CREATE DATABASE IF NOT EXISTS elissa;

USE elissa;

-- 1. TABLA DE USUARIOS (Acceso al Panel Web)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol VARCHAR(20) DEFAULT 'tecnico', -- administrador, tecnico, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_correo (correo)
) ENGINE=InnoDB;

-- 2. TABLA DE MIKROTIKS (Credenciales y ubicación)
CREATE TABLE IF NOT EXISTS mikrotiks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,            -- Nombre identificativo (ej. Nodo Central)
    ip_address VARCHAR(45) NOT NULL,         -- Soporta IPv4 e IPv6
    puerto_api INT DEFAULT 8728,             -- Puerto por defecto de MikroTik API
    usuario VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,          -- Guardado de forma segura o encriptado reversatible si es necesario
    latitud DECIMAL(10, 8) NULL,             -- Coordenadas geográficas
    longitud DECIMAL(11, 8) NULL,
    estado_actual BOOLEAN DEFAULT 1,         -- 1 = Activo/Monitorear, 0 = Inactivo
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. TABLA HISTÓRICA DE RECURSOS (Alimentada por el CRON)
CREATE TABLE IF NOT EXISTS historico_recursos (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    mikrotik_id INT NOT NULL,
    cpu_uso INT NOT NULL,                    -- Porcentaje de uso (0 a 100)
    ram_total BIGINT NOT NULL,               -- En bytes
    ram_libre BIGINT NOT NULL,               -- En bytes
    disco_total BIGINT NOT NULL,             -- En bytes
    disco_libre BIGINT NOT NULL,             -- En bytes
    uptime VARCHAR(50) NULL,                 -- Tiempo activo (ej. 2w4d12:05:01)
    version_ros VARCHAR(20) NULL,            -- Versión de RouterOS detectada
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Llave foránea vinculada a la tabla de mikrotiks
    FOREIGN KEY (mikrotik_id) REFERENCES mikrotiks(id) ON DELETE CASCADE,
    
    -- Índices para acelerar gráficas y reportes por fecha/dispositivo
    INDEX idx_mikrotik_fecha (mikrotik_id, fecha_registro)
) ENGINE=InnoDB;

-- 4. TABLA HISTÓRICA DE PINGS
CREATE TABLE IF NOT EXISTS historico_pings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    mikrotik_id INT NOT NULL,
    tipo ENUM('google', 'servidor') NOT NULL, -- Destino del ping
    ms INT NOT NULL,                          -- Tiempo en milisegundos (0 si falla)
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (mikrotik_id) REFERENCES mikrotiks(id) ON DELETE CASCADE,
    INDEX idx_ping_fecha (mikrotik_id, tipo, fecha_registro)
) ENGINE=InnoDB;

-- 5. TABLA HISTÓRICA DE TRÁFICO DE INTERFACES
CREATE TABLE IF NOT EXISTS historico_trafico (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    mikrotik_id INT NOT NULL,
    interface VARCHAR(100) NOT NULL,
    rx_bits BIGINT NOT NULL DEFAULT 0,
    tx_bits BIGINT NOT NULL DEFAULT 0,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (mikrotik_id) REFERENCES mikrotiks(id) ON DELETE CASCADE,
    INDEX idx_trafico_fecha (mikrotik_id, interface, fecha_registro)
) ENGINE=InnoDB;

-- 6. TABLA DE ALERTAS Y NOTIFICACIONES
CREATE TABLE IF NOT EXISTS alertas (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    mikrotik_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,              -- offline, cpu, latencia, log_mikrotik
    mensaje TEXT NOT NULL,
    estado ENUM('no_leido', 'leido', 'resuelto') DEFAULT 'no_leido',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mikrotik_id) REFERENCES mikrotiks(id) ON DELETE CASCADE,
    INDEX idx_estado (estado),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB;

-- 7. DATOS POR DEFECTO
INSERT IGNORE INTO usuarios (nombre, correo, password, rol)  VALUES ('Administrador', 'admin@elissa.com', '$2y$12$2XRHLOwaiaz6LrRCTqk70OZTn84ME1xJwsz1u35VqCKh.o01RSDtW', 'administrador');

-- 8. TABLA DE EQUIPOS (Equipos generales y SNMP)
CREATE TABLE IF NOT EXISTS equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NULL,
    password VARCHAR(255) NULL,
    ip_address VARCHAR(45) NOT NULL,
    comunidad_snmp VARCHAR(100) NULL,
    contacto_snmp VARCHAR(100) NULL,
    estado BOOLEAN DEFAULT 1,         -- 1 = Activo, 0 = Eliminado (Borrado Lógico)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB; 

-- 9. TABLA HISTÓRICA DE PINGS DE EQUIPOS
CREATE TABLE IF NOT EXISTS historico_pings_equipos (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT NOT NULL,
    ms INT NOT NULL,                          -- Tiempo en milisegundos (0 si falla)
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE,
    INDEX idx_equipo_ping_fecha (equipo_id, fecha_registro)
) ENGINE=InnoDB;

-- 10. TABLA DE NÚMEROS DE ALERTA
CREATE TABLE IF NOT EXISTS contactos_alerta (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    telefono VARCHAR(50) NOT NULL,
    estado TINYINT(1) DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- 11. CONFIGURACIÓN DE WHATSAPP (WAHA API)
CREATE TABLE IF NOT EXISTS whatsapp_config (
    id INT(11) NOT NULL AUTO_INCREMENT,
    waha_api_key VARCHAR(255) NOT NULL COMMENT 'Tu API Key',
    waha_url VARCHAR(255) NOT NULL DEFAULT 'http://localhost:3000/api/sendText',
    url_sistema VARCHAR(255) NOT NULL COMMENT 'URL publica para PDFs',
    api_secret VARCHAR(100) NOT NULL COMMENT 'Token para descargar PDFs sin login',
    enlaces_publicos_activos TINYINT(1) DEFAULT 1,
    activo TINYINT(1) DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- Insertar el registro único de configuración de WhatsApp si no existe
INSERT IGNORE INTO whatsapp_config (id, waha_api_key, waha_url, url_sistema, api_secret, activo) 
VALUES (1, '', 'http://localhost:3000/api/sendText', 'http://localhost/MiWISPro', 'WISP_SEC_2026', 1);