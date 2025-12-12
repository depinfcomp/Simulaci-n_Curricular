# Sistema de Convalidaciones Curriculares

Sistema web integral de convalidaciones curriculares que automatice el mapeo, análisis y gestión de equivalencias entre mallas curriculares externas o nuevas y la malla base del programa de Administración de Sistemas Informáticos, con el fin de optimizar los tiempos de procesamiento y garantizar la consistencia en los criterios de convalidación. Desarrollado con Laravel 12, PHP 8.2+ y PostgreSQL 15.


---

## Documentación

Toda la documentación técnica y manuales se encuentran en la carpeta `/documentation`:

- **[Manual de Usuario](documentation/Manual%20del%20usuario_%20Sistema%20de%20Convalidaciones%20Curriculares.pdf)**: Guía completa para usuarios finales
- **[Documento de Ingeniería de Software](documentation/Ingeniería%20de%20Software_%20Sistema%20de%20Convalidaciones%20Curriculares.pdf)**: Especificaciones técnicas del sistema
- **[Diagrama Entidad-Relación](documentation/DIAGRAMA_ENTIDAD_RELACION.md)**: Modelo de datos completo (25 tablas)
- **[Trabajo Futuro y Mejoras Propuestas](documentation/Trabajo%20Futuro%20y%20Mejoras%20Propuestas_%20Sistema%20de%20Convalidaciones%20Curriculares.pdf)**: Guía para resumir el trabajo previo
- **[Video tutorial](https://drive.google.com/file/d/1n6iGEVPiNFc38PbxUmkE0z0QoKd9bxzY/view?usp=drive_link)**: Video tutorial de cómo usar el aplicativo


---

## Despliegue con Docker

### Requisitos Previos

- **Docker** (versión 20.10 o superior)
- **Docker Compose** (versión 2.0 o superior)
- **Git**

### Instalación Rápida

#### 1. Clonar el Repositorio

```bash
git clone https://github.com/DmejiariUnal8313/Simulaci-n_Curricular.git
cd Simulaci-n_Curricular
```

#### 2. Configurar Variables de Entorno

```bash
# Copiar el archivo de ejemplo
cp .env.example .env

# Editar las credenciales de base de datos
nano .env
```

**Configuración mínima requerida en `.env`:**

```env
# Base de Datos
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=simulacion_curricular
DB_USERNAME=postgres
DB_PASSWORD=TuPasswordSegura123

# Aplicación
APP_NAME="Sistema de Convalidaciones"
APP_URL=http://localhost:8080
```

#### 3. Dar Permisos al Script

```bash
chmod +x docker.sh
```

#### 4. Desplegar el Sistema

```bash
./docker.sh setup
```

Este comando ejecutará automáticamente:
- Construcción de contenedores Docker
- Instalación de dependencias PHP (Composer)
- Generación de clave de aplicación Laravel
- Instalación de dependencias JavaScript (NPM)
- Compilación de assets frontend
- Ejecución de migraciones de base de datos
- Carga de datos iniciales (seeders)

**Correr esto si es primera vez que se despliega**

```bash
docker-compose exec app php artisan db:seed --class=DepartmentUserSeeder
```

#### 5. Acceder al Sistema

Una vez completada la instalación:

- **Aplicación Web**: http://localhost:8080
- **Base de Datos**: localhost:5432

> **Importante**: Cambiar la contraseña en el primer inicio de sesión.
---

## 🛠️ Comandos Disponibles con `docker.sh`

El script `docker.sh` proporciona comandos convenientes para gestionar el sistema:

### Gestión de Contenedores

```bash
# Iniciar todos los contenedores
./docker.sh start

# Detener todos los contenedores
./docker.sh stop

# Reiniciar todos los contenedores
./docker.sh restart

# Ver el estado de los contenedores
docker-compose ps
```

### Logs y Debugging

```bash
# Ver logs de todos los servicios
./docker.sh logs

# Ver logs de un servicio específico
./docker.sh logs app     # Logs de PHP-FPM
./docker.sh logs web     # Logs de Nginx
./docker.sh logs db      # Logs de PostgreSQL

# Seguir logs en tiempo real
./docker.sh logs -f app
```

### Acceso a Contenedores

```bash
# Acceder al contenedor de la aplicación (bash)
./docker.sh shell

# Acceder a la base de datos PostgreSQL
./docker.sh db-shell
```

### Comandos Laravel (Artisan)

```bash
# Ejecutar migraciones
./docker.sh artisan migrate

# Ejecutar migraciones con seed
./docker.sh artisan migrate --seed

# Revertir última migración
./docker.sh artisan migrate:rollback

# Ver estado de migraciones
./docker.sh artisan migrate:status

# Limpiar caché de la aplicación
./docker.sh artisan cache:clear
./docker.sh artisan config:clear
./docker.sh artisan route:clear
./docker.sh artisan view:clear

# Ejecutar tests
./docker.sh artisan test
```

### Comandos Composer

```bash
# Instalar dependencias
./docker.sh composer install

# Actualizar dependencias
./docker.sh composer update

# Agregar un paquete
./docker.sh composer require vendor/package
```

### Comandos NPM

```bash
# Instalar dependencias
./docker.sh npm install

# Compilar assets en modo desarrollo
./docker.sh npm run dev

# Compilar assets en modo producción
./docker.sh npm run build

# Modo watch (recompilación automática)
./docker.sh npm run watch
```

### Ayuda

```bash
# Ver todos los comandos disponibles
./docker.sh help
```

---
