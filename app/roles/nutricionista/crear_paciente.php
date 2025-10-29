<?php
// crear_paciente.php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$nombre    = trim($_POST['user_name'] ?? '');
$email     = trim($_POST['user_email'] ?? '');
$dni       = trim($_POST['dni'] ?? '');
$fnac      = $_POST['fecha_nacimiento'] ?? null;
$telefono  = trim($_POST['telefono'] ?? '');
$objetivo  = trim($_POST['objetivo_principal'] ?? '');

if ($nombre === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Nombre y email son obligatorios.']);
    exit;
}

try {
    // obtener id del nutricionista logueado
    $stmtNutri = $pdo->prepare("SELECT id FROM nutricionistas WHERE id_usuario = ? LIMIT 1");
    $stmtNutri->execute([$_SESSION['user_id']]);
    $idNutricionista = $stmtNutri->fetchColumn();

    if (!$idNutricionista) {
        echo json_encode(['success' => false, 'message' => 'Tu cuenta de nutricionista no estÃ¡ activa.']);
        exit;
    }

    $pdo->beginTransaction();

    // email Ãºnico
    $st = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $st->execute([$email]);
    if ($st->fetch()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'El email ya estÃ¡ registrado.']);
        exit;
    }

    // crear usuario paciente (role_id = 3)
    $passwordTemp = bin2hex(random_bytes(4)); // 8 caracteres hex
    $passwordHash = password_hash($passwordTemp, PASSWORD_BCRYPT);

    $estadoActivoId = (int)$pdo->query("SELECT id FROM estados WHERE nombre = 'activo' LIMIT 1")->fetchColumn();
    $insUser = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, role_id, id_estado) VALUES (?,?,?,?,?)");
    $insUser->execute([$nombre, $email, $passwordHash, 3, $estadoActivoId ?: null]);
    $idUsuario = (int)$pdo->lastInsertId();

    // crear paciente
    $insPac = $pdo->prepare("
        INSERT INTO pacientes (id_usuario, id_nutricionista, dni, fecha_nacimiento, telefono, objetivo_principal) VALUES (?,?,?,?,?,?)
    ");
    $insPac->execute([
        $idUsuario,
        $idNutricionista,
        $dni !== '' ? $dni : null,
        $fnac ?: null,
        $telefono !== '' ? $telefono : null,
        $objetivo !== '' ? $objetivo : null
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Paciente creado correctamente.',
        'temp_password' => $passwordTemp
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('crear_paciente: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al crear el paciente.']);
}



