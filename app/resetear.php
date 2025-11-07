<?php
session_start();

// 1. Obtener el token de la URL.
$token = $_GET['token'] ?? '';

// 2. Si no hay token, no se puede continuar. Redirigir a la página de recuperación.
if (empty($token)) {
    header('Location: recuperar.php?error=token_invalido');
    exit;
}

// 3. Manejar mensajes de error que puedan venir de procesar_reseteo.php
$error = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'password_corta') {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($_GET['error'] == 'password_no_coincide') {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $error = 'Ha ocurrido un error inesperado. Inténtalo de nuevo.';
    }
}

// 4. Manejar mensaje de éxito
$exito = '';
if (isset($_GET['exito']) && $_GET['exito'] === 'password_actualizada') {
    $exito = '¡Contraseña actualizada con éxito! Serás redirigido al login en 3 segundos...';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - NutriApp</title>

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

    <!-- Header simplificado -->
    <header class="navbar navbar-expand-lg shadow-sm navbar-primary bg-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../public/index.php">
                <i class="bi bi-heart-pulse fs-4 me-2"></i>
                <strong>NutriApp</strong>
            </a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link inicio-link" href="index.php">Iniciar Sesión</a>
                </li>
            </ul>
        </div>
    </header>

    <!-- Contenedor del formulario -->
    <main class="login-wrapper">
        <div class="login-card">
            <h1 class="form-title">Crea tu nueva contraseña</h1>
            <p class="form-subtitle">Ingresa una contraseña segura para tu cuenta.</p>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif ($exito): ?>
                <div class="success-message"><?php echo htmlspecialchars($exito); ?></div>
            <?php endif; ?>

            <?php if (!$exito): // Solo mostrar el formulario si no hay mensaje de éxito ?>
                <form action="procesar_reseteo.php" method="POST">
                    <!-- Campo oculto para enviar el token -->
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="input-group">
                        <label for="password">Nueva Contraseña</label>
                        <input type="password" id="password" name="password" class="form-input" required>
                    </div>

                    <div class="input-group">
                        <label for="password_confirm">Confirmar Nueva Contraseña</label>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-input" required>
                    </div>

                    <button type="submit" class="btn-primary">Guardar Contraseña</button>
                </form>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>