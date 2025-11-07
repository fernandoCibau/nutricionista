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
    <title>Sobre Nosotros - NutriApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Header con navegación -->
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
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="nosotros.php">Nosotros</a></li>
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

    <main class="container my-5">
        <div class="row p-4 pb-0 pe-lg-0 pt-lg-5 align-items-center rounded-3 border shadow-lg">
            <div class="col-lg-7 p-3 p-lg-5 pt-lg-3">
                <h1 class="display-4 fw-bold lh-1 text-body-emphasis">Nuestra Misión</h1>
                <p class="lead">Facilitar la conexión entre profesionales de la nutrición y sus pacientes a través de una herramienta digital, moderna y segura. Creamos NutriApp con el objetivo de simplificar la gestión nutricional para que todos puedan enfocarse en lo más importante: alcanzar una vida más saludable.</p>
            </div>
            <div class="col-lg-4 offset-lg-1 p-0 overflow-hidden shadow-lg d-none d-lg-block">
                <img class="rounded-lg-3" src="https://images.unsplash.com/photo-1498837167922-ddd27525d352?q=80&w=2070&auto=format&fit=crop" alt="Alimentos saludables para nutrición" width="720">
            </div>
        </div>

        <div class="row g-4 py-5 row-cols-1 row-cols-lg-2">
            <div class="feature col">
                <div class="feature-icon d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-2 mb-3">
                    <i class="bi bi-tools"></i>
                </div>
                <h3 class="fs-2 text-body-emphasis">Sobre el Servicio</h3>
                <p>NutriApp es una plataforma integral que permite a los nutricionistas gestionar la información de sus pacientes, crear planes de alimentación personalizados y realizar un seguimiento detallado del progreso. Para los pacientes, ofrece un acceso fácil a sus dietas, la posibilidad de registrar sus comidas y una comunicación directa con su profesional.</p>
            </div>
            <div class="feature col">
                <div class="feature-icon d-inline-flex align-items-center justify-content-center text-bg-primary bg-gradient fs-2 mb-3">
                    <i class="bi bi-people-fill"></i>
                </div>
                <h3 class="fs-2 text-body-emphasis">Sobre el Equipo</h3>
                <p>Somos un equipo de estudiantes de la <strong>UTN Haedo</strong> apasionados por la tecnología y el bienestar. Este proyecto es el resultado de nuestro esfuerzo por aplicar los conocimientos adquiridos para crear una solución real a un problema cotidiano, buscando siempre la calidad y la usabilidad.</p>
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