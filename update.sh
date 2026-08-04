#!/bin/bash

# Terminar el script inmediatamente si ocurre un error inesperado
set -e

echo "=== ACTUALIZADOR INTELIGENTE DE ELISSA ==="
echo ""

# 1. Preguntar la ruta de instalación con valor por defecto
DEFAULT_PATH="/var/www/html/elissa"
read -p "Ingresa la ruta de instalación [$DEFAULT_PATH]: " TARGET_DIR
TARGET_DIR=${TARGET_DIR:-$DEFAULT_PATH}

# Normalizar la ruta eliminando barras al final
TARGET_DIR=$(echo "$TARGET_DIR" | sed 's/*$//')

# Verificar si el directorio existe
if [ ! -d "$TARGET_DIR" ]; then
    echo "Error: El directorio '$TARGET_DIR' no existe."
    exit 1
fi

echo ""
echo "--> Carpeta seleccionada: $TARGET_DIR"
cd "$TARGET_DIR"

# Evitar el error de "dubious ownership" (permisos de root vs www-data)
git config --global --add safe.directory "$TARGET_DIR" 2>/dev/null || true

# 2. Inicializar Git si no existe
if [ ! -d ".git" ]; then
    echo "--> Inicializando repositorio Git en la carpeta..."
    git init -b main
    git remote add origin https://github.com/RicardoSync/silver-barnacle.git
fi

# 3. Sincronización inteligente (Sin commits ni configuración de usuario)
echo "--> Descargando actualizaciones desde GitHub..."
git fetch origin main

echo "--> Sincronizando archivos forzosamente..."
# Truco: Agregamos al índice para que Git los reconozca y no lance "untracked files error"
git add . 
# Forzamos a que todo sea exactamente igual a la versión de GitHub
git reset --hard origin/main

# 4. Actualizar dependencias de Composer
echo "--> Actualizando dependencias de Composer..."
rm -rf vendor/ composer.lock
COMPOSER_ALLOW_SUPERUSER=1 composer update --no-interaction

# 5. Asegurar la configuración en includes/config.php
CONFIG_FILE="includes/config.php"

if [ ! -f "$CONFIG_FILE" ]; then
    echo "--> Creando $CONFIG_FILE a partir del archivo de ejemplo..."
    if [ -f "includes/config.php.example" ]; then
        cp includes/config.php.example "$CONFIG_FILE"
    fi
fi

if [ -f "$CONFIG_FILE" ]; then
    echo "--> Configurando datos de base de datos..."
    sed -i "s/['\"]user['\"]\s*=>\s*['\"].*['\"]/'user' => 'doblenet'/g" "$CONFIG_FILE" 2>/dev/null || true
    sed -i "s/['\"]pass['\"]\s*=>\s*['\"].*['\"]/'pass' => 'zerocuatro04'/g" "$CONFIG_FILE" 2>/dev/null || true
    sed -i "s/['\"]password['\"]\s*=>\s*['\"].*['\"]/'password' => 'zerocuatro04'/g" "$CONFIG_FILE" 2>/dev/null || true
    sed -i "s/['\"]host['\"]\s*=>\s*['\"].*['\"]/'host' => 'localhost'/g" "$CONFIG_FILE" 2>/dev/null || true
fi

# 6. Ajustar permisos para el servidor web
echo "--> Ajustando permisos..."
chown -R www-data:www-data "$TARGET_DIR"

echo ""
echo "================================================="
echo " ¡Elissa se ha actualizado de forma inteligente! "
echo "================================================="