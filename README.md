# NutriApp - Sistema de Gestión Nutricional 🥗

¡Bienvenido a NutriApp! Una aplicación web diseñada para facilitar la gestión de pacientes y planes nutricionales, ofreciendo una interfaz clara y segura para diferentes tipos de usuarios.

---

## ✨ Características Principales

- **Autenticación Segura:** Sistema de inicio de sesión robusto con contraseñas hasheadas para garantizar la seguridad de los datos.
- **Gestión de Roles:** Control de acceso diferenciado para tres tipos de usuarios:
  - 👤 **Super Administrador:** Control total sobre la plataforma.
  - 🧑‍⚕️ **Nutricionista:** Gestión de sus pacientes y planes.
  - 🚶 **Paciente:** Acceso a su información y seguimiento.
- **Paneles Dedicados:** Cada rol cuenta con un panel de control (dashboard) personalizado y seguro.
- **Interfaz Moderna y Responsiva:** Diseño limpio y adaptable a cualquier dispositivo (móvil, tablet, escritorio) gracias a Bootstrap 5.

---

## 🛠️ Tecnologías Utilizadas

Este proyecto está construido con un stack de tecnologías moderno y ampliamente utilizado en el desarrollo web.

### Backend
- **PHP 8+:** Lenguaje de programación del lado del servidor para toda la lógica de negocio.
- **MySQL:** Sistema de gestión de bases de datos para almacenar la información.
- **PDO (PHP Data Objects):** Extensión para una conexión segura y estandarizada a la base de datos.

### Frontend
- **HTML5:** Estructura semántica del contenido.
- **CSS3:** Estilos personalizados para una apariencia única.
- **Bootstrap 5.3:** Framework CSS para un diseño responsivo y componentes de interfaz modernos.
- **Bootstrap Icons:** Biblioteca de íconos vectoriales.
- **Google Fonts (Inter):** Tipografía moderna para una mejor legibilidad.

### Entorno de Desarrollo
- **Servidor:** Apache (generalmente provisto por XAMPP, WAMP, etc.).

---

## 🚀 Instalación y Puesta en Marcha

Para ejecutar este proyecto en tu entorno local, sigue estos pasos:

1.  **Clonar el Repositorio:**
    ```bash
    git clone <URL-del-repositorio>
    ```

2.  **Configurar la Base de Datos:**
    -   Abre tu gestor de base de datos (como phpMyAdmin).
    -   Crea una nueva base de datos llamada `nutri`.
    -   Importa el archivo `nutri.sql` (si existe) para crear las tablas y registros iniciales.
    -   Verifica los datos de conexión en el archivo `app/config.php` y ajústalos si es necesario (host, usuario, contraseña).

    ```php
    // app/config.php
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'nutri');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    ```

3.  **Mover los Archivos del Proyecto:**
    -   Copia la carpeta completa del proyecto dentro del directorio raíz de tu servidor web (ej. `C:\xampp\htdocs\` si usas XAMPP).

4.  **Acceder a la Aplicación:**
    -   Abre tu navegador y ve a `http://localhost/nutri/app/`. Serás redirigido a la página de inicio de sesión.

---

## 📂 Estructura del Proyecto

El proyecto está organizado de la siguiente manera para mantener el código limpio y escalable.

```
/nutri
├── app/                  # Núcleo de la aplicación
│   ├── assets/           # Recursos estáticos (CSS, JS, imágenes)
│   │   └── css/
│   │       └── styles.css      # Estilos para el login
│   ├── roles/            # Paneles de control por rol
│   │   ├── super_usuario/
│   │   │   └── index.php # Dashboard del Superadmin
│   │   ├── nutricionista/
│   │   └── paciente/
│   ├── autenticar.php    # Script de procesamiento de login
│   ├── config.php        # Configuración de la base de datos
│   ├── dashboard_estilos.css # Estilos para los dashboards
│   ├── index.php         # Página de inicio de sesión
│   └── logout.php        # Script para cerrar sesión
│
└── public/               # Archivos de la web pública/landing page
    └── index.html
```

---

## 🎨 Interfaces Desarrolladas

### 1. Página de Login (`app/index.php`)

Una interfaz limpia y minimalista para que los usuarios accedan al sistema.
- Muestra un formulario para ingresar email y contraseña.
- Valida los campos y muestra mensajes de error claros y amigables en caso de credenciales incorrectas o campos vacíos.
- Diseño responsivo que se adapta a cualquier tamaño de pantalla.

### 2. Panel de Super Administrador (`app/roles/super_usuario/index.php`)

Es el área privada para el usuario con los máximos privilegios.
- **Acceso Restringido:** Solo los usuarios con `role_id = 1` pueden ingresar. Cualquier otro intento es redirigido al login.
- **Bienvenida Personalizada:** Muestra un mensaje de bienvenida con el nombre del usuario, obtenido de la variable de sesión.
- **Navegación Segura:** Incluye un encabezado con una opción clara para "Cerrar Sesión", que destruye la sesión activa y protege la cuenta.

---

## ✒️ Autores

Este proyecto fue desarrollado con dedicación por:

- **Alumnos de UTN Haedo**

&copy; 2024 - Todos los derechos reservados.