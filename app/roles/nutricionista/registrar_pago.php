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

$turno_id = filter_var($_POST['turno_id'] ?? '', FILTER_VALIDATE_INT);
$id_paciente = filter_var($_POST['id_paciente'] ?? '', FILTER_VALIDATE_INT);
$fecha_hora = $_POST['fecha_hora'] ?? '';
$senia = isset($_POST['senia']) ? trim($_POST['senia']) : null; // puede ser texto
$pagado = isset($_POST['pagado']) ? (int)$_POST['pagado'] : 0; // 0 o 1
$monto = isset($_POST['monto']) ? floatval($_POST['monto']) : null;

try {
    if ($turno_id) {
        $stmt = $pdo->prepare("SELECT id, id_nutricionista FROM turnos WHERE id = ? LIMIT 1");
        $stmt->execute([$turno_id]);
        $turno = $stmt->fetch();
        if (!$turno || $turno['id_nutricionista'] != $_SESSION['user_id']) {
            header('Location: index.php?error=turno_no_encontrado');
            exit;
        }
        $upd = $pdo->prepare("UPDATE turnos SET senia = ?, pagado = ?, monto = ? WHERE id = ?");
        $upd->execute([$senia, $pagado, $monto, $turno_id]);
        header('Location: index.php?exito=pago_registrado');
        exit;
    }

    if ($id_paciente === false || empty($fecha_hora)) {
        header('Location: index.php?error=turno_invalido');
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, id_nutricionista FROM turnos WHERE id_nutricionista = ? AND id_paciente = ? AND fecha_hora = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id'], $id_paciente, $fecha_hora]);
    $turno = $stmt->fetch();
    if (!$turno) {
        header('Location: index.php?error=turno_no_encontrado');
        exit;
    }

    $upd = $pdo->prepare("UPDATE turnos SET senia = ?, pagado = ?, monto = ? WHERE id = ?");
    $upd->execute([$senia, $pagado, $monto, $turno['id']]);
    header('Location: index.php?exito=pago_registrado');
    exit;

} catch (PDOException $e) {
    error_log('Error al registrar pago: ' . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}

?>
