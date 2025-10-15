<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../../config.php';

$id_paciente = filter_var($_POST['id_paciente'] ?? '', FILTER_VALIDATE_INT);
$fecha_hora = $_POST['fecha_hora'] ?? '';
$senia = isset($_POST['senia']) ? trim($_POST['senia']) : null;
$monto = isset($_POST['monto']) ? floatval($_POST['monto']) : null;

if ($id_paciente === false || empty($fecha_hora)) {
    header('Location: index.php?error=turno_invalido');
    exit;
}

try {
    // Comprobar que el paciente exista y que pertenezca a este nutricionista
    $stmt = $pdo->prepare("SELECT u.id, u.assigned_nutricionista_id, r.nombre as role_name FROM usuarios u JOIN roles r ON u.role_id = r.id WHERE u.id = ? LIMIT 1");
    $stmt->execute([$id_paciente]);
    $userRow = $stmt->fetch();
    if (!$userRow || strtolower($userRow['role_name']) !== 'paciente') {
        header('Location: index.php?error=paciente_no_encontrado');
        exit;
    }

    // Si existe assigned_nutricionista_id, se debe corresponder al nutricionista actual
    $colCheck = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'assigned_nutricionista_id'");
    if ($colCheck && $colCheck->rowCount() > 0) {
        if ($userRow['assigned_nutricionista_id'] == null || $userRow['assigned_nutricionista_id'] != $_SESSION['user_id']) {
            header('Location: index.php?error=no_autorizado');
            exit;
        }
    } else {
        // Si no hay columna de asignación, verificar que exista al menos un turno previo entre paciente y nutricionista
        $stmtTurn = $pdo->prepare("SELECT 1 FROM turnos WHERE id_nutricionista = ? AND id_paciente = ? LIMIT 1");
        $stmtTurn->execute([$_SESSION['user_id'], $id_paciente]);
        if (!$stmtTurn->fetch()) {
            header('Location: index.php?error=no_autorizado');
            exit;
        }
    }

    // Insertar el turno
    $ins = $pdo->prepare("INSERT INTO turnos (id_nutricionista, id_paciente, fecha_hora, estado, senia, pagado, monto, creado_en) VALUES (?, ?, ?, 'programado', ?, 0, ?, NOW())");
    $ins->execute([$_SESSION['user_id'], $id_paciente, $fecha_hora, $senia, $monto]);

    header('Location: index.php?exito=turno_creado');
    exit;
} catch (PDOException $e) {
    error_log('Error al crear turno: ' . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}

?>
