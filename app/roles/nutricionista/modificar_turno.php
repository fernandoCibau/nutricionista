<?php
header('Content-Type: application/json');

session_start();
require_once '../../config.php';

$response = ['success' => false, 'message' => 'Error desconocido.'];

// 1. Verificación de sesión y rol
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    $response['message'] = 'Acceso denegado.';
    http_response_code(403);
    echo json_encode($response);
    exit;
}

// 2. Validar que el ID del turno exista
$turno_id = filter_input(INPUT_POST, 'turno_id', FILTER_VALIDATE_INT);
if (!$turno_id) {
    $response['message'] = 'ID de turno no válido.';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

try {
    // 3. Obtener ID de Nutricionista y verificar que el turno le pertenece
    $stmtNutri = $pdo->prepare("SELECT id FROM nutricionistas WHERE id_usuario = ? LIMIT 1");
    $stmtNutri->execute([$_SESSION['user_id']]);
    $nutri = $stmtNutri->fetch(PDO::FETCH_ASSOC);

    if (!$nutri) {
        $response['message'] = 'Usuario no configurado como nutricionista.';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    $idNutricionista = (int)$nutri['id'];

    // Verificar pertenencia del turno
    $stmtCheck = $pdo->prepare("SELECT id FROM turnos WHERE id = ? AND id_nutricionista = ?");
    $stmtCheck->execute([$turno_id, $idNutricionista]);
    if ($stmtCheck->rowCount() === 0) {
        $response['message'] = 'No tienes permiso para modificar este turno.';
        http_response_code(403);
        echo json_encode($response);
        exit;
    }

    // 4. Recoger y validar datos del POST
    $id_paciente = filter_input(INPUT_POST, 'id_paciente', FILTER_VALIDATE_INT);
    $fecha_hora = $_POST['fecha_hora'] ?? null;
    $estado = $_POST['estado'] ?? null;
    $senia = filter_input(INPUT_POST, 'senia', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0.00]]);
    $pagado = isset($_POST['pagado']) ? 1 : 0;

    // Si solo se está cancelando, solo se necesita el estado
    if ($estado === 'cancelado' && !$id_paciente) {
        $sql = "UPDATE turnos SET estado = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute(['cancelado', $turno_id])) {
            $response['success'] = true;
            $response['message'] = 'Turno cancelado con éxito.';
        } else {
            $response['message'] = 'Error al cancelar el turno.';
            http_response_code(500);
        }
    } else {
        // Actualización completa
        if (!$id_paciente || !$fecha_hora || !$estado) {
            $response['message'] = 'Faltan datos obligatorios (paciente, fecha o estado).';
            http_response_code(400);
            echo json_encode($response);
            exit;
        }

        $sql = "UPDATE turnos SET id_paciente = ?, fecha_hora = ?, estado = ?, senia = ?, pagado = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$id_paciente, $fecha_hora, $estado, $senia, $pagado, $turno_id])) {
            $response['success'] = true;
            $response['message'] = 'Turno actualizado con éxito.';
        } else {
            $response['message'] = 'Error al actualizar el turno.';
            http_response_code(500);
        }
    }

} catch (PDOException $e) {
    error_log("Error en modificar_turno.php: " . $e->getMessage());
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
