<?php
// 1. Iniciar la sesión
session_start();

// 2. Verificar si el usuario está logueado y tiene el rol correcto.
// Si no hay sesión o el rol no es 'superadmin', se redirige al login.
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 1) {
    // Corregir ruta relativa: desde app/roles/super_usuario/ para ir al login en app/index.php

    // Excepción: si estamos suplantando a un usuario, sí permitimos el acceso
    // pero no si intentan acceder directamente al panel de superadmin.
    // Si hay una sesión original de admin, lo redirigimos a su panel correcto.
    if (isset($_SESSION['original_admin_id'])) {
        header('Location: ../../index.php'); // Redirigir a la página de inicio para que se resuelva el rol
        exit;
    }

    header('Location: ../../index.php'); // Redirige a la página de login
    exit;
}

// Obtenemos el nombre del usuario de la sesión para mostrarlo
$nombre_usuario = htmlspecialchars($_SESSION['user_nombre']);

// Incluir el archivo de configuración para la conexión a la BD
require_once '../../config.php';

$usuarios = []; // Inicializar array de usuarios
$roles = [];    // Inicializar array de roles
$nutricionistas_list = []; // Lista de nutricionistas para el modal
$paciente_role_id = null; // ID del rol 'paciente'
$estados_list = []; // Lista de estados para el modal de edición
$filtro_rol = $_GET['rol'] ?? 'todos'; // Por defecto, mostrar todos
$search_query = trim($_GET['q'] ?? ''); // Obtener el término de búsqueda

try {
    // Obtener todos los roles e identificar el ID de 'paciente'
    $stmt_all_roles = $pdo->query("SELECT id, nombre FROM roles");
    $all_roles = $stmt_all_roles->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_roles as $r) {
        if (strtolower($r['nombre']) === 'paciente') {
            $paciente_role_id = $r['id'];
        }
    }

    // Obtener todos los usuarios con rol 'nutricionista' para el dropdown
    $stmt_nutricionistas = $pdo->prepare("SELECT u.id, u.nombre FROM usuarios u JOIN roles r ON u.role_id = r.id WHERE r.nombre = 'nutricionista' ORDER BY u.nombre ASC");
    $stmt_nutricionistas->execute();
    $nutricionistas_list = $stmt_nutricionistas->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todos los estados para el modal de edición
    $stmt_estados = $pdo->query("SELECT id, nombre FROM estados ORDER BY nombre ASC");
    $estados_list = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);

    // Construir la consulta para obtener usuarios, con filtro opcional por rol
    // Agregamos, para pacientes, el nombre de su nutricionista (si existe)
    $sql_usuarios = "
        SELECT 
            u.id, u.nombre, u.email, u.creado_en, u.role_id, u.id_estado,
            r.nombre AS nombre_rol,
            e.nombre AS estado_nombre,
            un.nombre AS nutricionista_nombre,
            p.dni
        FROM usuarios u
        JOIN roles r ON u.role_id = r.id
        LEFT JOIN estados e ON u.id_estado = e.id
        LEFT JOIN pacientes p ON p.id_usuario = u.id
        LEFT JOIN nutricionistas n ON n.id = p.id_nutricionista
        LEFT JOIN usuarios un ON un.id = n.id_usuario";
    
    $where_clauses = [];
    $params = [];

    // Aplicar filtro por rol
    if ($filtro_rol && $filtro_rol !== 'todos') {
        $rol_buscar = $filtro_rol === 'super_usuario' ? 'superadmin' : $filtro_rol;
        $stmt_rol_id = $pdo->prepare("SELECT id FROM roles WHERE nombre = ?");
        $stmt_rol_id->execute([$rol_buscar]);
        $rol_id_obj = $stmt_rol_id->fetch();
        if ($rol_id_obj) {
            $where_clauses[] = "u.role_id = ?";
            $params[] = $rol_id_obj['id'];
        }
    }

    // Aplicar filtro por búsqueda
    if ($search_query !== '') {
        $where_clauses[] = "(u.nombre LIKE ? OR u.email LIKE ? OR p.dni LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }

    if (!empty($where_clauses)) {
        $sql_usuarios .= " WHERE " . implode(' AND ', $where_clauses);
    }

    $sql_usuarios .= " ORDER BY u.nombre ASC";
    
    $stmt_usuarios = $pdo->prepare($sql_usuarios);
    $stmt_usuarios->execute($params);
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
    } elseif ($_GET['error'] === 'nutricionista_requerido') {
        $mensaje = 'Error: Para crear un paciente, debes seleccionar o crear un nutricionista.';
    } elseif ($_GET['error'] === 'campos_nutri_vacios') {
        $mensaje = 'Error: Debes completar todos los campos para el nuevo nutricionista.';
    } elseif ($_GET['error'] === 'email_nutri_invalido') {
        $mensaje = 'Error: El email del nuevo nutricionista no es válido.';
    } elseif ($_GET['error'] === 'auto_eliminacion') {
        $mensaje = 'Error: No puedes eliminar tu propia cuenta de super administrador.';
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

    <!-- Estilos personalizados para mejoras de UI -->
    <style>
        .clickable-row {
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }
        .clickable-row:hover {
            background-color: #f0f8ff; /* Un azul claro suave */
        }
    </style>

    <!-- Dependencias de Estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="public\styles.css"> <!-- CORRECCIÓN: Usar los estilos generales -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

    <!-- Header para el panel de usuario -->
    <header class="navbar navbar-expand-lg shadow-sm navbar-primary bg-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center text-white" href="#">
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
                        <span class="navbar-text text-white">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo $nombre_usuario; ?>
                        </span>
                    </li>
                    <li class="nav-item"> <!-- Botón de cerrar sesión -->
                        <a class="nav-link logout-link text-white" href="../../logout.php">
                            <i class="bi bi-box-arrow-right"></i><span>Cerrar Sesión</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Contenido principal del dashboard -->
    <main class="container my-5">
        <!-- Contenedor para las notificaciones (toasts) -->
        <div class="toast-container position-fixed top-0 end-0 p-3"></div>
        <!-- Tabla de Gestión de Usuarios -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <!-- Lado Izquierdo: Buscador y Filtros -->
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <h2 class="h4 mb-0">Gestión de Usuarios</h2>
                        <!-- Botones de Filtro -->
                        <div class="btn-group" role="group" aria-label="Filtros de rol">
                            <a href="index.php?rol=todos" class="btn <?php echo $filtro_rol === 'todos' ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">Todos</a>
                            <a href="index.php?rol=super_usuario" class="btn <?php echo $filtro_rol === 'super_usuario' ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">Super Admins</a>
                            <a href="index.php?rol=nutricionista" class="btn <?php echo $filtro_rol === 'nutricionista' ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">Nutricionistas</a>
                            <a href="index.php?rol=paciente" class="btn <?php echo $filtro_rol === 'paciente' ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">Pacientes</a>
                        </div>
                    </div>
                    <!-- Lado Derecho: Acciones -->
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <!-- Campo de Búsqueda -->
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="search" id="searchInput" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Buscar por nombre, email, DNI..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-person-plus-fill me-1"></i>
                            Agregar Usuario
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Rol</th>
                                <th>Nutricionista</th>
                                <th>Email</th>
                                <th>Fecha de Registro</th>
                                <th class="text-center" style="width: 180px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No se encontraron usuarios.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                    <td>
                                        <?php 
                                            $estado = $usuario['estado_nombre'] ?? 'N/A';
                                            $badge_class = 'bg-secondary';
                                            if ($estado === 'activo') $badge_class = 'bg-success';
                                            if ($estado === 'pendiente') $badge_class = 'bg-warning text-dark';
                                            if ($estado === 'baja' || $estado === 'inactivo') $badge_class = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars(ucfirst($estado)); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            <?php echo $usuario['nombre_rol'] === 'superadmin' ? 'bg-danger' : ($usuario['nombre_rol'] === 'nutricionista' ? 'bg-info' : 'bg-secondary'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($usuario['nombre_rol'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (strtolower($usuario['nombre_rol']) === 'paciente'): ?>
                                            <?php echo htmlspecialchars($usuario['nutricionista_nombre'] ?? '-'); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($usuario['creado_en'])); ?></td>
                                    <td class="text-center">
                                        <!-- Ocultar botones para el propio superadmin para evitar auto-eliminación -->
                                        <?php if ($_SESSION['user_id'] !== $usuario['id']): ?>
                                        <button type="button" class="btn btn-sm btn-info me-2 view-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewUserModal"
                                                data-user-id="<?php echo $usuario['id']; ?>"
                                                data-user-role-name="<?php echo htmlspecialchars($usuario['nombre_rol']); ?>"
                                                title="Ver Detalles">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning me-2 edit-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editUserModal"
                                                data-user-id="<?php echo $usuario['id']; ?>"
                                                data-user-name="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                                data-user-email="<?php echo htmlspecialchars($usuario['email']); ?>"
                                                data-user-role-id="<?php echo $usuario['role_id']; ?>"
                                                data-user-status-id="<?php echo htmlspecialchars($usuario['id_estado'] ?? ''); ?>"
                                                title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <!-- Botón para suplantar usuario -->
                                        <button type="button" class="btn btn-sm btn-secondary me-2 impersonate-btn"
                                                onclick="location.href='impersonar_usuario.php?id=<?php echo $usuario['id']; ?>'"
                                                title="Suplantar Usuario"
                                                <?php echo ($usuario['nombre_rol'] === 'superadmin') ? 'disabled' : ''; ?>>
                                            <i class="bi bi-person-fill-gear"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteUserModal"
                                                data-user-id="<?php echo $usuario['id']; ?>"
                                                data-user-name="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                                title="Eliminar"><i class="bi bi-trash3"></i></button>
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
                <form id="addUserForm" action="crear_usuario.php" method="POST">
                    <div class="modal-body">
                        <!-- Paso 1: Selección de Rol -->
                        <div id="addUserStep1">
                            <p class="text-muted">Paso 1 de 2: Selecciona el tipo de usuario a crear.</p>
                            <div class="mb-3">
                                <!-- Campo oculto para el ID del rol paciente, se usará en JS -->
                                <input type="hidden" id="paciente-role-id" value="<?php echo $paciente_role_id; ?>">
                                <label for="add-user-role" class="form-label">Rol</label>
                                <select class="form-select" id="add-user-role" name="user_role_id" required>
                                    <option value="" selected disabled>-- Elige un rol --</option>
                                    <?php foreach ($roles as $rol): ?>
                                        <option value="<?php echo $rol['id']; ?>"><?php echo htmlspecialchars(ucfirst($rol['nombre'])); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Paso 2: Asignar Nutricionista (solo para pacientes) -->
                        <div id="addUserStep2_Nutri" style="display: none;">
                            <p class="text-muted">Paso 2 de 3: Asigna el paciente a un nutricionista.</p>
                            <div class="mb-3">
                                <label class="form-label">Nutricionistas Disponibles</label>
                                <?php if (empty($nutricionistas_list)): ?>
                                    <div class="alert alert-warning" role="alert">
                                        No hay nutricionistas disponibles para asignar.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: .375rem;">
                                        <table class="table table-hover mb-0">
                                            <tbody>
                                                <?php foreach ($nutricionistas_list as $nutri): ?>
                                                <tr class="nutri-row" data-nutri-id="<?php echo $nutri['id']; ?>" style="cursor: pointer;">
                                                    <td style="width: 10%;">
                                                        <input class="form-check-input" type="radio" name="nutricionista_id" id="nutri_<?php echo $nutri['id']; ?>" value="<?php echo $nutri['id']; ?>">
                                                    </td>
                                                    <td>
                                                        <label class="form-check-label w-100" for="nutri_<?php echo $nutri['id']; ?>"><?php echo htmlspecialchars($nutri['nombre']); ?></label>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>


                        <!-- Paso 2: Detalles del Usuario -->
                        <div id="addUserStep3" style="display: none;">
                            <p class="text-muted" id="step3-subtitle">Paso 2 de 2: Completa los datos del usuario.</p>
                            <div class="mb-3">
                                <label for="add-user-name" class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="add-user-name" name="user_name">
                            </div>
                            <div class="mb-3">
                                <label for="add-user-email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="add-user-email" name="user_email">
                            </div>
                            <div class="mb-3">
                                <label for="add-user-password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="add-user-password" name="user_password">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-secondary" id="addUserBtnPrev" style="display: none;">Anterior</button>
                        <button type="button" class="btn btn-primary" id="addUserBtnNext">Siguiente</button>
                        <button type="submit" class="btn btn-primary" id="addUserBtnCreate" style="display: none;">Crear Usuario</button>
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

                        <!-- Campo de Estado (solo visible si el rol es paciente o nutricionista) -->
                        <div class="mb-3" id="edit-status-container" style="display: none;">
                            <label for="edit-user-status-id" class="form-label">Estado</label>
                            <select class="form-select" id="edit-user-status-id" name="user_status_id">
                                <option value="">-- Sin Cambiar --</option>
                                <?php foreach ($estados_list as $estado_item): ?>
                                    <option value="<?php echo $estado_item['id']; ?>"><?php echo htmlspecialchars(ucfirst($estado_item['nombre'])); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Estado Clínico del Paciente (solo visible si el rol es paciente) -->
                        <div class="mb-3" id="edit-paciente-estado-container" style="display: none;">
                            <label for="edit-paciente-estado" class="form-label">Estado clínico del paciente</label>
                            <select class="form-select" id="edit-paciente-estado" name="paciente_estado">
                                <option value="">-- Sin Cambiar --</option>
                                <option value="activo">Activo</option>
                                <option value="alta">Alta</option>
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

    <!-- Modal para Ver Detalles de Usuario -->
    <div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewUserModalLabel">Detalles del Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="userInfoContainer">
                        <!-- La información básica del usuario se cargará aquí -->
                    </div>
                    <hr>
                    <div id="userDetailsContainer">
                        <!-- Los detalles adicionales (pacientes/nutri) se cargarán aquí -->
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center text-muted py-4 mt-auto">
        <p>&copy; 2025 Alumnos de UTN Haedo. Todos los derechos reservados.</p>
    </footer>

    <!-- Script de Bootstrap para que funcione el menú hamburguesa -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="index.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.querySelector('table tbody');
        let debounceTimer;

        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const query = searchInput.value;
                
                // Actualizar la URL sin recargar la página
                const url = new URL(window.location);
                url.searchParams.set('q', query);
                // Mantenemos el filtro de rol si existe
                const currentRol = new URLSearchParams(window.location.search).get('rol');
                if (currentRol) {
                    url.searchParams.set('rol', currentRol);
                }
                window.history.pushState({}, '', url);

                if (query.length === 0 || query.length >= 3) {
                    fetchUsers(query);
                }
            }, 300); // Espera 300ms después de que el usuario deja de escribir
        });

        function fetchUsers(query) {
            const rol = new URLSearchParams(window.location.search).get('rol') || 'todos';
            const fetchUrl = `buscar_usuarios.php?q=${encodeURIComponent(query)}&rol=${rol}`;

            fetch(fetchUrl)
                .then(response => response.json())
                .then(data => {
                    renderTable(data.usuarios);
                })
                .catch(error => {
                    console.error('Error al buscar usuarios:', error);
                    tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error al cargar los datos.</td></tr>';
                });
        }

        function renderTable(usuarios) {
            tableBody.innerHTML = ''; // Limpiar la tabla

            if (usuarios.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center">No se encontraron usuarios que coincidan con la búsqueda.</td></tr>';
                return;
            }

            const currentUserId = <?php echo $_SESSION['user_id']; ?>;

            usuarios.forEach(usuario => {
                const estadoBadgeClass = getEstadoBadge(usuario.estado_nombre);
                const rolBadgeClass = getRolBadge(usuario.nombre_rol);

                const accionesHtml = currentUserId !== parseInt(usuario.id) ? `
                    <button type="button" class="btn btn-sm btn-info me-2 view-btn" data-bs-toggle="modal" data-bs-target="#viewUserModal" data-user-id="${usuario.id}" title="Ver Detalles"><i class="bi bi-eye-fill"></i></button>
                    <button type="button" class="btn btn-sm btn-warning me-2 edit-btn" data-bs-toggle="modal" data-bs-target="#editUserModal" data-user-id="${usuario.id}" data-user-name="${usuario.nombre}" data-user-email="${usuario.email}" data-user-role-id="${usuario.role_id}" data-user-status-id="${usuario.id_estado || ''}" title="Editar"><i class="bi bi-pencil-square"></i></button>
                    <button type="button" class="btn btn-sm btn-secondary me-2 impersonate-btn" onclick="location.href='impersonar_usuario.php?id=${usuario.id}'" title="Suplantar Usuario" ${usuario.nombre_rol === 'superadmin' ? 'disabled' : ''}><i class="bi bi-person-fill-gear"></i></button>
                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal" data-user-id="${usuario.id}" data-user-name="${usuario.nombre}" title="Eliminar"><i class="bi bi-trash3"></i></button>
                ` : '';

                const row = `
                    <tr>
                        <td>${usuario.nombre}</td>
                        <td><span class="badge ${estadoBadgeClass}">${ucfirst(usuario.estado_nombre || 'N/A')}</span></td>
                        <td><span class="badge ${rolBadgeClass}">${ucfirst(usuario.nombre_rol)}</span></td>
                        <td>${usuario.nombre_rol.toLowerCase() === 'paciente' ? (usuario.nutricionista_nombre || '-') : '-'}</td>
                        <td>${usuario.email}</td>
                        <td>${new Date(usuario.creado_en).toLocaleDateString('es-ES')}</td>
                        <td class="text-center">${accionesHtml}</td>
                    </tr>
                `;
                tableBody.insertAdjacentHTML('beforeend', row);
            });
        }

        function getEstadoBadge(estado) {
            if (estado === 'activo') return 'bg-success';
            if (estado === 'pendiente') return 'bg-warning text-dark';
            if (estado === 'baja' || estado === 'inactivo') return 'bg-danger';
            return 'bg-secondary';
        }

        function getRolBadge(rol) {
            if (rol === 'superadmin') return 'bg-danger';
            if (rol === 'nutricionista') return 'bg-info';
            return 'bg-secondary';
        }

        function ucfirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
    });
    </script>

    <?php
    // Lógica para mostrar notificaciones como "toasts"
    $toast_mensaje = '';
    $toast_tipo = '';

    if ($mensaje) {
        $toast_mensaje = $mensaje;
        $toast_tipo = $tipo_mensaje;
    } elseif (isset($_GET['exito']) && $_GET['exito'] === 'admin_restaurado') {
        $toast_mensaje = 'Has vuelto a tu sesión de Super Administrador.';
        $toast_tipo = 'success';
    }

    if ($toast_mensaje):
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const toastContainer = document.querySelector('.toast-container');
        const toastMessage = "<?php echo addslashes(htmlspecialchars($toast_mensaje)); ?>";
        const toastType = "<?php echo $toast_tipo; ?>";
        
        const icon = toastType === 'success' ? '<i class="bi bi-check-circle-fill me-2"></i>' : '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
        const toastHTML = `
            <div class="toast align-items-center text-white bg-${toastType} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${icon}
                        ${toastMessage}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.innerHTML = toastHTML;
        const toastEl = toastContainer.querySelector('.toast');
        const toast = new bootstrap.Toast(toastEl, { delay: 5000 }); // El toast durará 5 segundos
        toast.show();
    });
    </script>
    <?php endif; ?>
</body>
</html>
