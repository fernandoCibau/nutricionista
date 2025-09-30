<?php
session_start();

// 1. Obtener el token de la URL.
$token = $_GET['token'] ?? '';

if (empty($token)) {
    // Si no hay token, no se puede continuar.
    header('Location: index.php?error=token_invalido');
    exit;
}

// 2. Comprobar si hay mensajes de error.
$error = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'password_no_coincide') {
        $error = 'Las contraseñas no coinciden.';
    } elseif ($_GET['error'] == 'password_corta') {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($_GET['error'] == 'token_invalido' || $_GET['error'] == 'token_expirado') {
        $error = 'El enlace de recuperación no es válido o ha expirado. Por favor, solicita uno nuevo.';
    } else {
        $error = 'Ocurrió un error. Inténtalo de nuevo.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - NutriApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <header class="navbar navbar-expand-lg shadow-sm navbar-primary bg-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <i class="bi bi-heart-pulse fs-4 me-2"></i>
                <strong>NutriApp</strong>
            </a>
        </div>
    </header>

    <main class="login-wrapper">
        <div class="login-card">
            <h1 class="form-title">Crear Nueva Contraseña</h1>
            <p class="form-subtitle">Ingresa tu nueva contraseña a continuación.</p>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="procesar_reseteo.php" method="POST">
                <!-- Campo oculto para enviar el token -->
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="input-group">
                    <label for="password">Nueva Contraseña</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                </div>
                <div class="input-group">
                    <label for="password_confirm">Confirmar Contraseña</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-input" required>
                </div>
                <button type="submit" class="btn-primary">Guardar Contraseña</button>
            </form>
        </div>
    </main>

    <footer class="login-footer">
        <p>&copy; 2024 Alumnos de UTN Haedo. Todos los derechos reservados.</p>
    </footer>
</body>
</html>