<?php
// Iniciar la sesión para poder manejar mensajes y sesiones
session_start();

// Primero, verificamos si venimos de un cierre de sesión.
// Si es así, nos aseguramos de que la sesión esté completamente destruida.
if (isset($_GET['exito']) && $_GET['exito'] === 'logout') {
    // Destruir todas las variables de la sesión.
    $_SESSION = array();

    // Borrar la cookie de sesión.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }

    session_destroy(); // Destruir la sesión en el servidor.
}

if (isset($_SESSION['user_id'])) {
    // Si el usuario ya está logueado, redirigirlo a la página correspondiente según su rol
    if ($_SESSION['user_rol'] == 1) {
        header('Location: roles/super_usuario/index.php');
        exit;
    } elseif ($_SESSION['user_rol'] == 2) {
        header('Location: roles/nutricionista/index.php');
        exit;
    } elseif ($_SESSION['user_rol'] == 3) {
        header('Location: roles/paciente/index.php');
        exit;
    }
}

// Comprobar si hay un mensaje de error en la URL (enviado desde login.php)
$error = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'campos_vacios') {
        $error = 'Por favor, completa todos los campos.';
    } elseif ($_GET['error'] == 'credenciales_invalidas') {
        $error = 'El email o la contraseña son incorrectos.';
    }
}

$exito = '';
if (isset($_GET['exito'])) {
    if ($_GET['exito'] == 'logout') {
        $exito = 'Has cerrado sesión correctamente.';
    } elseif ($_GET['exito'] == 'password_actualizada') {
        $exito = '¡Contraseña actualizada con éxito! Ya puedes iniciar sesión.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NutriApp</title>

    <!-- Dependencias de Estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../public/styles.css"> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="../public/styles.css" rel="stylesheet">
</head>
<body>

    <!-- Contenedor para las notificaciones (toasts). Debe estar aquí para un posicionamiento global correcto. -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100"></div>

    <!-- Header simplificado para la página de login -->
    <header class="navbar navbar-expand-lg shadow-sm navbar-primary bg-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../public/index.php"> <!-- Enlace a la web principal -->
                <i class="bi bi-heart-pulse fs-4 me-2"></i>
                <strong>NutriApp</strong>
            </a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link inicio-link" href="../public/index.php">Volver</a> <!-- Estilo mejorado -->
                </li>
            </ul>
        </div>
    </header>

    <!-- Contenedor del formulario de login -->
    <main class="login-wrapper">
        <div class="login-card">
            <h1 class="form-title">Bienvenido de nuevo</h1>
            <p class="form-subtitle">Ingresa a tu cuenta para continuar</p>

            <form action="autenticar.php" method="POST">
                <div class="input-group"><label for="email">Email</label><input type="email" id="email" name="email" class="form-input" required></div>
                <div class="input-group"><label for="password">Contraseña</label><input type="password" id="password" name="password" class="form-input" required></div>
                <a href="recuperar.php" class="forgot-password">¿Olvidaste tu contraseña?</a>
                <button type="submit" class="btn-primary">Iniciar Sesión</button>
            </form>
        </div>
    </main>

    <!-- Footer con el copyright -->
    <footer class="login-footer">
        <p>&copy; 2025 Alumnos de UTN Haedo. Todos los derechos reservados.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    // Lógica para mostrar notificaciones como "toasts"
    $toast_mensaje = '';
    $toast_tipo = '';

    if ($error) {
        $toast_mensaje = $error;
        $toast_tipo = 'danger';
    } elseif ($exito) {
        $toast_mensaje = $exito;
        $toast_tipo = 'success';
    }

    if ($toast_mensaje):
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const toastContainer = document.querySelector('.toast-container');
        const toastMessage = "<?php echo addslashes(htmlspecialchars($toast_mensaje)); ?>";
        const toastType = "<?php echo $toast_tipo; ?>";
        
        const icon = toastType === 'success' ? '<i class="bi bi-check-circle-fill me-2"></i>' : '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
        const toastHTML = `
            <div class="toast align-items-center text-white bg-${toastType} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${icon}
                        ${toastMessage}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.innerHTML = toastHTML;
        const toastEl = toastContainer.querySelector('.toast');
        const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
        toast.show();
    });
    </script>
    <?php endif; ?>
</body>
</html>