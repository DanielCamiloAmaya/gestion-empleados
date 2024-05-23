# Gestión de Empleados

Este proyecto es una aplicación de gestión de empleados construida con Laravel. Permite a los administradores registrar, editar, y eliminar empleados y departamentos.

## Requisitos previos

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
2. Instalar dependencias de PHP y JavaScript
bash
Copiar código
composer install
npm install
3. Configurar el archivo .env
Copia el archivo .env.example y renómbralo a .env.

bash
Copiar código
cp .env.example .env
Asegúrate de configurar las variables de entorno para la base de datos y otras configuraciones necesarias en el archivo .env.

env
Copiar código
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_de_tu_base_de_datos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
4. Generar la clave de la aplicación
bash
Copiar código
php artisan key:generate
5. Ejecutar migraciones y sembrar la base de datos
bash
Copiar código
php artisan migrate --seed
6. Compilar activos front-end
bash
Copiar código
npm run dev
Ejecución
Para ejecutar la aplicación, usa el siguiente comando:

bash
Copiar código
php artisan serve
Abre tu navegador web y ve a http://127.0.0.1:8000.

Pruebas
Pruebas Unitarias y de Integración
Para ejecutar las pruebas unitarias y de integración, usa el siguiente comando:

bash
Copiar código
php artisan test
Pruebas Manuales
Registrar Administrador:
Ve a http://127.0.0.1:8000/admin/register y completa el formulario de registro para crear una cuenta de administrador.
Login de Administrador:
Ve a http://127.0.0.1:8000/admin/login e inicia sesión con las credenciales del administrador.
Gestionar Empleados:
Después de iniciar sesión, navega a http://127.0.0.1:8000/empleados para agregar, editar o eliminar empleados.
Gestionar Departamentos:
Navega a http://127.0.0.1:8000/departamentos para agregar, editar o eliminar departamentos.
Estructura del Proyecto
app/ - Contiene los controladores, modelos y otros componentes de la lógica de la aplicación.
resources/views/ - Contiene las vistas de Blade para la interfaz de usuario.
routes/web.php - Define las rutas de la aplicación.
database/migrations/ - Contiene las migraciones de la base de datos.
Contribución
Si deseas contribuir a este proyecto, por favor sigue estos pasos:

Haz un fork del repositorio.
Crea una nueva rama (git checkout -b feature/nueva-funcionalidad).
Realiza los cambios necesarios y haz commit (git commit -am 'Agrega nueva funcionalidad').
Empuja los cambios a tu fork (git push origin feature/nueva-funcionalidad).
Abre un Pull Request en GitHub.
Licencia
Este proyecto está licenciado bajo la Licencia MIT. Consulta el archivo LICENSE para más detalles.
## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
