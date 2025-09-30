<?php
// Iniciar la sesión para poder manejar mensajes
session_start();

// Comprobar si hay mensajes en la URL (enviados desde el script que procesa la recuperación)
$mensaje = '';
$tipo_mensaje = ''; // 'error' o 'success'

if (isset($_GET['exito']) && $_GET['exito'] == 'enviado') {
    $mensaje = 'Si tu email está en nuestro sistema, recibirás un correo con las instrucciones.';
    $tipo_mensaje = 'success';
}

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'email_no_encontrado') {
        $mensaje = 'No se encontró ninguna cuenta con ese email.';
        $tipo_mensaje = 'error';
    } elseif ($_GET['error'] == 'campos_vacios') {
        $mensaje = 'Por favor, ingresa tu dirección de email.';
        $tipo_mensaje = 'error';
    } elseif ($_GET['error'] == 'email_error' || $_GET['error'] == 'db_error') {
        $mensaje = 'Ocurrió un problema. Por favor, intenta de nuevo más tarde.';
        $tipo_mensaje = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - NutriApp</title>

    <!-- Dependencias de Estilos (las mismas que el login) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/styles.css"> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

    <!-- Header simplificado -->
    <header class="navbar navbar-expand-lg shadow-sm navbar-primary bg-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <i class="bi bi-heart-pulse fs-4 me-2"></i>
                <strong>NutriApp</strong>
            </a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link inicio-link" href="../index.php">Inicio</a>
                </li>
            </ul>
        </div>
    </header>

    <!-- Contenedor del formulario -->
    <main class="login-wrapper">
        <div class="login-card">
            <h1 class="form-title">Recuperar Contraseña</h1>
            <p class="form-subtitle">Ingresa tu email para recibir instrucciones</p>

            <?php if ($mensaje && $tipo_mensaje == 'error'): ?>
                <div class="error-message"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <?php if ($mensaje && $tipo_mensaje == 'success'): ?>
                <div class="success-message"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <form action="procesar_recuperacion.php" method="POST">
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>
                <button type="submit" class="btn-primary">Enviar Instrucciones</button>
            </form>

            <a href="index.php" class="back-to-login">Volver a Iniciar Sesión</a>
        </div>
    </main>

    <!-- Footer con el copyright -->
    <footer class="login-footer">
        <p>&copy; 2024 Alumnos de UTN Haedo. Todos los derechos reservados.</p>
    </footer>

</body>
</html>
