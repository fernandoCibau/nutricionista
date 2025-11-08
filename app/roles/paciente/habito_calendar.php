<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 3) {
    http_response_code(403);
    echo json_encode(['error' => 'no_auth']);
    exit;
}

$id_habito = filter_var($_GET['id_habito'] ?? '', FILTER_VALIDATE_INT);
if ($id_habito === false) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_id']);
    exit;
}

try {
    // Obtener paciente_id real desde la tabla pacientes
    $pstmt = $pdo->prepare("SELECT id FROM pacientes WHERE id_usuario = ? LIMIT 1");
    $pstmt->execute([$_SESSION['user_id']]);
    $ppr = $pstmt->fetch();
    if (!$ppr) {
        http_response_code(404);
        echo json_encode(['error' => 'paciente_no_encontrado']);
        exit;
    }
    $paciente_id = $ppr['id'];

    // Verificar que el hábito pertenezca al paciente
    $stmt = $pdo->prepare("SELECT id_paciente FROM habitos WHERE id = ? LIMIT 1");
    $stmt->execute([$id_habito]);
    $h = $stmt->fetch();
    if (!$h || $h['id_paciente'] != $paciente_id) {
        http_response_code(403);
        echo json_encode(['error' => 'no_autorizado']);
        exit;
    }

    // Obtener fechas completadas (último año)
    $q = $pdo->prepare("SELECT fecha FROM habit_completados WHERE id_habito = ? AND id_paciente = ? AND fecha >= DATE_SUB(CURRENT_DATE, INTERVAL 365 DAY)");
    $q->execute([$id_habito, $paciente_id]);
    $fechas = $q->fetchAll(PDO::FETCH_COLUMN);

    // También calcular racha actual (opcional)
    $stmtFechas = $pdo->prepare("SELECT fecha FROM habit_completados WHERE id_habito = ? AND id_paciente = ? AND fecha <= ? ORDER BY fecha DESC");
    $stmtFechas->execute([$id_habito, $paciente_id, date('Y-m-d')]);
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

    echo json_encode(['dates' => array_values($fechas), 'racha' => $racha]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_error']);
    exit;
}
