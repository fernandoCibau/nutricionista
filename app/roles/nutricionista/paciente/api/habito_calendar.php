<?php
session_start();
require_once '../../../../config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$pacienteId = (int)($_GET['paciente_id'] ?? 0);
$id_habito = (int)($_GET['id_habito'] ?? 0);
if ($pacienteId <= 0 || $id_habito <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit;
}

try {
    // Validar que el paciente pertenece al nutricionista logueado
    $st = $pdo->prepare("SELECT p.id FROM pacientes p JOIN nutricionistas n ON n.id=p.id_nutricionista WHERE p.id=? AND n.id_usuario=? LIMIT 1");
    $st->execute([$pacienteId, $_SESSION['user_id']]);
    if (!$st->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Paciente no encontrado']);
        exit;
    }

    // Verificar que el habito pertenece al paciente
    $hstmt = $pdo->prepare("SELECT id FROM habitos WHERE id = ? AND id_paciente = ? LIMIT 1");
    $hstmt->execute([$id_habito, $pacienteId]);
    if (!$hstmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Hábito no encontrado para este paciente']);
        exit;
    }

    // Obtener fechas completadas (último año)
    $q = $pdo->prepare("SELECT fecha FROM habit_completados WHERE id_habito = ? AND id_paciente = ? AND fecha >= DATE_SUB(CURRENT_DATE, INTERVAL 365 DAY)");
    $q->execute([$id_habito, $pacienteId]);
    $fechas = $q->fetchAll(PDO::FETCH_COLUMN);

    // Calcular racha actual
    $stmtFechas = $pdo->prepare("SELECT fecha FROM habit_completados WHERE id_habito = ? AND id_paciente = ? AND fecha <= ? ORDER BY fecha DESC");
    $stmtFechas->execute([$id_habito, $pacienteId, date('Y-m-d')]);
    $fechas_all = $stmtFechas->fetchAll(PDO::FETCH_COLUMN);
    $racha = 0;
    $expected = date('Y-m-d');
    foreach ($fechas_all as $f) {
        if ($f === $expected) {
            $racha++;
            $expected = date('Y-m-d', strtotime($expected . ' -1 day'));
        } else {
            break;
        }
    }

    echo json_encode(['success' => true, 'dates' => array_values($fechas), 'racha' => $racha]);
    exit;

} catch (Throwable $e) {
    error_log('nutri habito_calendar: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de servidor']);
    exit;
}
