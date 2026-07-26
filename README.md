<img width="1920" height="1032" alt="Captura de pantalla 2026-07-25 191415" src="https://github.com/user-attachments/assets/1ec7a23f-7ff6-4820-84a0-7f93f4200e73" />

# 📊 Elissa - Sistema de Monitoreo

Elissa es un sistema de monitoreo fácil de instalar y configurar, diseñado para interactuar con la API de MikroTik y enviar notificaciones.

## 📋 Requisitos Previos

Antes de comenzar, asegúrate de tener instalados los siguientes servicios en tu servidor web:
* **Servidor Web:** Apache
* **Base de datos:** MySQL
* **Lenguaje:** PHP (incluyendo sus librerías estándar)
* **Dependencias:** Composer

---

## 🚀 Instalación paso a paso

### 1. Descargar el proyecto
Puedes clonar el repositorio usando Git (recomendado) o descargar el archivo `.zip`.

```bash
# Clonar usando Git
git clone https://github.com/RicardoSync/silver-barnacle.git
```

### 2. Ubicar los archivos en el servidor web
Crea la carpeta `elissa` en tu directorio web público y mueve los archivos:
```bash
sudo mkdir -p /var/www/html/elissa
# Mueve todo el contenido del proyecto a la carpeta recién creada
sudo mv silver-barnacle/* /var/www/html/elissa/
```

### 3. Instalar dependencias (Composer)
Dirígete a la carpeta del proyecto e instala las dependencias de PHP.

```bash
# Instalar composer en caso de no tenerlo
sudo apt update
sudo apt install composer -y

# Entrar al directorio
cd /var/www/html/elissa

# Limpiar instalaciones previas
rm -rf vendor/ composer.lock

# Instalar dependencias
composer update
```

### 4. Configurar la Base de Datos (MySQL)
Importa el archivo `database.sql` que viene incluido en el proyecto y configura los accesos.

Primero, importa la base de datos (asegúrate de estar en `/var/www/html/elissa`):
```bash
sudo mysql < database.sql
```

Luego, ingresa a la consola de MySQL para crear el usuario y darle permisos:
```bash
sudo mysql
```

Dentro de la consola de MySQL, ejecuta los siguientes comandos:
```sql
-- Crear el usuario elissa con su contraseña
CREATE USER 'elissa'@'localhost' IDENTIFIED BY 'zerocuatro04';

-- Otorgar todos los permisos (Ajusta 'elissa_db' al nombre real de la BD importada si varía, o usa *.* globalmente para todo)
GRANT ALL PRIVILEGES ON *.* TO 'elissa'@'localhost' WITH GRANT OPTION;

-- Guardar los cambios y salir
FLUSH PRIVILEGES;
EXIT;
```

### 5. Requisitos de Red
⚠️ **Importante:** Para que el sistema se comunique correctamente, el **puerto 8728 (API de MikroTik)** debe estar activo y accesible en tu red.

### 6. Acceso al Sistema
Una vez finalizada la instalación, el sistema estará listo. Puedes entrar desde tu navegador a través de cualquiera de estas rutas:
* `http://<tu_ip>/elissa`
* `http://<tu_dominio.com>/elissa`
* `http://<ip_local>/elissa`

---

## 📱 Integración con WhatsApp (WAHA)

Para el sistema de alertas por WhatsApp, es necesario montar un contenedor Docker con la API de WAHA.

### 1. Desplegar el contenedor de Waha
Ejecuta el siguiente comando para levantar el servicio:

```bash
docker run -d --restart=always \
  -p 1008:3000 \
  --name wa-conect_ti \
  -v waha_sessions_conecti:/app/.sessions \
  -e WAHA_API_KEY=1b21acf9ed92445197806ad03bbc97f1 \
  -e WAHA_DASHBOARD_USERNAME=admin \
  -e WAHA_DASHBOARD_PASSWORD=8e66ebe4abb940e0823547323204128e \
  devlikeapro/waha:gows
```

### 2. Configurar la Sesión
1. Ingresa a la interfaz administrativa en tu navegador utilizando tu IP local: `http://<tu_ip_local>:3050` *(o el puerto configurado)*.
2. Inicia sesión con las siguientes credenciales:
   * **Usuario:** `admin`
   * **Contraseña:** `8e66ebe4abb940e0823547323204128e`
3. En el apartado de **API**, coloca el siguiente token de validación: `1b21acf9ed92445197806ad03bbc97f1`
4. Crea una sesión de manera **obligatoria** y asígnale el nombre: **`default`**.
5. Escanea el código QR con la aplicación de WhatsApp de tu teléfono para vincular la cuenta.

### 3. Configuración final en Elissa
Ingresa a tu sistema **Elissa** y ve a la configuración de WhatsApp para llenar los campos correspondientes. 

📹 **Guía visual:** Si tienes dudas de cómo llenar estos campos en Elissa, por favor sigue los pasos de este video tutorial:
[Ver Video de Configuración en YouTube](https://youtu.be/aM-BHu-9pak?si=v4Bt53oFyfsSYIF2)


<img width="1920" height="1032" alt="Captura de pantalla 2026-07-25 191610" src="https://github.com/user-attachments/assets/dcc82921-f9de-4d51-9ca9-5eba043b5572" />
<img width="1920" height="1032" alt="Captura de pantalla 2026-07-25 191549" src="https://github.com/user-attachments/assets/2a452722-d155-43e3-b670-00a0e0c87dfd" />
<img width="1920" height="1032" alt="Captura de pantalla 2026-07-25 191452" src="https://github.com/user-attachments/assets/4e73af27-542f-4aa3-aa94-c758dbb28c33" />
<img width="1920" height="1032" alt="Captura de pantalla 2026-07-25 191415" src="https://github.com/user-attachments/assets/612ecaa7-0e17-4ad6-9299-7628168a4280" />
<img width="1920" height="1032" alt="Captura de pantalla 2026-07-25 175217" src="https://github.com/user-attachments/assets/3ff7371d-aada-4aef-9380-8589e63b5833" />
<img width="1920" height="1032" alt="Captura de pantalla 2026-07-25 175143" src="https://github.com/user-attachments/assets/cee9ec8a-4f88-42eb-8b64-20555459a423" />
<img width="1920" height="1032" alt="Captura de pantalla 2026-07-25 175131" src="https://github.com/user-attachments/assets/0c3294ba-f8b8-47f8-9e0d-789ada0c548b" />
<img width="1920" height="1032" alt="Captura de pantalla 2026-07-25 175100" src="https://github.com/user-attachments/assets/af565855-35a0-4182-b0d2-bf1447676843" />
