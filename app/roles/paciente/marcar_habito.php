<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 3) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'no_auth']);
        exit;
    }
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'invalid_method']);
    exit;
}

require_once __DIR__ . '/../../config.php';

// Function to calculate streak and provide debug info
function calcularRacha($pdo, $id_habito, $id_paciente) {
    $debug = [];
    try {
        $stmtFechas = $pdo->prepare("SELECT fecha FROM habit_completados WHERE id_habito = ? AND id_paciente = ? ORDER BY fecha DESC");
        $stmtFechas->execute([$id_habito, $id_paciente]);
        $fechas = $stmtFechas->fetchAll(PDO::FETCH_COLUMN);
        $debug['fechas_encontradas'] = $fechas;

        if (count($fechas) === 0) {
            $debug['resultado'] = 'No hay fechas de cumplimiento, la racha es 0.';
            return ['racha' => 0, 'debug' => $debug];
        }

        $racha = 1;
        $expected_date = new DateTimeImmutable($fechas[0]);
        $debug['calculo'][] = "Iniciando racha en 1 para la fecha: " . $fechas[0];

        for ($i = 1; $i < count($fechas); $i++) {
            $expected_date = $expected_date->modify('-1 day');
            $current_date_str = $fechas[$i];
            $current_date = DateTimeImmutable::createFromFormat('Y-m-d', $current_date_str);
            
            $debug_step = "Iteración " . $i . ": Fecha esperada: " . $expected_date->format('Y-m-d') . ". Fecha encontrada: " . $current_date_str;

            if ($current_date && $current_date->format('Y-m-d') === $expected_date->format('Y-m-d')) {
                $racha++;
                $debug_step .= ". Coincide. Racha ahora es " . $racha;
            } else {
                $debug_step .= ". No coincide. Se rompe la racha.";
                $debug['calculo'][] = $debug_step;
                break; 
            }
            $debug['calculo'][] = $debug_step;
        }
        
        $debug['racha_final_calculada'] = $racha;
        return ['racha' => $racha, 'debug' => $debug];

    } catch (Exception $e) {
        error_log('Error en calcularRacha: ' . $e->getMessage());
        $debug['error'] = 'Excepción en calcularRacha: ' . $e->getMessage();
        return ['racha' => 0, 'debug' => $debug];
    }
}

$id_habito = filter_var($_POST['id_habito'] ?? '', FILTER_VALIDATE_INT);
$fecha = trim($_POST['fecha'] ?? date('Y-m-d'));
$debug_info = [];

if ($id_habito === false || empty($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'datos_invalidos']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt_paciente = $pdo->prepare("SELECT id FROM pacientes WHERE id_usuario = ? LIMIT 1");
    $stmt_paciente->execute([$_SESSION['user_id']]);
    $paciente = $stmt_paciente->fetch();
    if (!$paciente) {
        throw new Exception('paciente_no_encontrado');
    }
    $paciente_id = $paciente['id'];

    $stmt_habito = $pdo->prepare("SELECT id_paciente FROM habitos WHERE id = ? LIMIT 1");
    $stmt_habito->execute([$id_habito]);
    $habito = $stmt_habito->fetch();
    if (!$habito || $habito['id_paciente'] != $paciente_id) {
        throw new Exception('no_autorizado');
    }

    $cstmt = $pdo->prepare("SELECT id FROM habit_completados WHERE id_habito = ? AND id_paciente = ? AND fecha = ? LIMIT 1");
    $cstmt->execute([$id_habito, $paciente_id, $fecha]);
    $exists = $cstmt->fetch();

    $action = '';

    if ($exists) {
        $del = $pdo->prepare("DELETE FROM habit_completados WHERE id = ?");
        $del->execute([$exists['id']]);
        $action = 'desmarcado';
    } else {
        $ins = $pdo->prepare("INSERT INTO habit_completados (id_habito, id_paciente, fecha) VALUES (?, ?, ?)");
        $ins->execute([$id_habito, $paciente_id, $fecha]);
        $action = 'marcado';
    }

    $racha_result = calcularRacha($pdo, $id_habito, $paciente_id);
    $racha_actual = $racha_result['racha'];
    $debug_info = $racha_result['debug'];

    $upd_racha = $pdo->prepare("UPDATE habitos SET racha_dias = ? WHERE id = ?");
    $upd_racha->execute([$racha_actual, $id_habito]);
    
    $debug_info['sql_update'] = "UPDATE habitos SET racha_dias = " . $racha_actual . " WHERE id = " . $id_habito;
    $debug_info['update_exitoso'] = $upd_racha->rowCount() > 0;

    $pdo->commit();

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'ok', 'action' => $action, 'racha' => $racha_actual, 'debug' => $debug_info]);
    } else {
        header('Location: index.php#habitos');
    }
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al marcar hábito: ' . $e->getMessage());

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'db_error', 'detail' => $e->getMessage(), 'debug_info' => $debug_info ?? null]);
    } else {
        $_SESSION['error_message'] = 'Error al marcar el hábito. Inténtelo de nuevo.';
        header('Location: index.php#habitos');
    }
    exit;
}
?>