<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    header('Location: ../../index.php');
    exit;
}

require_once __DIR__ . '/../../config.php';

$id_paciente = filter_var($_GET['id_paciente'] ?? '', FILTER_VALIDATE_INT);
if ($id_paciente === false) {
    header('Location: index.php?error=paciente_no_encontrado');
    exit;
}

// Verificar pertenencia del paciente
$stmtUser = $pdo->prepare("SELECT u.id, u.nombre, u.apellido, u.assigned_nutricionista_id FROM usuarios u WHERE u.id = ? LIMIT 1");
$stmtUser->execute([$id_paciente]);
$paciente = $stmtUser->fetch();

if (!$paciente || $paciente['assigned_nutricionista_id'] != $_SESSION['user_id']) {
    header('Location: index.php?error=no_autorizado');
    exit;
}

// Obtener hábitos actuales del paciente
$habitos = [];
try {
    $hstmt = $pdo->prepare("SELECT h.*, COALESCE(COUNT(hc.id), 0) as veces_completado 
                           FROM habitos h 
                           LEFT JOIN habit_completados hc ON h.id = hc.id_habito 
                           WHERE h.id_paciente = ? 
                           GROUP BY h.id 
                           ORDER BY h.creado_en DESC");
    $hstmt->execute([$id_paciente]);
    $habitos = $hstmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error al obtener hábitos: " . $e->getMessage());
}

// Obtener estadísticas de cumplimiento de la última semana
$stats = [];
try {
    $sstmt = $pdo->prepare("
        SELECT h.id, h.descripcion,
               COUNT(DISTINCT hc.fecha) as dias_completados,
               DATEDIFF(CURDATE(), DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as total_dias
        FROM habitos h
        LEFT JOIN habit_completados hc ON h.id = hc.id_habito
            AND hc.fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()
        WHERE h.id_paciente = ?
        GROUP BY h.id
    ");
    $sstmt->execute([$id_paciente]);
    $stats = $sstmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener estadísticas: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Hábitos - <?php echo htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestionar Hábitos - <?php echo htmlspecialchars($paciente['nombre'] . ' ' . $paciente['apellido']); ?></h2>
            <a href="vista_paciente.php?id=<?php echo $id_paciente; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>

        <?php if (isset($_GET['exito'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                    $mensaje = match($_GET['exito']) {
                        'habito_agregado' => 'Hábito agregado correctamente.',
                        'habito_eliminado' => 'Hábito eliminado correctamente.',
                        default => 'Operación realizada con éxito.'
                    };
                    echo $mensaje;
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php
                    $mensaje = match($_GET['error']) {
                        'campos_vacios' => 'Por favor complete todos los campos.',
                        'db_error' => 'Error en la base de datos. Por favor intente nuevamente.',
                        default => 'Error al procesar la solicitud.'
                    };
                    echo $mensaje;
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <!-- Formulario para agregar nuevo hábito -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Agregar Nuevo Hábito</h5>
                    </div>
                    <div class="card-body">
                        <form action="agregar_habito.php" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="id_paciente" value="<?php echo $id_paciente; ?>">
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción del Hábito</label>
                                <input type="text" class="form-control" id="descripcion" name="descripcion" required
                                       placeholder="Ej: Tomar 2 litros de agua">
                                <div class="invalid-feedback">
                                    Por favor ingrese una descripción para el hábito.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Agregar Hábito</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <!-- Estadísticas de cumplimiento -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Estadísticas (Última Semana)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($stats)): ?>
                            <p class="text-muted">No hay estadísticas disponibles.</p>
                        <?php else: ?>
                            <?php foreach ($stats as $stat): ?>
                                <div class="mb-3">
                                    <p class="mb-1"><?php echo htmlspecialchars($stat['descripcion']); ?></p>
                                    <div class="progress">
                                        <?php
                                            $porcentaje = ($stat['dias_completados'] / 7) * 100;
                                            $color = match(true) {
                                                $porcentaje >= 75 => 'bg-success',
                                                $porcentaje >= 50 => 'bg-warning',
                                                default => 'bg-danger'
                                            };
                                        ?>
                                        <div class="progress-bar <?php echo $color; ?>" role="progressbar" 
                                             style="width: <?php echo $porcentaje; ?>%" 
                                             aria-valuenow="<?php echo $porcentaje; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?php echo $stat['dias_completados']; ?>/7 días
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de hábitos actuales -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Hábitos Actuales</h5>
            </div>
            <div class="card-body">
                <?php if (empty($habitos)): ?>
                    <p class="text-muted">No hay hábitos registrados.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Hábito</th>
                                    <th>Fecha de Creación</th>
                                    <th>Veces Completado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($habitos as $habito): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($habito['descripcion']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($habito['creado_en'])); ?></td>
                                        <td><?php echo $habito['veces_completado']; ?></td>
                                        <td>
                                            <form action="eliminar_habito.php" method="POST" class="d-inline" 
                                                  onsubmit="return confirm('¿Está seguro de eliminar este hábito?');">
                                                <input type="hidden" name="id_habito" value="<?php echo $habito['id']; ?>">
                                                <input type="hidden" name="id_paciente" value="<?php echo $id_paciente; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación de formularios de Bootstrap
        (function() {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>