# NutriApp - Sistema de Gestión Nutricional 🥗

¡Bienvenido a NutriApp! Una aplicación web diseñada para facilitar la gestión de pacientes y planes nutricionales, ofreciendo una interfaz clara y segura para diferentes tipos de usuarios.

---

## ✨ Características Principales

- **Autenticación Segura:** Sistema de inicio de sesión robusto con contraseñas hasheadas para garantizar la seguridad de los datos.
- **Recuperación de Contraseña:** Flujo completo para que los usuarios puedan restablecer su contraseña de forma segura a través de un token enviado a su email.
- **Gestión de Roles:** Control de acceso diferenciado para tres tipos de usuarios:
  - 👤 **Super Administrador:** Control total sobre la plataforma.
  - 🧑‍⚕️ **Nutricionista:** Gestión de sus pacientes y planes.
  - 🚶 **Paciente:** Acceso a su información y seguimiento.
- **Gestión de Usuarios (CRUD):** El super administrador puede crear, leer, actualizar y eliminar usuarios a través de una interfaz intuitiva con modales.
  - **Creación Segura:** Al crear un usuario, se le envía un email para que establezca su propia contraseña.
  - **Eliminación Segura:** Para evitar acciones accidentales, se requiere la contraseña del administrador para confirmar la eliminación de otro usuario.
- **Paneles Dedicados:** Cada rol cuenta con un panel de control (dashboard) personalizado y seguro.
- **Interfaz Moderna y Responsiva:** Diseño limpio y adaptable a cualquier dispositivo (móvil, tablet, escritorio) gracias a Bootstrap 5.
- **Notificaciones Dinámicas:** Mensajes de éxito y error claros para informar al usuario sobre el resultado de sus acciones.

---

## 🛠️ Tecnologías Utilizadas

Este proyecto está construido con un stack de tecnologías moderno y ampliamente utilizado en el desarrollo web.

### Backend
- **PHP 8+:** Lenguaje de programación del lado del servidor para toda la lógica de negocio.
- **MySQL:** Sistema de gestión de bases de datos para almacenar la información.
- **PDO (PHP Data Objects):** Extensión para una conexión segura y estandarizada a la base de datos.
- **PHPMailer:** Biblioteca para el envío de correos electrónicos (recuperación de contraseña, bienvenida).

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
    -   Abre tu navegador y ve a `http://localhost/nutricionista/app`. Serás redirigido a la página de inicio de sesión.

---

## 📂 Estructura del Proyecto

El proyecto está organizado de la siguiente manera para mantener el código limpio y escalable.

```
/nutri
├── app/                  # Núcleo de la aplicación
│   ├── assets/           # Recursos estáticos (CSS, JS, imágenes)
│   │   └── css/
│   │       └── styles.css      # Estilos para el login
│   ├── libs/             # Bibliotecas de terceros
│   │   └── vendor/       # Autoloader y paquetes de Composer (PHPMailer)
│   ├── roles/            # Paneles de control por rol
│   │   ├── super_usuario/
│   │   │   ├── index.php         # Dashboard del Superadmin (lista de usuarios)
│   │   │   ├── crear_usuario.php   # Procesa la creación de usuarios
│   │   │   ├── actualizar_usuario.php # Procesa la actualización
│   │   │   └── eliminar_usuario.php  # Procesa la eliminación
│   │   ├── nutricionista/
│   │   └── paciente/
│   ├── autenticar.php    # Script de procesamiento de login
│   ├── config.php        # Configuración de la base de datos
│   ├── index.php         # Página de inicio de sesión
│   ├── logout.php        # Script para cerrar sesión
│   ├── recuperar.php     # Formulario para solicitar reseteo de contraseña
│   ├── procesar_recuperacion.php # Envía el email de reseteo
│   ├── resetear.php      # Formulario para crear nueva contraseña
│   └── procesar_reseteo.php # Procesa y guarda la nueva contraseña
│
└── public/               # Archivos de la web pública/landing page
    └── index.html
```

---

## 🎨 Interfaces Desarrolladas

### 1. Flujo de Autenticación y Recuperación

- **Página de Login (`app/index.php`):** Interfaz limpia para que los usuarios accedan al sistema. Valida los campos y muestra mensajes de error claros.
- **Página de Recuperación (`app/recuperar.php`):** Permite a los usuarios solicitar un enlace para restablecer su contraseña.
- **Página de Reseteo (`app/resetear.php`):** Página segura a la que se accede mediante un token único para establecer una nueva contraseña.

### 2. Panel de Super Administrador

Es el área privada para el usuario con los máximos privilegios.
- **Acceso Restringido:** Solo los usuarios con `role_id = 1` pueden ingresar. Cualquier otro intento es redirigido al login.
- **Dashboard de Gestión (`app/roles/super_usuario/index.php`):**
  - Muestra una tabla con todos los usuarios del sistema, su rol, email y fecha de registro.
  - Permite realizar operaciones CRUD (Crear, Leer, Actualizar, Eliminar) a través de modales de Bootstrap, sin recargar la página.
  - **Crear Usuario:** Abre un modal para añadir un nuevo usuario, asignarle un rol y una contraseña temporal. El sistema luego le envía un email para que establezca su contraseña final.
  - **Editar Usuario:** Abre un modal con los datos del usuario para modificar su nombre, email o rol.
  - **Eliminar Usuario:** Abre un modal de confirmación que, por seguridad, solicita la contraseña del super administrador para completar la acción.
- **Navegación Segura:** Incluye un encabezado con una opción clara para "Cerrar Sesión", que destruye la sesión activa y protege la cuenta.

---

## 🩺 Panel del Nutricionista (nuevos endpoints y reglas)

El rol `nutricionista` tiene ahora funcionalidades ampliadas para gestionar SUS pacientes. Importante: un nutricionista solo puede ver y actuar sobre pacientes que le pertenecen —esto se determina en este orden:

- Si la tabla `usuarios` tiene la columna `assigned_nutricionista_id`, se usa para la asignación directa.
- Si no existe esa columna, la pertenencia se deriva de la existencia de al menos un registro en la tabla `turnos` entre el nutricionista y el paciente.

Endpoints disponibles en `app/roles/nutricionista/`:

- `index.php` — Panel principal (muestra solo pacientes asignados).
- `crear_usuario.php` — Crea pacientes (server-side valida que el rol sea `paciente` y, si existe, asigna `assigned_nutricionista_id`).
- `actualizar_usuario.php` — Actualiza datos de pacientes que te pertenecen (no permite cambiar el rol a otro distinto de `paciente`).
- `eliminar_usuario.php` — Solicita al super admin la eliminación de un paciente; no borra datos directamente. Solo puede solicitar eliminación de pacientes que te pertenezcan.
- `crear_turno.php` — Crear un turno para un paciente asignado.
- `cancelar_turno.php` — Cancelar un turno que pertenezca al nutricionista.
- `registrar_pago.php` — Registrar seña / marcar pago y monto de una consulta (por turno_id o paciente+fecha).
- `vista_turnos.php` — Vista básica semanal/mensual de tus turnos con acciones rápidas (cancelar, marcar pago).
- `ver_historia.php` — Ver la historia clínica de un paciente que te pertenece.
- `agregar_dieta.php` — Agregar una dieta asociada a un paciente que te pertenece.
- `agregar_habito.php` — Agregar un hábito recomendado para un paciente que te pertenece.
- `gestionar_jornada.php` / `bloquear_dia.php` — Guardar jornada y bloquear días (si las tablas correspondientes existen).
- `actualizar_password.php` — Cambiar tu propia contraseña (verificando la actual).

Mensajes de error comunes (GET params): `no_autorizado`, `paciente_no_encontrado`, `turno_no_encontrado`, `tabla_dietas_no_exist`, `tabla_habitos_no_exist`, `tabla_jornadas_no_exist`, `id_invalido`.

Si alguna tabla auxiliar (por ej. `notificaciones`, `historias`, `dietas`, `habitos`, `jornadas`, `dias_no_laborales`) no existe, los endpoints devolverán un error visible y lo registrarán en `error_log`.

SQL sugerido (esquema mínimo) para crear las tablas auxiliares:

CREATE TABLE `archivos_plan` (
  `id` bigint(20) NOT NULL,
  `id_paciente` bigint(20) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `url_archivo` text NOT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `consultas`
--

CREATE TABLE `consultas` (
  `id` bigint(20) NOT NULL,
  `id_paciente` bigint(20) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `peso_kg` decimal(6,2) DEFAULT NULL,
  `altura_cm` decimal(5,1) DEFAULT NULL,
  `masa_muscular_pct` decimal(5,2) DEFAULT NULL,
  `comentarios` text DEFAULT NULL,
  `habitos_cumplidos` text DEFAULT NULL,
  `objetivos_trabajados` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `consultas`
--

INSERT INTO `consultas` (`id`, `id_paciente`, `fecha`, `peso_kg`, `altura_cm`, `masa_muscular_pct`, `comentarios`, `habitos_cumplidos`, `objetivos_trabajados`) VALUES
(5, 17, '2025-11-12 05:03:00', 60.00, NULL, NULL, NULL, NULL, NULL),
(6, 17, '2025-10-12 05:03:00', 55.00, NULL, NULL, NULL, NULL, NULL),
(7, 17, '2025-09-12 05:03:00', 50.00, NULL, NULL, NULL, NULL, NULL),
(8, 17, '2025-11-01 05:03:00', 54.00, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contactos`
--

CREATE TABLE `contactos` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `texto` text NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `contactos`
--

INSERT INTO `contactos` (`id`, `email`, `nombre`, `texto`, `creado_en`) VALUES
(1, 'ffacurrombo@gmail.com', 'Facundo Rombola', 'Prueba carga de contacto en base de datos', '2025-11-12 02:36:50'),
(2, 'fernandocibau89@gmail.com', 'Alejandra Rodriguez', 'Hola quiero bajar de pesoooooooo.', '2025-11-15 00:14:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `diario`
--

CREATE TABLE `diario` (
  `id` bigint(20) NOT NULL,
  `id_paciente` bigint(20) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `tipo_comida` enum('desayuno','almuerzo','merienda','cena','snack') NOT NULL,
  `detalles` text DEFAULT NULL,
  `url_foto` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `diario`
--

INSERT INTO `diario` (`id`, `id_paciente`, `fecha_hora`, `tipo_comida`, `detalles`, `url_foto`) VALUES
(13, 17, '2025-11-15 01:02:57', 'desayuno', 'Hola probando', '//public/uploads/comidas/comida_6917c331b3e31.png'),
(14, 17, '2025-11-15 01:04:02', 'desayuno', '123123', '//public/uploads/comidas/comida_6917c37265f15.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados`
--

CREATE TABLE `estados` (
  `id` bigint(20) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estados`
--

INSERT INTO `estados` (`id`, `nombre`) VALUES
(1, 'activo'),
(3, 'inactivo'),
(2, 'pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habitos`
--

CREATE TABLE `habitos` (
  `id` bigint(20) NOT NULL,
  `id_paciente` bigint(20) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `visibilidad` enum('publico','privado') NOT NULL DEFAULT 'publico',
  `creado_por` enum('paciente','nutricionista') NOT NULL,
  `racha_dias` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `habitos`
--

INSERT INTO `habitos` (`id`, `id_paciente`, `nombre`, `color`, `visibilidad`, `creado_por`, `racha_dias`) VALUES
(5, 17, 'Tomar 2 lts de agua', '#0d6efd', 'publico', 'nutricionista', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habit_completados`
--

CREATE TABLE `habit_completados` (
  `id` bigint(20) NOT NULL,
  `id_habito` bigint(20) NOT NULL,
  `id_paciente` bigint(20) NOT NULL,
  `fecha` date NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `habit_completados`
--

INSERT INTO `habit_completados` (`id`, `id_habito`, `id_paciente`, `fecha`, `creado_en`) VALUES
(3, 5, 17, '2025-11-12', '2025-11-12 04:05:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `contenido` text NOT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp(),
  `leido` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nutricionistas`
--

CREATE TABLE `nutricionistas` (
  `id` bigint(20) NOT NULL,
  `id_usuario` bigint(20) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `nutricionistas`
--

INSERT INTO `nutricionistas` (`id`, `id_usuario`, `creado_en`) VALUES
(7, 38, '2025-11-12 00:54:53'),
(8, 43, '2025-11-12 12:51:47'),
(12, 48, '2025-11-12 22:58:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `id` bigint(20) NOT NULL,
  `id_usuario` bigint(20) DEFAULT NULL,
  `id_nutricionista` bigint(20) DEFAULT NULL,
  `dni` varchar(50) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `objetivo_principal` text DEFAULT NULL,
  `progreso_objetivo` int(11) NOT NULL DEFAULT 0,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`id`, `id_usuario`, `id_nutricionista`, `dni`, `fecha_nacimiento`, `telefono`, `objetivo_principal`, `progreso_objetivo`, `creado_en`) VALUES
(17, 39, 7, '12321312', '1999-11-12', '1130321164', 'Aumentar masa Muscular', 0, '2025-11-12 00:56:56'),
(18, NULL, 7, NULL, NULL, NULL, NULL, 0, '2025-11-12 00:59:58'),
(19, NULL, 7, NULL, NULL, NULL, NULL, 0, '2025-11-12 01:01:04'),
(22, 51, 12, NULL, NULL, NULL, NULL, 0, '2025-11-14 21:12:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recetas`
--

CREATE TABLE `recetas` (
  `id` bigint(20) NOT NULL,
  `id_nutricionista` bigint(20) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `contenido` text DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `publicado` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'superadmin', 'Acceso total al sistema'),
(2, 'nutricionista', 'Cuenta profesional de nutricionista'),
(3, 'paciente', 'Cuenta de paciente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

CREATE TABLE `turnos` (
  `id` bigint(20) NOT NULL,
  `id_nutricionista` bigint(20) NOT NULL,
  `id_paciente` bigint(20) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `senia` decimal(10,2) DEFAULT 0.00,
  `pagado` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `id_estado` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `turnos`
--

INSERT INTO `turnos` (`id`, `id_nutricionista`, `id_paciente`, `fecha_hora`, `senia`, `pagado`, `creado_en`, `id_estado`) VALUES
(19, 7, 17, '2025-11-14 21:08:00', 0.00, 1, '2025-11-14 21:08:54', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` bigint(20) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_exp` datetime DEFAULT NULL,
  `id_estado` bigint(20) DEFAULT NULL,
  `fecha_desactivacion` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `role_id`, `creado_en`, `reset_token`, `reset_token_exp`, `id_estado`, `fecha_desactivacion`) VALUES
(1, 'Admin Principal', 'admin@nutri.test', '$2y$10$8pZzf.Qu89DHIy3X6cchN.Hga9A97tfvaAie1QMr8wiBXR6sbqnnC', 1, '2025-09-27 20:52:29', '672f34bb3f6708e39f77f471a1605e17f577a4e8b2fb9de547007d03c86de845', '2025-11-13 02:54:16', 1, NULL),
(38, 'Juan Barela', 'nutricionista@nutri.test', '$2y$10$yPvyBTvIG99GIqD2kd0l9uHfDpSVTTMrMEilyNPP.AFXy/JOkLSYW', 2, '2025-11-12 00:54:53', '65647236a1e05eec85fcde771c4aa38c86570356b47432f727141ca021c16eb3', '2025-11-13 04:54:53', 2, NULL),
(39, 'Sofia Laura Perez', 'paciente@nutri.test', '$2y$10$ZUZLeHUGmJbjGrkTmjFAs.x7hwla8Df84BlQZsR5L5LCByxGbR9K.', 3, '2025-11-12 00:56:56', NULL, NULL, 1, '2025-11-12'),
(43, 'fernando nutri', 'fernandocibau@hotmail.com', '$2y$10$uW0wBq3I18Va13fxjLG5A.sRuoHx3CJSAoOTddaK/7.p6VkhS3oSG', 2, '2025-11-12 12:51:47', 'a30d88df47023921152773dcc4efa62da215de336d9fd8f519f193589b25eb5c', '2025-11-13 16:51:47', 1, NULL),
(44, 'Nancy Gambacotar', 'thekitin@hotmail.com', '$2y$10$60dJPHkg1TZaYC9DW1MDMOxr1A84vobeA.a242MVYpe.URPcg3ggq', 1, '2025-11-12 20:38:42', 'f5db3449c5ecec42bc6e8dbb4c9cca0add6259f12be26fb758ec31fc209c290d', '2025-11-14 00:38:42', 1, NULL),
(48, 'fernando cibau', 'fernandocibau89@gmail.com', '$2y$10$O/PQO7/PFpX7/cLMy8J5kugdVuzLYy/u.7uakcqASlx9nX7iPwPdO', 2, '2025-11-12 22:58:13', NULL, NULL, 1, NULL),
(51, 'Tomas Fernandez', 'tomasfernandez@nutri.test', '$2y$10$lN4TgAhQdbWa/QwNFSa2w.7IIjpYf2yAmWNBIaD4AfpkJNAvV32mG', 3, '2025-11-14 21:12:58', '4971af70784d01fd04a3de8f68b41af87c78d37b081c4b361927546733226dcc', '2025-11-16 01:12:58', 2, NULL);

--
-- Disparadores `usuarios`
--
DELIMITER $$
CREATE TRIGGER `eliminar_paciente_inactivo` AFTER DELETE ON `usuarios` FOR EACH ROW BEGIN
    DELETE FROM pacientes 
     WHERE id_usuario = OLD.id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `marca_desactivacion` BEFORE UPDATE ON `usuarios` FOR EACH ROW BEGIN
IF OLD.id_estado = 1 AND 
    NEW.id_estado = 3 THEN
    SET NEW.fecha_desactivacion = NOW();
    END IF;
END
$$
DELIMITER ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `archivos_plan`
--
ALTER TABLE `archivos_plan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_archivos_plan_id_paciente` (`id_paciente`);

--
-- Indices de la tabla `consultas`
--
ALTER TABLE `consultas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_consultas_id_paciente` (`id_paciente`);

--
-- Indices de la tabla `contactos`
--
ALTER TABLE `contactos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `diario`
--
ALTER TABLE `diario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diario_id_paciente` (`id_paciente`);

--
-- Indices de la tabla `estados`
--
ALTER TABLE `estados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `habitos`
--
ALTER TABLE `habitos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_habitos_id_paciente` (`id_paciente`);

--
-- Indices de la tabla `habit_completados`
--
ALTER TABLE `habit_completados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_hc_habito_paciente_fecha` (`id_habito`,`id_paciente`,`fecha`),
  ADD KEY `idx_hc_habito` (`id_habito`),
  ADD KEY `idx_hc_paciente` (`id_paciente`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `nutricionistas`
--
ALTER TABLE `nutricionistas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`),
  ADD KEY `idx_nutricionistas_id_usuario` (`id_usuario`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`),
  ADD KEY `idx_pacientes_id_nutricionista` (`id_nutricionista`);

--
-- Indices de la tabla `recetas`
--
ALTER TABLE `recetas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recetas_id_nutricionista` (`id_nutricionista`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_turnos_id_nutricionista` (`id_nutricionista`),
  ADD KEY `idx_turnos_id_paciente` (`id_paciente`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_usuarios_role_id` (`role_id`),
  ADD KEY `fk_usuarios_estado` (`id_estado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `archivos_plan`
--
ALTER TABLE `archivos_plan`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `consultas`
--
ALTER TABLE `consultas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `contactos`
--
ALTER TABLE `contactos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `diario`
--
ALTER TABLE `diario`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `estados`
--
ALTER TABLE `estados`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `habitos`
--
ALTER TABLE `habitos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `habit_completados`
--
ALTER TABLE `habit_completados`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `nutricionistas`
--
ALTER TABLE `nutricionistas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `recetas`
--
ALTER TABLE `recetas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `archivos_plan`
--
ALTER TABLE `archivos_plan`
  ADD CONSTRAINT `fk_archivos_plan_paciente` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `consultas`
--
ALTER TABLE `consultas`
  ADD CONSTRAINT `fk_consultas_paciente` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `diario`
--
ALTER TABLE `diario`
  ADD CONSTRAINT `fk_diario_paciente` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `habitos`
--
ALTER TABLE `habitos`
  ADD CONSTRAINT `fk_habitos_paciente` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `habit_completados`
--
ALTER TABLE `habit_completados`
  ADD CONSTRAINT `fk_hc_habito` FOREIGN KEY (`id_habito`) REFERENCES `habitos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hc_paciente` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `nutricionistas`
--
ALTER TABLE `nutricionistas`
  ADD CONSTRAINT `fk_nutricionistas_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD CONSTRAINT `fk_pacientes_nutricionista` FOREIGN KEY (`id_nutricionista`) REFERENCES `nutricionistas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pacientes_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `recetas`
--
ALTER TABLE `recetas`
  ADD CONSTRAINT `fk_recetas_nutricionista` FOREIGN KEY (`id_nutricionista`) REFERENCES `nutricionistas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD CONSTRAINT `fk_turnos_nutricionista` FOREIGN KEY (`id_nutricionista`) REFERENCES `nutricionistas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_turnos_paciente` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_estado` FOREIGN KEY (`id_estado`) REFERENCES `estados` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

DELIMITER $$
--
-- Eventos
--
CREATE DEFINER=`admin`@`nutri.test` EVENT `eliminar_usuario_inactivo` ON SCHEDULE EVERY 5 DAY STARTS '2025-11-01 00:00:00' ENDS '2027-10-01 00:27:29' ON COMPLETION PRESERVE ENABLE COMMENT 'ELIMINAR USUARIO INACTIVO (REVISA CADA 5 DÍAS)' DO DELETE FROM usuarios WHERE
fecha_desactivacion IS NOT NULL AND fecha_desactivacion <= NOW() - INTERVAL 60 DAY$$

DELIMITER ;
COMMIT;

---

## ✒️ Autores

Este proyecto fue desarrollado con dedicación por:

- **Alumnos de UTN Haedo**

&copy; 2025 - Todos los derechos reservados.
