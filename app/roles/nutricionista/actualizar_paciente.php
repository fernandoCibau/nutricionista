<?php
// actualizar_paciente.php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$idPaciente = (int)($_POST['paciente_id'] ?? 0);
$nombre     = trim($_POST['user_name'] ?? '');
$email      = trim($_POST['user_email'] ?? '');
$dni        = trim($_POST['dni'] ?? '');
$fnac       = $_POST['fecha_nacimiento'] ?? null;
$telefono   = trim($_POST['telefono'] ?? '');
$objetivo   = trim($_POST['objetivo_principal'] ?? '');

if ($idPaciente <= 0 || $nombre === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit;
}

try {
    // verificar que el paciente pertenece al nutricionista logueado
    $stmt = $pdo->prepare("
        SELECT p.id, p.id_usuario
        FROM pacientes p
        JOIN nutricionistas n ON n.id = p.id_nutricionista
        WHERE p.id = ? AND n.id_usuario = ?
        LIMIT 1
    ");
    $stmt->execute([$idPaciente, $_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Paciente no encontrado.']);
        exit;
    }
    $idUsuario = (int)$row['id_usuario'];

    $pdo->beginTransaction();

    // email único (excluyendo al propio usuario)
    $st = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1");
    $st->execute([$email, $idUsuario]);
    if ($st->fetch()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'El email ya está en uso por otro usuario.']);
        exit;
    }

    // actualizar usuarios
    $upU = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?");
    $upU->execute([$nombre, $email, $idUsuario]);

    // actualizar pacientes
    $upP = $pdo->prepare("
        UPDATE pacientes
        SET dni = ?, fecha_nacimiento = ?, telefono = ?, objetivo_principal = ?
        WHERE id = ?
    ");
    $upP->execute([
        $dni !== '' ? $dni : null,
        $fnac ?: null,
        $telefono !== '' ? $telefono : null,
        $objetivo !== '' ? $objetivo : null,
        $idPaciente
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Paciente actualizado correctamente.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('actualizar_paciente: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el paciente.']);
}
