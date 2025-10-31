<?php
session_start();
require_once '../../../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$pacienteId = (int)($_POST['paciente_id'] ?? 0);
$fecha = $_POST['fecha'] ?? null; // YYYY-MM-DD HH:MM:SS o DATE
$peso = $_POST['peso_kg'] ?? null;
$altura = $_POST['altura_cm'] ?? null;
$masa = $_POST['masa_muscular_pct'] ?? null;
$coment = trim($_POST['comentarios'] ?? '');
$habitos = trim($_POST['habitos_cumplidos'] ?? '');
$objetivos = trim($_POST['objetivos_trabajados'] ?? '');

if ($pacienteId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Paciente inválido']);
    exit;
}

try {
    $st = $pdo->prepare("SELECT p.id FROM pacientes p JOIN nutricionistas n ON n.id=p.id_nutricionista WHERE p.id=? AND n.id_usuario=? LIMIT 1");
    $st->execute([$pacienteId, $_SESSION['user_id']]);
    if (!$st->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Paciente no encontrado']);
        exit;
    }

    $q = $pdo->prepare("INSERT INTO consultas (id_paciente, fecha, peso_kg, altura_cm, masa_muscular_pct, comentarios, habitos_cumplidos, objetivos_trabajados) VALUES (?,?,?,?,?,?,?,?)");
    $q->execute([
        $pacienteId,
        $fecha ?: date('Y-m-d H:i:s'),
        $peso !== '' ? $peso : null,
        $altura !== '' ? $altura : null,
        $masa !== '' ? $masa : null,
        $coment !== '' ? $coment : null,
        $habitos !== '' ? $habitos : null,
        $objetivos !== '' ? $objetivos : null,
    ]);

    echo json_encode(['success' => true, 'message' => 'Consulta creada']);
} catch (Throwable $e) {
    error_log('consultas_crear: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al crear consulta']);
}
