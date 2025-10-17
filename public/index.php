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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a NutriApp</title>
    <!-- Usamos los mismos estilos que en el resto de la app para consistencia -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Header con estilos de Bootstrap y personalizados -->
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
                    <li class="nav-item"><a class="nav-link" href="contactos.php">Contacto</a></li>
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
    
    <!-- Contenido principal de la página pública -->
    <main>
        <!-- Sección Hero -->
        <div class="px-4 py-5 my-5 text-center">
            <i class="bi bi-heart-pulse display-1 text-primary"></i>
            <h1 class="display-5 fw-bold mt-3">Transforma tu gestión nutricional</h1>
            <div class="col-lg-6 mx-auto">
                <p class="lead mb-4">La plataforma todo-en-uno para nutricionistas y pacientes. Simplifica el seguimiento, personaliza planes y alcanza tus metas de salud de forma colaborativa y eficiente.</p>
                <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                    <a href="contactos.php" class="btn btn-primary btn-lg px-4 gap-3">Solicitar Cita</a>
                    <a href="nosotros.php" class="btn btn-outline-secondary btn-lg px-4">Sobre Nosotros</a>
                </div>
            </div>
        </div>

        <!-- Galería de Profesionales -->
        <h2 class="pb-2 border-bottom text-center">Profesionales que nos eligen</h2>
        <div class="gallery-container">
            <button class="btn-izq">&#10094;</button>
            <div class="gallery">
                <div class="gallery-item">
                    <img src="../img/nutri1.jpg" alt="Profesional 1">
                    <div class="description">
                        <p>Dra. Ana Pérez, especialista en nutrición deportiva. Ayudando a atletas a alcanzar su máximo potencial.</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="../img/nutri2.webp" alt="Profesional 2">
                    <div class="description">
                        <p>Lic. Marta Sánchez, dedicada a la nutrición infantil y a crear hábitos alimenticios saludables desde la niñez.</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="../img/nutri3.jpg" alt="Profesional 3">
                    <div class="description">
                        <p>Dra. Laura Gómez, experta en nutrición clínica y manejo de condiciones metabólicas a través de la alimentación.</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="../img/nutri4.jpg" alt="Profesional 4">
                    <div class="description">
                        <p>Lic. Carlos García, enfocado en dietas personalizadas para el control de peso y hábitos saludables.</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="../img/nutri4.jpg" alt="Profesional 4">
                    <div class="description">
                        <p>Lic. Carlos García, enfocado en dietas personalizadas para el control de peso y hábitos saludables.</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="../img/nutri4.jpg" alt="Profesional 4">
                    <div class="description">
                        <p>Lic. Carlos García, enfocado en dietas personalizadas para el control de peso y hábitos saludables.</p>
                    </div>
                </div>
            </div>
            <button class="btn-der">&#10095;</button>
        </div>

        


        <!-- Sección de Características -->
        <div class="container px-4 py-5" id="features">
            <h2 class="pb-2 border-bottom text-center">Una herramienta para cada necesidad</h2>
            <div class="row g-4 py-5 row-cols-1 row-cols-lg-3">
                <div class="col d-flex align-items-start">
                    <div class="icon-square text-bg-light d-inline-flex align-items-center justify-content-center fs-4 flex-shrink-0 me-3">
                        <i class="bi bi-person-workspace text-primary"></i>
                    </div>
                    <div>
                        <h3 class="fs-2">Para Nutricionistas</h3>
                        <p>Gestiona tu cartera de pacientes, crea planes de alimentación personalizados, asigna turnos y sigue su progreso en tiempo real desde un único lugar.</p>
                    </div>
                </div>
                <div class="col d-flex align-items-start">
                    <div class="icon-square text-bg-light d-inline-flex align-items-center justify-content-center fs-4 flex-shrink-0 me-3">
                        <i class="bi bi-person-check text-primary"></i>
                    </div>
                    <div>
                        <h3 class="fs-2">Para Pacientes</h3>
                        <p>Accede a tu plan nutricional, registra tus comidas diarias con fotos y comentarios, y mantén una comunicación fluida con tu profesional de la salud.</p>
                    </div>
                </div>
                <div class="col d-flex align-items-start">
                    <div class="icon-square text-bg-light d-inline-flex align-items-center justify-content-center fs-4 flex-shrink-0 me-3">
                        <i class="bi bi-shield-check text-primary"></i>
                    </div>
                    <div>
                        <h3 class="fs-2">Seguro y Confiable</h3>
                        <p>Tu información está protegida. Usamos un sistema de autenticación robusto para garantizar que solo tú y tu profesional tengan acceso a tus datos.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer de la página pública -->
    <footer class="container py-5">
        <div class="row">
            <div class="col-12 text-center">
                <p class="text-muted">&copy; 2025 Alumnos de UTN Haedo. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="main.js"></script>
</body>
</html>