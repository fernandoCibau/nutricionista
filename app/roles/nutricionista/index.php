<?php
// 1) Iniciar sesión y verificar rol NUTRICIONISTA (rol_id = 2)
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    header('Location: ../../index.php');
    exit;
}

// 2) Datos de sesión para UI
$nombre_usuario = htmlspecialchars($_SESSION['user_nombre'] ?? 'Nutricionista');

// 3) Conexión a BD
require_once '../../config.php';

// --- Carga de datos para la vista ---
$pacientes = [];
$roles = []; // solo 'paciente'
$mensaje = '';
$tipo_mensaje = ''; // success | danger

try {
    // 3.1) Obtener ID (PK) del nutricionista (tabla 'nutricionistas') a partir del usuario logueado
    $stmtNutri = $pdo->prepare("SELECT id FROM nutricionistas WHERE id_usuario = ? LIMIT 1");
    $stmtNutri->execute([$_SESSION['user_id']]);
    $nutri = $stmtNutri->fetch(PDO::FETCH_ASSOC);

    if ($nutri) {
        $idNutricionista = (int)$nutri['id'];

        // 3.2) Listar SOLO pacientes de este nutricionista, con ficha completa
        $sqlPac = "
            SELECT 
                u.id        AS usuario_id,
                u.nombre    AS nombre,
                u.email     AS email,
                u.creado_en AS creado_en,
                p.id        AS paciente_id,
                p.estado    AS estado,
                p.dni       AS dni,
                p.fecha_nacimiento AS fecha_nacimiento,
                p.telefono  AS telefono,
                p.objetivo_principal AS objetivo_principal
            FROM pacientes p
            JOIN usuarios u ON u.id = p.id_usuario
            JOIN roles r    ON r.id = u.role_id
            WHERE p.id_nutricionista = ?
              AND LOWER(r.nombre) = 'paciente'
            ORDER BY u.nombre ASC
        ";
        $st = $pdo->prepare($sqlPac);
        $st->execute([$idNutricionista]);
        $pacientes = $st->fetchAll(PDO::FETCH_ASSOC);
    } else {
        error_log("Nutricionista sin fila en 'nutricionistas' (id_usuario=" . $_SESSION['user_id'] . ")");
        $pacientes = [];
    }

    // 3.3) Roles permitidos para crear (solo paciente)
    $sql_roles = "SELECT id, nombre FROM roles WHERE LOWER(nombre) = 'paciente' ORDER BY nombre ASC";
    $stmt_roles = $pdo->query($sql_roles);
    $roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al obtener pacientes/roles: " . $e->getMessage());
    $pacientes = [];
    $roles = [];
}

// 4) Mensajería por querystring
if (isset($_GET['exito'])) {
    $tipo_mensaje = 'success';
    if ($_GET['exito'] === 'usuario_creado') {
        $mensaje = '¡Paciente creado con éxito! Se envió un email para establecer la contraseña.';
    } elseif ($_GET['exito'] === 'paciente_actualizado' || $_GET['exito'] === 'usuario_actualizado') {
        $mensaje = '¡Paciente actualizado correctamente!';
    } elseif ($_GET['exito'] === 'usuario_eliminado') {
        $mensaje = 'El usuario ha sido eliminado correctamente.';
    }
}
if (isset($_GET['error'])) {
    $tipo_mensaje = 'danger';
    switch ($_GET['error']) {
        case 'email_existente':
            $mensaje = 'Error: El email ya está registrado.';
            break;
        case 'campos_vacios':
        case 'email_invalido':
            $mensaje = 'Error: Verificá que todos los campos sean válidos.';
            break;
        case 'email_fallido':
            $mensaje = 'Error: No se pudo enviar el email de bienvenida. No se creó el usuario.';
            break;
        case 'email_existente_actualizar':
            $mensaje = 'Error al actualizar: El email ingresado ya pertenece a otro usuario.';
            break;
        case 'campos_vacios_actualizar':
        case 'email_invalido_actualizar':
            $mensaje = 'Error al actualizar: Revisá los campos.';
            break;
        case 'password_incorrecta':
            $mensaje = 'Error: La contraseña de administrador es incorrecta. No se eliminó el usuario.';
            break;
        case 'auto_eliminacion':
            $mensaje = 'Error: No podés eliminar tu propia cuenta.';
            break;
        case 'nutri_no_configurado':
            $mensaje = 'Error: Tu usuario no está configurado como nutricionista en el sistema.';
            break;
        case 'sin_permiso_paciente':
            $mensaje = 'Error: No tenés permiso para editar este paciente.';
            break;
        default:
            $mensaje = 'Ha ocurrido un error inesperado. Intentá de nuevo.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Nutricionista - NutriApp</title>

    <!-- Estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="navbar navbar-expand-lg shadow-sm navbar-primary bg-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center text-white" href="#">
                <i class="bi bi-heart-pulse fs-4 me-2"></i>
                <strong>NutriApp - Panel Nutricionista</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-3">
                        <span class="navbar-text text-white">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo $nombre_usuario; ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link logout-link text-white" href="../../logout.php">
                            <i class="bi bi-box-arrow-right"></i><span> Cerrar Sesión</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Contenido -->
    <main class="container my-5">
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($mensaje); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h2 class="h4 mb-0">Mis Pacientes</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus-fill me-2"></i>Agregar Paciente
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Fecha de Registro</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pacientes)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No tenés pacientes asignados todavía.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pacientes as $p): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($p['email']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($p['creado_en'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo ($p['estado'] === 'activo' ? 'bg-success' : 'bg-secondary'); ?>">
                                                <?php echo htmlspecialchars(ucfirst($p['estado'] ?: 'activo')); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button"
                                                    class="btn btn-sm btn-warning me-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editUserModal"
                                                    data-user-id="<?php echo (int)$p['usuario_id']; ?>"
                                                    data-paciente-id="<?php echo (int)$p['paciente_id']; ?>"
                                                    data-user-name="<?php echo htmlspecialchars($p['nombre']); ?>"
                                                    data-user-email="<?php echo htmlspecialchars($p['email']); ?>"
                                                    data-dni="<?php echo htmlspecialchars($p['dni'] ?? ''); ?>"
                                                    data-fecha-nac="<?php echo htmlspecialchars($p['fecha_nacimiento'] ?? ''); ?>"
                                                    data-telefono="<?php echo htmlspecialchars($p['telefono'] ?? ''); ?>"
                                                    data-objetivo="<?php echo htmlspecialchars($p['objetivo_principal'] ?? ''); ?>"
                                                    data-estado="<?php echo htmlspecialchars($p['estado'] ?? 'activo'); ?>"
                                                    title="Editar ficha del paciente">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-sm btn-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteUserModal"
                                                    data-user-id="<?php echo (int)$p['usuario_id']; ?>"
                                                    data-user-name="<?php echo htmlspecialchars($p['nombre']); ?>"
                                                    title="Solicitar eliminación">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal: Agregar Paciente -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Agregar Nuevo Paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
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
                            <label for="add-user-password" class="form-label">Contraseña Temporal</label>
                            <input type="password" class="form-control" id="add-user-password" name="user_password" required>
                        </div>

                        <!-- Rol: solo 'paciente' -->
                        <div class="mb-3">
                            <label for="add-user-role" class="form-label">Rol</label>
                            <select class="form-select" id="add-user-role" name="user_role_id" required>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?php echo (int)$rol['id']; ?>">
                                        <?php echo htmlspecialchars(ucfirst($rol['nombre'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Datos opcionales de paciente -->
                        <div class="mb-3">
                            <label for="add-user-dni" class="form-label">DNI (opcional)</label>
                            <input type="text" class="form-control" id="add-user-dni" name="dni">
                        </div>
                        <div class="mb-3">
                            <label for="add-user-fecha" class="form-label">Fecha de Nacimiento (opcional)</label>
                            <input type="date" class="form-control" id="add-user-fecha" name="fecha_nacimiento">
                        </div>
                        <div class="mb-3">
                            <label for="add-user-telefono" class="form-label">Teléfono (opcional)</label>
                            <input type="text" class="form-control" id="add-user-telefono" name="telefono">
                        </div>
                        <div class="mb-3">
                            <label for="add-user-objetivo" class="form-label">Objetivo Principal (opcional)</label>
                            <input type="text" class="form-control" id="add-user-objetivo" name="objetivo_principal">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Paciente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Editar Paciente (usuario + ficha + estado) -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Editar Paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <!-- Importante: apunta a actualizar_usuario.php -->
                <form action="actualizar_usuario.php" method="POST">
                    <div class="modal-body">
                        <!-- IDs -->
                        <input type="hidden" id="edit-usuario-id" name="usuario_id">
                        <input type="hidden" id="edit-paciente-id" name="paciente_id">

                        <!-- Datos de USUARIO -->
                        <div class="mb-3">
                            <label for="edit-user-name" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="edit-user-name" name="user_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-user-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit-user-email" name="user_email" required>
                        </div>

                        <!-- Datos de PACIENTE -->
                        <div class="mb-3">
                            <label for="edit-dni" class="form-label">DNI</label>
                            <input type="text" class="form-control" id="edit-dni" name="dni">
                        </div>
                        <div class="mb-3">
                            <label for="edit-fecha-nac" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="edit-fecha-nac" name="fecha_nacimiento">
                        </div>
                        <div class="mb-3">
                            <label for="edit-telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="edit-telefono" name="telefono">
                        </div>
                        <div class="mb-3">
                            <label for="edit-objetivo" class="form-label">Objetivo Principal</label>
                            <input type="text" class="form-control" id="edit-objetivo" name="objetivo_principal">
                        </div>

                        <!-- Estado del PACIENTE -->
                        <div class="mb-3">
                            <label for="edit-estado" class="form-label">Estado del paciente</label>
                            <select class="form-select" id="edit-estado" name="estado" required>
                                <option value="activo">Activo (en tratamiento)</option>
                                <option value="alta">Dado de alta</option>
                            </select>
                            <small class="text-muted">Podés volver a “Activo” cuando el paciente retome el tratamiento.</small>
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

    <!-- Modal: Solicitar Eliminación -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Solicitar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form action="eliminar_usuario.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="delete_user_id" id="delete-user-id">
                        <p>Vas a solicitar la eliminación del paciente <strong id="delete-user-name"></strong>. Esta solicitud será revisada por el super administrador.</p>
                        <div class="mb-3">
                            <label for="delete-reason" class="form-label">Motivo (opcional)</label>
                            <textarea class="form-control" id="delete-reason" name="delete_reason" rows="3" placeholder="Explicá por qué solicitás la eliminación..."></textarea>
                        </div>
                        <p class="text-muted">No se eliminará ningún dato automáticamente; el super admin evaluará la solicitud.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Solicitar Eliminación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="text-center text-muted py-4 mt-auto">
        <p>&copy; 2025 Alumnos de UTN Haedo. Todos los derechos reservados.</p>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script para completar los modales con data-* -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Editar: carga campos en el modal
        const editModal = document.getElementById('editUserModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            const btn   = event.relatedTarget;
            const get   = (attr) => btn.getAttribute(attr) || '';

            // IDs
            editModal.querySelector('#edit-usuario-id').value = get('data-user-id');
            editModal.querySelector('#edit-paciente-id').value = get('data-paciente-id');

            // Usuario
            editModal.querySelector('#edit-user-name').value = get('data-user-name');
            editModal.querySelector('#edit-user-email').value = get('data-user-email');

            // Paciente
            editModal.querySelector('#edit-dni').value = get('data-dni');
            editModal.querySelector('#edit-fecha-nac').value = get('data-fecha-nac');
            editModal.querySelector('#edit-telefono').value = get('data-telefono');
            editModal.querySelector('#edit-objetivo').value = get('data-objetivo');

            // Estado
            const estado = get('data-estado') || 'activo';
            const estadoSelect = editModal.querySelector('#edit-estado');
            estadoSelect.value = ['activo','alta'].includes(estado) ? estado : 'activo';
        });

        // Eliminar: completar nombres/ids
        const deleteModal = document.getElementById('deleteUserModal');
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const btn = event.relatedTarget;
            deleteModal.querySelector('#delete-user-id').value = btn.getAttribute('data-user-id');
            deleteModal.querySelector('#delete-user-name').textContent = btn.getAttribute('data-user-name');
        });
    });
    </script>

    <!-- Si tenés lógica adicional, podés mantener tu index.js -->
    <script src="index.js"></script>
</body>
</html>
