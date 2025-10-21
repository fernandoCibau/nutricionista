<?php
// Iniciar la sesión para poder manejar mensajes de error
session_start();

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
    <link rel="stylesheet" href="assets/css/styles.css"> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="../public/styles.css" rel="stylesheet">
</head>
<body>

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

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($exito): ?>
                <div class="success-message"><?php echo htmlspecialchars($exito); ?></div>
            <?php endif; ?>

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

</body>
</html>