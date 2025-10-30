<?php
// obtener_paciente.php
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    $st = $pdo->prepare("
        SELECT
            p.id AS paciente_id, p.dni, p.fecha_nacimiento, p.telefono, p.objetivo_principal,
            u.nombre, u.email
        FROM pacientes p
        JOIN usuarios u ON u.id = p.id_usuario
        JOIN nutricionistas n ON n.id = p.id_nutricionista
        WHERE p.id = ? AND n.id_usuario = ?
        LIMIT 1
    ");
    $st->execute([$id, $_SESSION['user_id']]);
    $data = $st->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'No encontrado']);
        exit;
    }
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    error_log('obtener_paciente: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
}
