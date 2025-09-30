<?php
// 1. Iniciar la sesión
session_start();

// 2. Verificar si el usuario está logueado y tiene el rol correcto.
// Si no hay sesión o el rol no es 'superadmin', se redirige al login.
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 1) {
    header('Location: ../index.php'); // Redirige a la página de login
    exit;
}

// Obtenemos el nombre del usuario de la sesión para mostrarlo
$nombre_usuario = htmlspecialchars($_SESSION['user_nombre']);

// Incluir el archivo de configuración para la conexión a la BD
require_once '../../config.php';

$usuarios = []; // Inicializar array de usuarios
try {
    // Preparar y ejecutar la consulta para obtener todos los usuarios
    $stmt = $pdo->query("SELECT id, nombre, email FROM usuarios ORDER BY nombre ASC");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    // En un caso real, aquí se manejaría el error (ej. log)
    // Por ahora, la tabla simplemente aparecerá vacía si hay un error.
    error_log("Error al obtener usuarios: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NutriApp</title>

    <!-- Dependencias de Estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css"> <!-- CORRECCIÓN: Usar los estilos generales -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

    <!-- Header para el panel de usuario -->
    <header class="navbar navbar-expand-lg shadow-sm navbar-primary bg-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="bi bi-heart-pulse fs-4 me-2"></i>
                <strong>NutriApp - Panel Superadmin</strong>
            </a>

            <!-- Botón Hamburguesa para móvil -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Menú Colapsable -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <!-- Nombre de usuario en el header -->
                    <li class="nav-item me-3">
                        <span class="navbar-text">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo $nombre_usuario; ?>
                        </span>
                    </li>
                    <li class="nav-item"> <!-- Botón de cerrar sesión -->
                        <a class="nav-link logout-link" href="../../logout.php">
                            <i class="bi bi-box-arrow-right"></i><span>Cerrar Sesión</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Contenido principal del dashboard -->
    <main class="container my-5">
        <!-- Tabla de Gestión de Usuarios -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h2 class="h4 mb-0">Gestión de Usuarios</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Estado</th>
                                <th>Pago</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td><span class="badge bg-success">Activo</span></td> <!-- Placeholder -->
                                <td><span class="badge bg-primary">Al día</span></td> <!-- Placeholder -->
                                <td class="text-center">
                                    <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-warning me-2" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                    <a href="eliminar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Estás seguro de que deseas eliminar a este usuario?');"><i class="bi bi-trash3"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <footer class="text-center text-muted py-4 mt-auto">
        <p>&copy; 2024 Alumnos de UTN Haedo. Todos los derechos reservados.</p>
    </footer>

    <!-- Script de Bootstrap para que funcione el menú hamburguesa -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>