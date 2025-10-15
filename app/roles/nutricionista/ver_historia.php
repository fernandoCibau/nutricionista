<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    header('Location: ../../index.php');
    exit;
}

require_once __DIR__ . '/../../config.php';

$id_paciente = filter_var($_GET['id_paciente'] ?? '', FILTER_VALIDATE_INT);
if ($id_paciente === false) {
    echo "<p>Paciente inválido.</p>";
    exit;
}

// Comprobar existencia de tabla historiales
$check = $pdo->query("SHOW TABLES LIKE 'historias'");
if (!$check || $check->rowCount() === 0) {
    echo "<p>No existe la tabla 'historias'. Contacta al administrador para activar la historia clínica.</p>";
    exit;
}

try {
    // Verificar que el paciente pertenezca a este nutricionista
    $stmtUser = $pdo->prepare("SELECT u.id, u.assigned_nutricionista_id, r.nombre as role_name FROM usuarios u JOIN roles r ON u.role_id = r.id WHERE u.id = ? LIMIT 1");
    $stmtUser->execute([$id_paciente]);
    $userRow = $stmtUser->fetch();
    if (!$userRow || strtolower($userRow['role_name']) !== 'paciente') {
        echo "<p>Paciente no encontrado o no válido.</p>";
        exit;
    }

    // Si existe assigned_nutricionista_id, validar pertenencia
    $colCheck = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'assigned_nutricionista_id'");
    if ($colCheck && $colCheck->rowCount() > 0) {
        if ($userRow['assigned_nutricionista_id'] == null || $userRow['assigned_nutricionista_id'] != $_SESSION['user_id']) {
            echo "<p>No autorizado: el paciente no te pertenece.</p>";
            exit;
        }
    } else {
        $stmtTurn = $pdo->prepare("SELECT 1 FROM turnos WHERE id_nutricionista = ? AND id_paciente = ? LIMIT 1");
        $stmtTurn->execute([$_SESSION['user_id'], $id_paciente]);
        if (!$stmtTurn->fetch()) {
            echo "<p>No autorizado: el paciente no te pertenece.</p>";
            exit;
        }
    }

    $stmt = $pdo->prepare("SELECT h.*, u.nombre as paciente_nombre, u.id as paciente_id FROM historias h LEFT JOIN usuarios u ON u.id = h.id_paciente WHERE h.id_paciente = ? ORDER BY h.creado_en DESC LIMIT 1");
    $stmt->execute([$id_paciente]);
    $historia = $stmt->fetch();

    if (!$historia) {
        echo "<p>No se encontró historia clínica para el paciente.</p>";
        exit;
    }

    // Si existe un flag que bloquee edición (historia_bloqueada), respetarlo
    $bloqueada = isset($historia['bloqueada']) && $historia['bloqueada'];

} catch (PDOException $e) {
    error_log('Error al obtener historia: ' . $e->getMessage());
    echo "<p>Error al obtener historia clínica.</p>";
    exit;
}

?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Historia Clínica</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="container my-4">
    <h1>Historia Clínica: <?php echo htmlspecialchars($historia['paciente_nombre']); ?></h1>
    <?php if ($bloqueada): ?>
        <div class="alert alert-warning">La historia clínica está bloqueada y no puede editarse.</div>
    <?php endif; ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5>Resumen</h5>
            <p><?php echo nl2br(htmlspecialchars($historia['resumen'] ?? '')); ?></p>
            <h5>Antecedentes</h5>
            <p><?php echo nl2br(htmlspecialchars($historia['antecedentes'] ?? '')); ?></p>
            <h5>Notas del nutricionista</h5>
            <p><?php echo nl2br(htmlspecialchars($historia['notas_nutri'] ?? '')); ?></p>
        </div>
    </div>

    <a href="index.php" class="btn btn-sm btn-link">Volver</a>
</body>
</html>
