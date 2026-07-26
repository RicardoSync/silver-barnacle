# Sistema de Monitoreo (Elissa)

Sistema de monitoreo para equipos e infraestructura de red, diseñado con integración para la API de MikroTik.

## Requisitos Previos

Antes de comenzar con la instalación, asegúrate de tener instalado y configurado lo siguiente en tu servidor (entorno LAMP recomendado):
- **Servidor Web**: Apache
- **Base de Datos**: MySQL
- **Lenguaje**: PHP y sus librerías correspondientes
- **Red**: El puerto **8728** debe estar activo/abierto en tu red o firewall para permitir la comunicación con la API de MikroTik.

## Instalación

Sigue estos pasos para desplegar el sistema de manera local o en tu servidor web:

### 1. Descargar el Proyecto
Puedes descargar el código en un archivo `.zip` y extraerlo, o clonarlo directamente usando Git:
```bash
git clone https://github.com/RicardoSync/silver-barnacle.git
```

### 2. Preparar el Directorio del Servidor Web
Crea la carpeta para el sistema dentro del directorio público de Apache y mueve los archivos:
```bash
sudo mkdir -p /var/www/html/elissa

# Mueve todo el contenido del proyecto descargado o clonado a la nueva ruta.
# Ejemplo si usaste git clone:
sudo cp -r silver-barnacle/* /var/www/html/elissa/
```

### 3. Instalar Dependencias (Composer)
Es necesario instalar Composer en el servidor y cargar las dependencias de PHP requeridas por el proyecto:
```bash
# Instalar Composer en el sistema
sudo apt install composer -y

# Ingresar a la carpeta del proyecto
cd /var/www/html/elissa

# Limpiar caché o instalaciones previas
rm -rf vendor/ composer.lock

# Instalar y actualizar dependencias
composer update
```

### 4. Configurar la Base de Datos MySQL
Entra a la consola de base de datos con privilegios de superusuario para importar las tablas y crear el acceso:
```bash
sudo mysql
```
Una vez dentro de la consola de MySQL (`mysql>`), ejecuta los siguientes comandos exactos:

```sql
-- Importar la estructura y datos iniciales
SOURCE /var/www/html/elissa/database.sql;

-- Crear el usuario del sistema con su respectiva contraseña
CREATE USER 'elissa'@'localhost' IDENTIFIED BY 'zerocuatro04';

-- Otorgar todos los permisos al usuario creado
GRANT ALL PRIVILEGES ON *.* TO 'elissa'@'localhost';

-- Refrescar los privilegios para aplicar los cambios
FLUSH PRIVILEGES;

-- Salir de la consola de MySQL
EXIT;
```

### 5. Acceso al Sistema
Una vez completados los pasos anteriores, la instalación habrá finalizado. Puedes ingresar al sistema desde cualquier navegador web utilizando cualquiera de las siguientes rutas:
- `http://<ip_del_servidor>/elissa`
- `http://<tu_dominio.com>/elissa`
- `http://localhost/elissa` (si estás de manera local)
