<?php
// 1. Iniciar la sesión
session_start();

// 2. Verificar si el usuario está logueado y tiene el rol correcto.
// Si no hay sesión o el rol no es 'paciente', se redirige al login.
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 3) {
    // Corregir ruta relativa: desde app/roles/paciente/ para ir al login en app/index.php
    header('Location: ../../index.php'); // Redirige a la página de login
    exit;
}

// Obtenemos el nombre del usuario de la sesión para mostrarlo
$nombre_usuario = htmlspecialchars($_SESSION['user_nombre']);

// Incluir el archivo de configuración para la conexión a la BD
require_once '../../config.php';

// Cargar las comidas recientes del paciente (tabla `diario`)
$comidas = [];
try {
    $stmt = $pdo->prepare("SELECT id, id_paciente, fecha_hora, tipo_comida, detalles, url_foto FROM diario WHERE id_paciente = ? ORDER BY fecha_hora DESC LIMIT 10");
    $stmt->execute([$_SESSION['user_id']]);
    $comidas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error al obtener comidas (diario): " . $e->getMessage());
}

// Obtener próximo turno programado
$proximo_turno = null;
try {
    $stmt = $pdo->prepare("SELECT id, id_nutricionista, id_paciente, fecha_hora, estado, senia, pagado, creado_en FROM turnos WHERE id_paciente = ? AND fecha_hora > NOW() AND estado = 'programado' ORDER BY fecha_hora ASC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $proximo_turno = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error al obtener turno: " . $e->getMessage());
}

// Obtener la receta/dieta más reciente (tabla `recetas`) — las recetas están asociadas a nutricionistas,
// si la relación paciente->receta no existe en tu modelo deberías adaptar esto.
$dieta = null;
try {
    // Intento obtener una receta publicada (ejemplo simple: se listan las recetas publicadas)
    $stmt = $pdo->prepare("SELECT id, id_nutricionista, titulo, contenido, creado_en FROM recetas WHERE publicado = 1 ORDER BY creado_en DESC LIMIT 1");
    $stmt->execute();
    $dieta = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error al obtener receta (recetas): " . $e->getMessage());
}

// Manejo de mensajes de éxito y error desde la URL
$mensaje = '';
$tipo_mensaje = ''; // 'success' o 'danger'

if (isset($_GET['exito'])) {
    if ($_GET['exito'] === 'usuario_creado') {
        $mensaje = '¡Usuario creado con éxito! Se ha enviado un email para que establezca su contraseña.';
        $tipo_mensaje = 'success';
    }
    if ($_GET['exito'] === 'usuario_actualizado') {
        $mensaje = '¡Usuario actualizado correctamente!';
        $tipo_mensaje = 'success';
    }
    if ($_GET['exito'] === 'usuario_eliminado') {
        $mensaje = 'El usuario ha sido eliminado correctamente.';
        $tipo_mensaje = 'success';
    }
}

if (isset($_GET['error'])) {
    $tipo_mensaje = 'danger';
    if ($_GET['error'] === 'email_existente') {
        $mensaje = 'Error: El email ingresado ya está registrado en el sistema.';
    } elseif ($_GET['error'] === 'campos_vacios' || $_GET['error'] === 'email_invalido') {
        $mensaje = 'Error: Por favor, verifica que todos los campos estén completos y sean válidos.';
    } elseif ($_GET['error'] === 'email_fallido') {
        $mensaje = 'Error: No se pudo enviar el email de bienvenida. El usuario no fue creado para evitar inconsistencias.';
    } elseif ($_GET['error'] === 'email_existente_actualizar') {
        $mensaje = 'Error al actualizar: El email ingresado ya pertenece a otro usuario.';
    } elseif ($_GET['error'] === 'campos_vacios_actualizar' || $_GET['error'] === 'email_invalido_actualizar') {
        $mensaje = 'Error al actualizar: Por favor, verifica que todos los campos estén completos y sean válidos.';
    } elseif ($_GET['error'] === 'password_incorrecta') {
        $mensaje = 'Error: La contraseña de administrador es incorrecta. No se eliminó el usuario.';
    } elseif ($_GET['error'] === 'auto_eliminacion') {
        $mensaje = 'Error: No puedes eliminar tu propia cuenta de paciente.';
    } else {
        $mensaje = 'Ha ocurrido un error inesperado en la base de datos. Por favor, intente de nuevo.';
    }
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
                <strong>NutriApp - Panel Paciente</strong>
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
                    <li class="nav-item me-2">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="bi bi-key-fill"></i><span class="ms-1">Cambiar Contraseña</span>
                        </a>
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
        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Dashboard Paciente: Subir comidas, ver instrucciones, turno y dieta -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="h5 mb-0">Registrar Comida</h3>
                        <small class="text-muted">Comparte tus fotos y comentarios</small>
                    </div>
                    <div class="card-body">
                        <form action="subir_comida.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="foto" class="form-label">Foto de la comida</label>
                                <input class="form-control" type="file" id="foto" name="foto" accept="image/*" required>
                            </div>
                            <div class="mb-3">
                                <label for="comentario" class="form-label">Comentario</label>
                                <textarea class="form-control" id="comentario" name="comentario" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="tipo_comida" class="form-label">Tipo de comida</label>
                                <select class="form-select" id="tipo_comida" name="tipo_comida" required>
                                    <option value="desayuno">Desayuno</option>
                                    <option value="almuerzo">Almuerzo</option>
                                    <option value="merienda">Merienda</option>
                                    <option value="cena">Cena</option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <button class="btn btn-primary" type="submit">Subir</button>
                                <small class="text-muted">Máx. 5MB. Imágenes JPG, PNG, WEBP</small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="h5 mb-0">Próximo Turno</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($proximo_turno): ?>
                            <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($proximo_turno['fecha_hora'])); ?></p>
                            <div class="mb-2">
                                <strong>Seña:</strong> <?php echo htmlspecialchars($proximo_turno['senia'] ?? 'N/A'); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Pagado:</strong> <?php echo !empty($proximo_turno['pagado']) ? 'Sí' : 'No'; ?>
                            </div>
                            <form action="cancelar_turno.php" method="POST" onsubmit="return confirm('¿Seguro que deseas cancelar este turno?');">
                                <input type="hidden" name="fecha_hora" value="<?php echo htmlspecialchars($proximo_turno['fecha_hora']); ?>">
                                <input type="hidden" name="id_nutricionista" value="<?php echo htmlspecialchars($proximo_turno['id_nutricionista']); ?>">
                                <button class="btn btn-danger">Cancelar Turno</button>
                            </form>
                        <?php else: ?>
                            <p class="text-muted">No tienes turnos programados.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="h6 mb-0">Dieta</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($dieta): ?>
                            <p class="mb-2">Última receta: <?php echo date('d/m/Y', strtotime($dieta['creado_en'])); ?></p>
                            <a href="descargar_dieta.php?titulo=<?php echo urlencode($dieta['titulo']); ?>&creado_en=<?php echo urlencode($dieta['creado_en']); ?>" class="btn btn-outline-primary">Descargar Dieta</a>
                        <?php else: ?>
                            <p class="text-muted">Aún no hay recetas públicas disponibles.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="h5 mb-0">Tus últimas comidas</h3>
                        <small class="text-muted">Hasta 10 registros</small>
                    </div>
                    <div class="card-body">
                        <?php if (empty($comidas)): ?>
                            <p class="text-muted">No has registrado comidas aún.</p>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($comidas as $c): ?>
                                    <div class="col-sm-6 col-md-4">
                                        <div class="card h-100">
                                            <?php if (!empty($c['url_foto'])): ?>
                                                <img src="../../..<?php echo htmlspecialchars($c['url_foto']); ?>" class="card-img-top" alt="Foto comida">
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <h6 class="card-title text-capitalize"><?php echo htmlspecialchars($c['tipo_comida']); ?></h6>
                                                <p class="card-text small text-muted"><?php echo date('d/m/Y H:i', strtotime($c['fecha_hora'])); ?></p>
                                                <?php if (!empty($c['detalles'])): ?>
                                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($c['detalles'])); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
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

    <!-- Modal para Confirmar Eliminación -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="eliminar_usuario.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="delete_user_id" id="delete-user-id">
                        <p>¿Estás seguro de que deseas eliminar permanentemente al usuario <strong id="delete-user-name"></strong>?</p>
                        
                        <div class="my-3">
                            <label for="admin-password" class="form-label fw-bold">Ingresa tu contraseña para confirmar</label>
                            <input type="password" class="form-control" id="admin-password" name="admin_password" required>
                        </div>

                        <p class="text-danger">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Sí, Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="text-center text-muted py-4 mt-auto">
        <p>&copy; 2025 Alumnos de UTN Haedo. Todos los derechos reservados.</p>
    </footer>

    <!-- Modal para Cambiar Contraseña -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Cambiar Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="change-password-form" action="cambiar_password.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Contraseña actual</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nueva contraseña</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password_confirm" class="form-label">Confirmar nueva contraseña</label>
                            <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Cambiar contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Script de Bootstrap para que funcione el menú hamburguesa -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="index.js"></script>
</body>
</html>