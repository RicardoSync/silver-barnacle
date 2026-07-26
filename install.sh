#!/usr/bin/env bash

# ==============================================================================
# Script de Instalación Automatizada - Silver Barnacle / Elissa
# Debe ser ejecutado con permisos de superusuario: sudo ./install.sh
# ==============================================================================

# Colores para mensajes de consola
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # Sin color

# Función para imprimir mensajes
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[ÉXITO]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[ADVERTENCIA]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 1. Verificar permisos de superusuario (root)
if [ "$EUID" -ne 0 ]; then
    log_error "Este script debe ser ejecutado con permisos de superusuario."
    echo -e "Por favor, ejecute: ${YELLOW}sudo ./install.sh${NC}"
    exit 1
fi

log_info "Iniciando instalación del sistema..."

# Obtener directorio del script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# 2. Detección del Gestor de Paquetes e Instalación de Dependencias (PHP, MySQL/MariaDB, Apache, Git, Curl, Unzip)
if command -v apt-get &> /dev/null; then
    log_info "Gestor de paquetes 'apt' detectado (Debian/Ubuntu)."
    log_info "Actualizando repositorios del sistema..."
    apt-get update -y

    log_info "Instalando Apache, MariaDB/MySQL, PHP y extensiones necesarias..."
    apt-get install -y \
        apache2 \
        mariadb-server \
        mariadb-client \
        php \
        php-cli \
        php-mysql \
        php-pdo \
        php-curl \
        php-mbstring \
        php-xml \
        php-zip \
        curl \
        git \
        unzip

    log_info "Habilitando mod_rewrite en Apache..."
    a2enmod rewrite &> /dev/null || true

    SERVICE_APACHE="apache2"
    SERVICE_MYSQL="mariadb"

elif command -v dnf &> /dev/null || command -v yum &> /dev/null; then
    PKG_MGR="dnf"
    command -v dnf &> /dev/null || PKG_MGR="yum"
    log_info "Gestor de paquetes '$PKG_MGR' detectado (RHEL/CentOS/Fedora)."

    log_info "Instalando Apache, MariaDB, PHP y extensiones necesarias..."
    $PKG_MGR install -y \
        httpd \
        mariadb-server \
        mariadb \
        php \
        php-cli \
        php-mysqlnd \
        php-pdo \
        php-curl \
        php-mbstring \
        php-xml \
        php-zip \
        curl \
        git \
        unzip

    SERVICE_APACHE="httpd"
    SERVICE_MYSQL="mariadb"
else
    log_error "No se detectó un gestor de paquetes soportado (apt/dnf/yum)."
    exit 1
fi

# 3. Iniciar y habilitar servicios
log_info "Iniciando y habilitando servicios..."
systemctl enable --now $SERVICE_APACHE || systemctl start $SERVICE_APACHE
systemctl enable --now $SERVICE_MYSQL || systemctl start $SERVICE_MYSQL

log_success "Servicios de Apache y MySQL/MariaDB activos."

# 4. Configurar MySQL / MariaDB (Usuario, Contraseña y Base de Datos)
DB_USER="doblenet"
DB_PASS="zerocuatro04"
DB_NAME="elissa"
SQL_FILE="$SCRIPT_DIR/database.sql"

log_info "Configurando base de datos MySQL/MariaDB..."

# Función para ejecutar SQL como root usando socket o sin contraseña
mysql_exec() {
    mysql -u root -e "$1" 2>/dev/null || mysql -e "$1" 2>/dev/null
}

mysql_import() {
    mysql -u root < "$1" 2>/dev/null || mysql < "$1" 2>/dev/null
}

# Crear usuario y otorgar permisos
log_info "Creando usuario '$DB_USER' y configurando accesos..."
MYSQL_SETUP_SQL="
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON *.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
"

mysql_exec "$MYSQL_SETUP_SQL"

if [ $? -eq 0 ]; then
    log_success "Usuario '$DB_USER' y base de datos '$DB_NAME' configurados en MySQL."
else
    log_warning "No se pudo ejecutar la configuración directa de MySQL como root sin contraseña. Intentando alternativas..."
    mysql -u root -p -e "$MYSQL_SETUP_SQL"
fi

# Importar el archivo database.sql
if [ -f "$SQL_FILE" ]; then
    log_info "Importando esquema desde '$SQL_FILE'..."
    mysql_import "$SQL_FILE"
    if [ $? -eq 0 ]; then
        log_success "Base de datos '$DB_NAME' importada correctamente desde database.sql."
    else
        log_warning "Intentando importar database.sql especificando la base de datos..."
        mysql -u root "$DB_NAME" < "$SQL_FILE" 2>/dev/null || mysql "$DB_NAME" < "$SQL_FILE"
    fi
else
    log_warning "No se encontró el archivo $SQL_FILE para importar."
fi

# Ejecutar migraciones secundarias si existen
if [ -f "$SCRIPT_DIR/migrate.php" ]; then
    log_info "Ejecutando script de migración adicional (migrate.php)..."
    php "$SCRIPT_DIR/migrate.php"
    echo ""
fi

# 5. Instalación de Composer e integración en el proyecto
log_info "Verificando instalación de Composer..."
if ! command -v composer &> /dev/null; then
    log_info "Composer no está instalado. Instalando Composer de forma global..."
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://output");')"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
        log_error "Firma del instalador de Composer no coincide. Abortando instalación de Composer."
        rm -f composer-setup.php
        exit 1
    fi

    php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f composer-setup.php
    log_success "Composer instalado exitosamente en /usr/local/bin/composer."
else
    log_success "Composer ya se encuentra instalado."
fi

# 6. Ejecutar composer update en el proyecto
if [ -f "$SCRIPT_DIR/composer.json" ]; then
    log_info "Ejecutando 'composer update' dentro del proyecto..."
    composer update --no-interaction --allow-root --working-dir="$SCRIPT_DIR"
    log_success "Dependencias de Composer actualizadas correctamente."
else
    log_warning "No se encontró composer.json en el directorio del proyecto."
fi

# 7. Resumen Final
echo ""
echo -e "${GREEN}======================================================================${NC}"
echo -e "${GREEN}        ¡INSTALACIÓN Y CONFIGURACIÓN COMPLETADA CON ÉXITO!            ${NC}"
echo -e "${GREEN}======================================================================${NC}"
echo -e " Detalles de la configuración:"
echo -e " - Servidor Web: ${BLUE}$SERVICE_APACHE${NC}"
echo -e " - Servidor de Base de Datos: ${BLUE}$SERVICE_MYSQL${NC}"
echo -e " - Usuario MySQL: ${BLUE}$DB_USER${NC}"
echo -e " - Contraseña MySQL: ${BLUE}$DB_PASS${NC}"
echo -e " - Base de Datos: ${BLUE}$DB_NAME${NC}"
echo -e " - Ruta del Proyecto: ${BLUE}$SCRIPT_DIR${NC}"
echo -e "${GREEN}======================================================================${NC}"
