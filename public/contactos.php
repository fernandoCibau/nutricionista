<?php
    session_start();

    // Determinar si el usuario está logueado para mostrar el enlace correcto al panel
    $dashboard_link = '../app/index.php'; // Por defecto, va al login
    if (isset($_SESSION['user_id'])) {
        switch ($_SESSION['user_rol']) {
            case 1:
                $dashboard_link = '../app/roles/super_usuario/index.php';
                break;
            case 2:
                $dashboard_link = '../app/roles/nutri/index.php';
                break;
            case 3:
                $dashboard_link = '../app/roles/paciente/index.php';
                break;
        }
    }

    // Manejo de mensajes de éxito y error desde enviar_contacto.php
    $mensaje = '';
    $tipo_mensaje = ''; // 'success' o 'danger'

    if (isset($_GET['exito']) && $_GET['exito'] === 'enviado') {
        $mensaje = '¡Gracias por tu mensaje! Nos pondremos en contacto contigo a la brevedad.';
        $tipo_mensaje = 'success';
    }

    if (isset($_GET['error'])) {
        $tipo_mensaje = 'danger';
        if ($_GET['error'] === 'campos_invalidos') {
            $mensaje = 'Error: Por favor, completa todos los campos del formulario.';
        } elseif ($_GET['error'] === 'envio_fallido') {
            $mensaje = 'Error: No se pudo enviar tu mensaje. Por favor, intenta de nuevo más tarde.';
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto - NutriApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Header con navegación mejorada -->
    <header class="navbar navbar-expand-lg shadow-sm navbar-primary bg-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-heart-pulse fs-4 me-2"></i>
                <strong>NutriApp</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavPublic" aria-controls="navbarNavPublic" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavPublic">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="nosotros.php">Nosotros</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="contactos.php">Contacto</a></li>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center flex-row">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link inicio-link" href="../app/index.php">Iniciar Sesión</a></li>
                    <?php else: ?>
                        <li class="nav-item me-3"><a class="nav-link" href="<?php echo $dashboard_link; ?>">Mi Panel</a></li>
                        <li class="nav-item"><a class="nav-link logout-link" href="../app/logout.php"><i class="bi bi-box-arrow-right"></i><span class="d-none d-sm-inline ms-1">Cerrar Sesión</span></a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </header>

    <main class="container my-5">
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold">Solicita tu Primera Consulta</h1>
            <p class="lead">Completa el formulario y da el primer paso hacia una vida más saludable. Nos contactaremos para coordinar una cita.</p>
        </div>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show col-md-8 mx-auto" role="alert" id="contact-form">
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <div class="row g-5">
            <!-- Formulario de Contacto -->
            <div class="col-md-6">
                <form action="enviar_contacto.php" method="POST">
                    <div class="mb-3"><label for="nombre" class="form-label">Nombre Completo</label><input type="text" class="form-control" id="nombre" name="nombre" required></div>
                    <div class="mb-3"><label for="email" class="form-label">Correo Electrónico</label><input type="email" class="form-control" id="email" name="email" required></div>
                    <input type="hidden" name="asunto" value="Solicitud de Primera Cita">
                    <div class="mb-3"><label for="mensaje" class="form-label">Cuéntanos sobre tus objetivos</label><textarea class="form-control" id="mensaje" name="mensaje" rows="5" required></textarea></div>
                    <button type="submit" class="btn btn-primary btn-lg">Enviar Solicitud</button>
                </form>
            </div>
            <!-- Mapa -->
            <div class="col-md-6">
                <div class="ratio ratio-16x9 rounded shadow-sm"><iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3282.979301909229!2d-58.6433938244805!3d-34.63000595841667!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95bcc760248c22d7%3A0xdf7d157eb54951e!2sUniversidad%20Tecnol%C3%B3gica%20Nacional%20-%20Facultad%20Regional%20Haedo%20(UTN-FRH)!5e0!3m2!1ses-419!2sar!4v1716320429550!5m2!1ses-419!2sar" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>
            </div>
        </div>
    </main>

    <!-- Footer con el copyright -->
    <footer class="login-footer">
        <p>&copy; 2025 Alumnos de UTN Haedo. Todos los derechos reservados.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>