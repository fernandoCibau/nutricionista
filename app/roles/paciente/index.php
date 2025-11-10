<?php
// 1. Iniciar la sesión
session_start();

// 2. Verificar si el usuario está logueado y tiene el rol correcto.
// Si no hay sesión o el rol no es 'paciente', se redirige al login.
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 3 && $_SESSION['user_rol'] !== 1) {
    // Corregir ruta relativa: desde app/roles/paciente/ para ir al login en app/index.php
    header('Location: ../../index.php'); // Redirige a la página de login
    exit;
}

// Obtenemos el nombre del usuario de la sesión para mostrarlo
$nombre_usuario = htmlspecialchars($_SESSION['user_nombre']);

// Incluir el archivo de configuración para la conexión a la BD
require_once '../../config.php';

// Obtener mapeo de estados
$estados_map = [];
try {
    $stmt_estados = $pdo->query("SELECT id, nombre FROM estados");
    while ($row = $stmt_estados->fetch()) {
        $estados_map[$row['id']] = $row['nombre'];
    }
} catch (PDOException $e) {
    error_log("Error al obtener estados: " . $e->getMessage());
}

// Obtener id interno del paciente (tabla `pacientes`) para usar en las queries
$paciente_id = null;
$paciente_estado = null; // mantenemos la variable para compatibilidad con otras partes del código
try {
    // Debug session
    error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'no definido'));
    
    // Seleccionamos solo columnas existentes: id.
    $pstmt = $pdo->prepare("SELECT id FROM pacientes WHERE id_usuario = ? LIMIT 1");
    $pstmt->execute([$_SESSION['user_id']]);
    $prow = $pstmt->fetch();
    if ($prow) {
        $paciente_id = $prow['id'];
        error_log("Paciente ID encontrado: " . $paciente_id);
    } else {
        error_log('Paciente no encontrado para user_id=' . $_SESSION['user_id']);
        
        // Debug adicional
        $check = $pdo->prepare("SELECT COUNT(*) FROM pacientes");
        $check->execute();
        $total = $check->fetchColumn();
        error_log("Total de pacientes en la tabla: " . $total);
    }
} catch (PDOException $e) {
    error_log("Error al obtener paciente: " . $e->getMessage());
}

// Cargar las comidas recientes del paciente (tabla `diario`)
$comidas = [];
try {
    if ($paciente_id !== null) {
        $stmt = $pdo->prepare("SELECT id, id_paciente, fecha_hora, tipo_comida, detalles, url_foto FROM diario WHERE id_paciente = ? ORDER BY fecha_hora DESC LIMIT 10");
        $stmt->execute([$paciente_id]);
        $comidas = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Error al obtener comidas (diario): " . $e->getMessage());
}

// Obtener próximos turnos programados (todos los futuros)
$turnos_programados = [];
try {
    if ($paciente_id !== null) {
        error_log("DEBUG: Paciente ID para turnos: " . $paciente_id);
        $sql_turnos = "SELECT t.id, t.id_nutricionista, t.id_paciente, t.fecha_hora, e.nombre AS estado, t.senia, t.pagado, t.monto, t.creado_en FROM turnos t LEFT JOIN estados e ON t.id_estado = e.id WHERE t.id_paciente = ? AND t.fecha_hora > NOW() AND e.nombre IN ('programado','pendiente') ORDER BY t.fecha_hora ASC";
        error_log("DEBUG: SQL Turnos: " . $sql_turnos);
        $stmt = $pdo->prepare($sql_turnos);
        $stmt->execute([$paciente_id]);
        $turnos_programados = $stmt->fetchAll();
        error_log("DEBUG: Turnos programados encontrados: " . count($turnos_programados));
    }
} catch (PDOException $e) {
    error_log("Error al obtener turnos: " . $e->getMessage());
}

// Debug de sesión y conexión
echo "<!-- Debug:\n";
echo "SESSION user_id: " . ($_SESSION['user_id'] ?? 'no definido') . "\n";
echo "SESSION rol: " . ($_SESSION['user_rol'] ?? 'no definido') . "\n";
echo "-->\n";

// Cargar hábitos asignados con estadísticas
$habitos = [];
try {
    // Primero verificar que tenemos el id_paciente
    if ($paciente_id === null) {
        echo "<!-- Error: No se pudo obtener el id_paciente -->\n";
    }

    $check = $pdo->query("SHOW TABLES LIKE 'habitos'");
    if ($check && $check->rowCount() > 0 && $paciente_id !== null) {
        // Debug de la tabla habitos
        $total = $pdo->query("SELECT COUNT(*) FROM habitos")->fetchColumn();
        echo "<!-- Total hábitos en la tabla: $total -->\n";
        
        // Contar hábitos para este paciente
        $count = $pdo->prepare("SELECT COUNT(*) FROM habitos WHERE id_paciente = ?");
        $count->execute([$paciente_id]);
        $totalPaciente = $count->fetchColumn();
        echo "<!-- Hábitos para paciente_id=$paciente_id: $totalPaciente -->\n";
        
        // Determinar nombres de columna (compatibilidad entre esquemas antiguos/nuevos)
        $descCol = null;
        $createdCol = null;
        $c = $pdo->query("SHOW COLUMNS FROM habitos")->fetchAll(PDO::FETCH_COLUMN);
        echo "<!-- Columnas encontradas: " . implode(', ', $c) . " -->\n";
        
        if (in_array('descripcion', $c)) $descCol = 'descripcion';
        elseif (in_array('nombre', $c)) $descCol = 'nombre';

        if (in_array('creado_en', $c)) $createdCol = 'creado_en';
        elseif (in_array('creado', $c)) $createdCol = 'creado';
        elseif (in_array('created_at', $c)) $createdCol = 'created_at';

        // Construir SELECT dinámico
        $selectDesc = $descCol ? "$descCol AS descripcion" : "'' AS descripcion";
        $selectCreated = $createdCol ? "$createdCol AS creado_en" : "NULL AS creado_en";
        $orderBy = $createdCol ? "$createdCol DESC" : "id DESC";

        // Construir SQL usando las columnas correctas que vimos en SHOW COLUMNS
        $sql = "SELECT 
            id, 
            $selectDesc, 
            $selectCreated, 
            COALESCE(racha_dias, 0) AS racha_actual,
            color,
            creado_por 
        FROM habitos 
        WHERE id_paciente = ? 
        ORDER BY $orderBy";
        
        echo "<!-- SQL ejecutado: " . str_replace('?', $paciente_id, $sql) . " -->\n";
        
        $hstmt = $pdo->prepare($sql);
        $hstmt->execute([$paciente_id]);
        $habitos = $hstmt->fetchAll();
        
        echo "<!-- Hábitos obtenidos: " . count($habitos) . " -->\n";
        if (count($habitos) > 0) {
            echo "<!-- Primer hábito: ";
            print_r($habitos[0]);
            echo " -->\n";
        }
        
        // Calcular completados de la semana para cada hábito
        $fechaInicio = date('Y-m-d', strtotime('-6 days')); // 7 días incluyendo hoy
        $fechaFin = date('Y-m-d');
        
        foreach ($habitos as &$h) {
            // Obtener completados de la última semana
            $stmt = $pdo->prepare("SELECT COUNT(*) as completados_semana FROM habit_completados 
                                 WHERE id_habito = ? AND fecha BETWEEN ? AND ?");
            $stmt->execute([$h['id'], $fechaInicio, $fechaFin]);
            $stats = $stmt->fetch();
            $h['completados_semana'] = $stats['completados_semana'] ?? 0;
            
            // Obtener total de veces completado
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM habit_completados WHERE id_habito = ?");
            $stmt->execute([$h['id']]);
            $total = $stmt->fetch();
            $h['veces_completado'] = $total['total'] ?? 0;
        }
        unset($h); // romper la referencia
    }
} catch (PDOException $e) {
    error_log("Error al obtener hábitos: " . $e->getMessage());
}

// Preparar array de completados hoy
$completados_hoy = [];
try {
    if ($paciente_id !== null) {
        $fecha_hoy = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT id_habito FROM habit_completados 
                              WHERE id_paciente = ? AND fecha = ?");
        $stmt->execute([$paciente_id, $fecha_hoy]);
        while ($row = $stmt->fetch()) {
            $completados_hoy[$row['id_habito']] = true;
        }
    }
} catch (PDOException $e) {
    error_log("Error al obtener completados_hoy: " . $e->getMessage());
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

// Cargar archivos (PDFs) subidos por el nutricionista para este paciente (tabla `archivos_plan`)
$archivos_plan = [];
try {
    if ($paciente_id !== null) {
        $af = $pdo->prepare("SELECT id, nombre_archivo, url_archivo, fecha_subida FROM archivos_plan WHERE id_paciente = ? ORDER BY fecha_subida DESC");
        $af->execute([$paciente_id]);
        $archivos_plan = $af->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Error al obtener archivos_plan: " . $e->getMessage());
}

// (estado del paciente ya se obtuvo más arriba en la variable $paciente_estado)

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
    <link rel="stylesheet" href="../../public/styles.css"> <!-- CORRECCIÓN: Usar los estilos generales -->
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
                <strong>NutriApp - Panel Paciente</strong>
            </a>

            <!-- Botón Hamburguesa para móvil -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Menú Colapsable -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center text-white">
                    <!-- Nombre de usuario en el header -->
                    <li class="nav-item me-3 text-white">
                        <span class="navbar-text text-white">
                            <i class="bi bi-person-circle me-1 text-white"></i>
                            <?php echo $nombre_usuario; ?>
                        </span>
                    </li>
                    <li class="nav-item me-2">
                        <a class="nav-link text-white" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="bi bi-key-fill"></i><span class="ms-1">Cambiar Contraseña</span>
                        </a>
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
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100"></div>

<<<<<<< Updated upstream
<<<<<<< Updated upstream
        <?php if (isset($_SESSION['original_admin_id'])): ?>
=======
       <?php if (isset($_SESSION['original_admin_id'])): ?>
>>>>>>> Stashed changes
=======
       <?php if (isset($_SESSION['original_admin_id'])): ?>
>>>>>>> Stashed changes
            <div class="alert alert-warning border-warning d-flex justify-content-between align-items-center mb-4" role="alert">
                <div>
                    <i class="bi bi-person-fill-gear me-2"></i>
                    Estás suplantando a <strong><?php echo htmlspecialchars($_SESSION['user_nombre']); ?></strong>.
                </div>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                <a href="../super_usuario/volver_admin.php" class="btn btn-warning fw-bold">Volver a mi sesión (<?php echo htmlspecialchars($_SESSION['original_admin_nombre'] ?? 'Admin'); ?>)</a>
=======
                 <a href="../super_usuario/volver_admin.php" class="btn btn-warning fw-bold">Volver a mi sesión (<?php echo htmlspecialchars($_SESSION['original_admin_nombre'] ?? 'Admin'); ?>)</a>
>>>>>>> Stashed changes
=======
                 <a href="../super_usuario/volver_admin.php" class="btn btn-warning fw-bold">Volver a mi sesión (<?php echo htmlspecialchars($_SESSION['original_admin_nombre'] ?? 'Admin'); ?>)</a>
>>>>>>> Stashed changes
            </div>
        <?php elseif (isset($_SESSION['original_nutri_id'])): ?>
            <div class="alert alert-info border-info d-flex justify-content-between align-items-center mb-4" role="alert">
                <div>
                    <i class="bi bi-person-fill-gear me-2"></i>
                    Estás viendo el panel como <strong><?php echo htmlspecialchars($_SESSION['user_nombre']); ?></strong>.
                </div>
                <a href="../nutricionista/volver_nutri.php" class="btn btn-info fw-bold">Volver a mi sesión (<?php echo htmlspecialchars($_SESSION['original_nutri_nombre'] ?? 'Nutricionista'); ?>)</a>
            </div>
        <?php endif; ?>

        <?php if ($paciente_estado === 'alta'): ?>
            <div class="alert alert-warning" role="alert">
                Tu historia clínica está en estado <strong>ALTA</strong> y no puede ser editada desde tu cuenta. Si necesitás cambios, contactá a tu nutricionista o al administrador.
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 1): ?>
        <div class="mb-4">
            <a href="../super_usuario/index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left-circle me-2"></i>Volver al Panel de Super Admin
            </a>
        </div>
        <?php endif; ?>

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
                        <?php // DEBUG: insertar comentario HTML con información útil para diagnóstico ?>
                        <?php
                        $session_uid = $_SESSION['user_id'] ?? 'null';
                        $patient_id_dbg = $paciente_id ?? 'null';
                        // Contar filas en diario para todo y para este paciente
                        try {
                            $total_diario = (int)$pdo->query("SELECT COUNT(*) FROM diario")->fetchColumn();
                            $diario_paciente = 0;
                            if ($patient_id_dbg !== 'null') {
                                $ct = $pdo->prepare("SELECT COUNT(*) FROM diario WHERE id_paciente = ?");
                                $ct->execute([(int)$patient_id_dbg]);
                                $diario_paciente = (int)$ct->fetchColumn();
                            }
                        } catch (Throwable $e) {
                            $total_diario = -1;
                            $diario_paciente = -1;
                        }
                        $first_urls = array_map(function($r){ return $r['url_foto'] ?? null; }, array_slice($comidas,0,5));
                        echo "<!--DEBUG: session_user_id={$session_uid}; paciente_id={$patient_id_dbg}; comidas_count=" . count($comidas) . "; diario_total={$total_diario}; diario_paciente={$diario_paciente}; urls=" . htmlspecialchars(json_encode($first_urls)) . "-->";
                        ?>
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
                                <label for="fecha_comida" class="form-label">Fecha de la comida</label>
                                <input type="date" class="form-control" id="fecha_comida" name="fecha_comida" value="<?php echo date('Y-m-d'); ?>" required>
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
                        <h3 class="h5 mb-0">Turnos Programados</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($turnos_programados)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($turnos_programados as $t): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-start">
                                        <div>
                                            <div><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($t['fecha_hora'])); ?></div>
                                            <div class="small text-muted">Estado: <?php echo htmlspecialchars(ucfirst($t['estado'])); ?> • Seña: <?php echo htmlspecialchars($t['senia'] ?? 'N/A'); ?> • Pagado: <?php echo !empty($t['pagado']) ? 'Sí' : 'No'; ?></div>
                                        </div>
                                        <div class="text-end">
                                            <form action="cancelar_turno.php" method="POST" onsubmit="return confirm('¿Seguro que deseas cancelar este turno?');" style="display:inline">
                                                <input type="hidden" name="fecha_hora" value="<?php echo htmlspecialchars($t['fecha_hora']); ?>">
                                                <input type="hidden" name="id_nutricionista" value="<?php echo htmlspecialchars($t['id_nutricionista']); ?>">
                                                <button class="btn btn-sm btn-danger">Cancelar</button>
                                            </form>
                                            <!-- Reprogramar: mostrar formulario pequeño -->
                                            <button class="btn btn-sm btn-secondary ms-2" type="button" onclick="document.getElementById('reprog-<?php echo $t['id']; ?>').classList.toggle('d-none')">Reprogramar</button>
                                            <div id="reprog-<?php echo $t['id']; ?>" class="mt-2 d-none">
                                                <form action="reprogramar_turno.php" method="POST" class="d-flex gap-2 align-items-center">
                                                    <input type="hidden" name="turno_id" value="<?php echo $t['id']; ?>">
                                                    <input type="datetime-local" name="nueva_fecha" class="form-control form-control-sm" required>
                                                    <button class="btn btn-sm btn-primary">Enviar</button>
                                                </form>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">Tiempo de agendar una nueva consulta.</p>
                            <!-- Botón para solicitar turno al nutricionista -->
                            <div class="mt-3">
                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#solicitarTurno" aria-expanded="false" aria-controls="solicitarTurno">Solicitar turno a mi nutricionista</button>
                                <div class="collapse mt-3" id="solicitarTurno">
                                    <div class="card card-body">
                                        <form action="enviar_notificacion_nutricionista.php" method="POST">
                                            <div class="mb-3">
                                                <label for="preferencia_fecha" class="form-label">Fecha/horario preferido (opcional)</label>
                                                <input type="datetime-local" id="preferencia_fecha" name="preferencia_fecha" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label for="mensaje_solicitud" class="form-label">Mensaje (opcional)</label>
                                                <textarea id="mensaje_solicitud" name="mensaje" class="form-control" rows="3">Hola, me gustaría coordinar un turno.</textarea>
                                            </div>
                                            <button class="btn btn-primary">Enviar solicitud</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="h6 mb-0">Dieta</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($dieta): ?>
                            <p class="mb-2">Última receta pública: <?php echo date('d/m/Y', strtotime($dieta['creado_en'])); ?></p>
                            <a href="descargar_dieta.php?titulo=<?php echo urlencode($dieta['titulo']); ?>&creado_en=<?php echo urlencode($dieta['creado_en']); ?>" class="btn btn-outline-primary">Descargar receta (.txt)</a>
                        <?php endif; ?>

                        <hr />
                        <h6 class="mb-2">Archivos del nutricionista</h6>
                        <?php if (empty($archivos_plan)): ?>
                            <p class="text-muted">No hay archivos (PDF) asignados a tu plan.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($archivos_plan as $ap): ?>
                                    <?php
                                        // Construir URL pública usando APP_BASE (archivo guardado como 'uploads/dietas/xxx.pdf')
                                        $url = (defined('APP_BASE') ? APP_BASE : '/nutricionista') . '/' . ltrim($ap['url_archivo'], '/');
                                        $safeName = htmlspecialchars($ap['nombre_archivo'] ?: basename($ap['url_archivo']));
                                        $dt = date('d/m/Y H:i', strtotime($ap['fecha_subida']));
                                    ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold"><?php echo $safeName; ?></div>
                                            <div class="small text-muted"><?php echo $dt; ?></div>
                                        </div>
                                        <div class="text-end">
                                            <a class="btn btn-sm btn-outline-primary me-2" href="<?php echo $url; ?>" target="_blank" download>Descargar</a>
                                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo $url; ?>" target="_blank">Abrir</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Incluir componente de hábitos -->
                <?php include __DIR__ . '/components/card_habitos.php'; ?>
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
                                    <?php if (!empty($c['url_foto'])): ?>
                                        <div class="col-6 col-md-4">
                                            <div class="gallery-tile p-0 border" style="height:200px;overflow:hidden;">
                                                <img src="<?php echo (defined('APP_BASE') ? APP_BASE : '/nutricionista') . htmlspecialchars($c['url_foto']); ?>" alt="comida" style="width:100%;height:100%;object-fit:cover;">
                                            </div>
                                            <div class="mt-1 small text-muted">
                                                <span class="fw-semibold text-capitalize"><?php echo htmlspecialchars($c['tipo_comida']); ?></span>
                                                <span class="ms-2"><i class="bi bi-clock"></i> <?php echo date('H:i', strtotime($c['fecha_hora'])); ?></span>
                                            </div>
                                            <?php if (!empty($c['detalles'])): ?>
                                                <p class="mb-0 mt-1 small"><?php echo nl2br(htmlspecialchars($c['detalles'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="card-title text-capitalize"><?php echo htmlspecialchars($c['tipo_comida']); ?> <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($c['fecha_hora'])); ?></small></h6>
                                                    <?php if (!empty($c['detalles'])): ?>
                                                        <p class="card-text"><?php echo nl2br(htmlspecialchars($c['detalles'])); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal para Agregar Usuario -->
    <?php if ($paciente_estado !== 'alta'): // Ocultar modales de gestión de usuarios si el paciente está de alta ?>
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
    <?php endif; ?>

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

    <!-- Footer con el copyright -->
    <footer class="login-footer">
        <p>&copy; 2025 Alumnos de UTN Haedo. Todos los derechos reservados.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/habitos.js"></script>
    <script src="js/habitos_calendar.js"></script>
    <script src="index.js"></script>
</body>
</html>