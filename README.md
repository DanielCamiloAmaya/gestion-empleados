# Gestión de Empleados

![Gestión de Empleados]([https://via.placeholder.com/800x200.png?text=Gestión+de+Empleados](https://media.gettyimages.com/id/1400038121/es/vector/concepto-de-dise%C3%B1o-de-banner-vectorial-relacionado-con-recursos-humanos-estilo-de-l%C3%ADnea.jpg?s=2048x2048&w=gi&k=20&c=lKNxPNjHdoz-biurSIExz_-LtG2tyAP7GSKgo_FyiG0=))

Este proyecto es una aplicación de gestión de empleados construida con Laravel. Permite a los administradores registrar, editar y eliminar empleados y departamentos.

## Requisitos Previos

Antes de empezar, asegúrate de tener instalado lo siguiente:

- PHP >= 7.3
- Composer
- Node.js y npm
- MySQL o cualquier otro sistema de gestión de bases de datos compatible con Laravel

## Instalación

Sigue estos pasos para configurar y ejecutar el proyecto localmente.

### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/gestion-empleados.git
cd gestion-empleados
```

### 2. Instalar dependencias de PHP y JavaScript

```bash
composer install
npm install
```

### 3. Configurar el archivo `.env`

Copia el archivo `.env.example` y renómbralo a `.env`.

```bash
cp .env.example .env
```

Asegúrate de configurar las variables de entorno para la base de datos y otras configuraciones necesarias en el archivo `.env`.

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_de_tu_base_de_datos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### 4. Generar la clave de la aplicación

```bash
php artisan key:generate
```

### 5. Ejecutar migraciones y sembrar la base de datos

```bash
php artisan migrate --seed
```

### 6. Compilar activos front-end

```bash
npm run dev
```

## Ejecución

Para ejecutar la aplicación, usa el siguiente comando:

```bash
php artisan serve
```

Luego, abre tu navegador web y accede a: [http://127.0.0.1:8000](http://127.0.0.1:8000).

## Pruebas

### Pruebas Unitarias y de Integración

Para ejecutar las pruebas unitarias y de integración, usa el siguiente comando:

```bash
php artisan test
```

### Pruebas Manuales

#### Registrar Administrador

1. Ve a [http://127.0.0.1:8000/admin/register](http://127.0.0.1:8000/admin/register) y completa el formulario de registro.
2. Crea una cuenta de administrador.

#### Login de Administrador

1. Accede a [http://127.0.0.1:8000/admin/login](http://127.0.0.1:8000/admin/login) e inicia sesión con las credenciales de administrador.

#### Home de Administrador

1. Al iniciar sesión, entrarás en [http://127.0.0.1:8000/admin/home](http://127.0.0.1:8000/admin/home).
2. Desde aquí, puedes buscar, agregar, editar y eliminar empleados y departamentos.

#### Gestionar Empleados

1. Inicia sesión como administrador.
2. Navega a [http://127.0.0.1:8000/empleados](http://127.0.0.1:8000/empleados) para:
   - Buscar empleados por nombre o departamento.
   - Agregar, editar o eliminar empleados.

#### Gestionar Departamentos

1. Inicia sesión como administrador.
2. Navega a [http://127.0.0.1:8000/departamentos](http://127.0.0.1:8000/departamentos) para agregar, editar o eliminar departamentos.

#### Registrar Usuario

1. Ve a [http://127.0.0.1:8000/register](http://127.0.0.1:8000/register) y completa el formulario de registro.

#### Login de Usuario

1. Accede a [http://127.0.0.1:8000/login](http://127.0.0.1:8000/login) e inicia sesión con las credenciales de usuario.

#### Home de Usuario

1. Al iniciar sesión, entrarás en [http://127.0.0.1:8000/home](http://127.0.0.1:8000/home).
2. No podrás agregar, editar ni eliminar empleados o departamentos, pero sí realizar búsquedas.

## Estructura del Proyecto

- `app/` - Contiene los controladores, modelos y otros componentes de la lógica de la aplicación.
- `resources/views/` - Contiene las vistas de Blade para la interfaz de usuario.
- `routes/web.php` - Define las rutas de la aplicación.
- `database/migrations/` - Contiene las migraciones de la base de datos.

## Licencia

El framework Laravel es software de código abierto licenciado bajo la [licencia MIT](https://opensource.org/licenses/MIT).


