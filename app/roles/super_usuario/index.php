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
$roles = []; // Inicializar array de roles
try {
    // Consulta para obtener todos los usuarios con su rol
    $sql_usuarios = "
        SELECT 
            u.id, u.nombre, u.email, u.creado_en, u.role_id,
            r.nombre as nombre_rol 
        FROM usuarios u
        JOIN roles r ON u.role_id = r.id
        ORDER BY u.nombre ASC";
    $stmt_usuarios = $pdo->query($sql_usuarios);
    $usuarios = $stmt_usuarios->fetchAll();

    // Consulta para obtener todos los roles (para el dropdown del modal)
    $sql_roles = "SELECT id, nombre FROM roles ORDER BY nombre ASC";
    $stmt_roles = $pdo->query($sql_roles);
    $roles = $stmt_roles->fetchAll();

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
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h2 class="h4 mb-0">Gestión de Usuarios</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus-fill me-2"></i>
                    Agregar Usuario
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Email</th>
                                <th>Fecha de Registro</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No se encontraron usuarios.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php echo $usuario['nombre_rol'] === 'superadmin' ? 'bg-danger' : ($usuario['nombre_rol'] === 'nutricionista' ? 'bg-info' : 'bg-secondary'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($usuario['nombre_rol'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($usuario['creado_en'])); ?></td>
                                    <td class="text-center">
                                        <!-- Ocultar botones para el propio superadmin para evitar auto-eliminación -->
                                        <?php if ($_SESSION['user_id'] !== $usuario['id']): ?>
                                        <button type="button" class="btn btn-sm btn-warning me-2 edit-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editUserModal"
                                                data-user-id="<?php echo $usuario['id']; ?>"
                                                data-user-name="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                                data-user-email="<?php echo htmlspecialchars($usuario['email']); ?>"
                                                data-user-role-id="<?php echo $usuario['role_id']; ?>"
                                                title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="eliminar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Estás seguro de que deseas eliminar a este usuario?');"><i class="bi bi-trash3"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal para Agregar Usuario -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Agregar Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="crear_usuario.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add-user-name" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="add-user-name" name="user_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="add-user-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="add-user-email" name="user_email" required>
                        </div>

                        <div class="mb-3">
                            <label for="add-user-password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="add-user-password" name="user_password" required>
                        </div>

                        <div class="mb-3">
                            <label for="add-user-role" class="form-label">Rol</label>
                            <select class="form-select" id="add-user-role" name="user_role_id" required>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?php echo $rol['id']; ?>"><?php echo htmlspecialchars(ucfirst($rol['nombre'])); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Usuario -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="actualizar_usuario.php" method="POST">
                    <div class="modal-body">
                        <!-- Campo oculto para el ID del usuario -->
                        <input type="hidden" id="edit-user-id" name="user_id">

                        <div class="mb-3">
                            <label for="edit-user-name" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="edit-user-name" name="user_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit-user-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit-user-email" name="user_email" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit-user-role" class="form-label">Rol</label>
                            <select class="form-select" id="edit-user-role" name="user_role_id" required>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?php echo $rol['id']; ?>"><?php echo htmlspecialchars(ucfirst($rol['nombre'])); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="text-center text-muted py-4 mt-auto">
        <p>&copy; 2024 Alumnos de UTN Haedo. Todos los derechos reservados.</p>
    </footer>

    <!-- Script de Bootstrap para que funcione el menú hamburguesa -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="index.js"></script>
</body>
</html>