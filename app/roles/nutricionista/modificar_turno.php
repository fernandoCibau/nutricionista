<?php
header('Content-Type: application/json');

session_start();
require_once '../../config.php';

$response = ['success' => false, 'message' => 'Error desconocido.'];

// 1) Verificación de sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    $response['message'] = 'Acceso denegado.';
    http_response_code(403);
    echo json_encode($response);
    exit;
}

// 2) Validar que el ID del turno exista
$turno_id = filter_input(INPUT_POST, 'turno_id', FILTER_VALIDATE_INT);
if (!$turno_id) {
    $response['message'] = 'ID de turno no válido.';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

try {
    // 3) Mapear usuario -> nutricionistas.id y verificar pertenencia del turno
    $stmtNutri = $pdo->prepare('SELECT id FROM nutricionistas WHERE id_usuario = ? LIMIT 1');
    $stmtNutri->execute([$_SESSION['user_id']]);
    $nutri = $stmtNutri->fetch(PDO::FETCH_ASSOC);
    if (!$nutri) {
        $response['message'] = 'Usuario no configurado como nutricionista.';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    $idNutricionista = (int)$nutri['id'];

    $stmtCheck = $pdo->prepare('SELECT id FROM turnos WHERE id = ? AND id_nutricionista = ?');
    $stmtCheck->execute([$turno_id, $idNutricionista]);
    if ($stmtCheck->rowCount() === 0) {
        $response['message'] = 'No tienes permiso para modificar este turno.';
        http_response_code(403);
        echo json_encode($response);
        exit;
    }

    // 4) Recoger y validar datos del POST
    $id_paciente = filter_input(INPUT_POST, 'id_paciente', FILTER_VALIDATE_INT);
    $fecha_hora = $_POST['fecha_hora'] ?? null;
    $estado = $_POST['estado'] ?? null;
    $senia = filter_input(INPUT_POST, 'senia', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0.00]]);
    $pagado = isset($_POST['pagado']) ? 1 : 0;

    // Normalizar formato datetime-local ('T' -> ' ' y segundos)
    if ($fecha_hora) {
        $fecha_hora = str_replace('T', ' ', $fecha_hora);
        if (strlen($fecha_hora) === 16) { // YYYY-MM-DD HH:MM
            $fecha_hora .= ':00';
        }
    }

    // 5) Cancelación: eliminar el turno
    if ($estado === 'cancelado' && !$id_paciente) {
        $sql = 'DELETE FROM turnos WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$turno_id])) {
            $response['success'] = true;
            $response['message'] = 'Turno cancelado correctamente.';
        } else {
            $response['message'] = 'Error al cancelar el turno.';
            http_response_code(500);
        }
    } else {
        // 6) Actualización completa
        if (!$id_paciente || !$fecha_hora) {
            $response['message'] = 'Faltan datos obligatorios (paciente y fecha).';
            http_response_code(400);
            echo json_encode($response);
            exit;
        }

        // a) Verificar que el paciente pertenezca al nutricionista
        $chk = $pdo->prepare('SELECT 1 FROM pacientes WHERE id = ? AND id_nutricionista = ? LIMIT 1');
        $chk->execute([$id_paciente, $idNutricionista]);
        if (!$chk->fetch()) {
            $response['message'] = 'El paciente no pertenece a tu matrícula.';
            http_response_code(400);
            echo json_encode($response);
            exit;
        }

        // b) Evitar solapamientos ~30min (excluir este turno)
        $conf = $pdo->prepare('SELECT 1 FROM turnos
                               WHERE id_nutricionista = ?
                                 AND id <> ?
                                 AND ABS(TIMESTAMPDIFF(MINUTE, fecha_hora, ?)) < 30
                               LIMIT 1');
        $conf->execute([$idNutricionista, $turno_id, $fecha_hora]);
        if ($conf->fetch()) {
            $response['message'] = 'Existe un turno dentro de 30 minutos de ese horario.';
            http_response_code(409);
            echo json_encode($response);
            exit;
        }

        // c) Actualizar
        $sql = 'UPDATE turnos SET id_paciente = ?, fecha_hora = ?, senia = ?, pagado = ? WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$id_paciente, $fecha_hora, $senia, $pagado, $turno_id])) {
            $response['success'] = true;
            $response['message'] = 'Turno actualizado con éxito.';
        } else {
            $response['message'] = 'Error al actualizar el turno.';
            http_response_code(500);
        }
    }

} catch (PDOException $e) {
    error_log('Error en modificar_turno.php: ' . $e->getMessage());
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);

