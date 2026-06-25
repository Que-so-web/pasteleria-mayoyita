# Los Postres de Mayoyita by C+- (C más o menos)

![Logo Los Postres de Mayoyita](logos/logo_c+-.png)   ![Logo Los Postres de Mayoyita](logos/logo_circulo.png)  




En este repositorio se almacena el trabajo realizado para el proyecto final de la materia de Ingenieria de Software 2026-1 de la universidad de sonora.

Para proteger la integridad del inventario de producción y las credenciales de acceso, ciertos archivos críticos han sido excluidos del control de versiones mediante el archivo `.gitignore`.

---------------------------------------------------------------------------------------------

## Requisitos para Despliegue Local
Para que el proyecto funcione correctamente en tu entorno local o servidor Apache, necesitas crear e inicializar manualmente dos archivos esenciales en la raíz del directorio:

### 1. Archivo de Configuración (`config.php`)
Crea un archivo llamado `config.php` en la raíz del proyecto para definir la contraseña del panel de administración (`r.php`). 

Estructura del archivo:
```php
<?php
// config.php - Credenciales globales del sistema
define('ADMIN_PASSWORD', 'tu_contraseña_secreta_aquí');
?>
```


### 2. Base de Datos Local (/data/postres.db)
Asegúrate de contar con un directorio llamado data/ en la raíz de tu instalación. Dentro de esa carpeta, debes generar un archivo de base de datos SQLite llamado postres.db ejecutando el siguiente esquema de base de datos (DDL):

```SQL
-- 1. Tabla de Categorías de Postres
CREATE TABLE categorias (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL UNIQUE
);

-- 2. Tabla de Productos del Catálogo
CREATE TABLE productos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_categoria INTEGER,
    nombre TEXT NOT NULL,
    precio REAL,
    precio_descuento REAL DEFAULT NULL,
    ruta_de_imagen TEXT,
    fecha_inicio TEXT,
    fecha_fin TEXT, 
    visible INTEGER DEFAULT 1,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id)
);

-- 3. Tabla de Configuración de Identidad y Diseño de Portada
CREATE TABLE configuracion (
    id INTEGER PRIMARY KEY,
    fondo_banner TEXT,
    logo_banner TEXT,
    logo_posicion TEXT DEFAULT 'center',
    logo_visible INTEGER DEFAULT 1,
    color_fondo TEXT DEFAULT '#f7eaf0',
    color_acento TEXT DEFAULT '#f58cd2',
    tipo_portada TEXT DEFAULT 'static'
);

-- 4. Inyección de Configuración Inicial Requerida por el Sistema
INSERT INTO configuracion (id, fondo_banner, logo_banner, logo_posicion, logo_visible, color_fondo, color_acento, tipo_portada) 
VALUES (1, 'index_media/pan_conchitas.jpg', 'logo_circulo.png', 'center', 1, '#f7eaf0', '#f58cd2', 'static');
```


### 3. Permisos en Linux (Servidor Apache)
Dado que el panel administrativo permite cargar imágenes de nuevos productos directamente desde el navegador, es indispensable otorgar permisos de lectura y escritura correctos al usuario del servidor web (www-data o apache) sobre los siguientes elementos del proyecto:

Carpeta de medios públicos: index_media/

Directorio y archivo de datos: data/ y data/postres.db
