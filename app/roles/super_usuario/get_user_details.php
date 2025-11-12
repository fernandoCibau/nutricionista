<?php
/**
 * Endpoint para obtener detalles de un usuario especÃ­fico (pacientes de un nutri, o nutri de un paciente).
 * Solo accesible por el superadmin.
 */

session_start();

// 1. Seguridad: Verificar sesión y rol de superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 1) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

require_once '../../config.php';

// 2. Validar entrada
$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
if (!$user_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID de usuario invÃ¡lido.']);
    exit;
}

header('Content-Type: application/json');

try {
    // 3. Obtener el usuario y su rol
    $stmt = $pdo->prepare("
        SELECT u.id, u.nombre, u.email, u.creado_en, r.nombre as nombre_rol, e.nombre as estado_nombre
        FROM usuarios u
        JOIN roles r ON u.role_id = r.id
        LEFT JOIN estados e ON u.id_estado = e.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Usuario no encontrado.']);
        exit;
    }

    $details = [];
    $role_name = strtolower($user['nombre_rol']);

    // 4. Obtener detalles adicionales según el rol
    if ($role_name === 'nutricionista') {
        // Si es nutricionista, buscar sus pacientes (incluyendo estado clÃ­nico de pacientes)
        $stmt_patients = $pdo->prepare("
            SELECT
                u.id,
                u.nombre,
                u.email,
                e.nombre as estado_nombre,
                p.id AS paciente_id,
                /* estado_paciente removido */ NULL AS estado_paciente
            FROM usuarios u
            JOIN pacientes p ON u.id = p.id_usuario
            LEFT JOIN estados e ON u.id_estado = e.id
            JOIN nutricionistas n ON p.id_nutricionista = n.id
            WHERE n.id_usuario = ?
            ORDER BY u.nombre
        ");
        $stmt_patients->execute([$user_id]);
        $details['pacientes'] = $stmt_patients->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($role_name === 'paciente') {
        // Si es paciente, devolver su estado clí­nico y su nutricionista asignado
        $stmt_p = $pdo->prepare("SELECT id AS paciente_id FROM pacientes WHERE id_usuario = ? LIMIT 1");
        $stmt_p->execute([$user_id]);
        $details['paciente'] = $stmt_p->fetch(PDO::FETCH_ASSOC) ?: null;

        $stmt_nutri = $pdo->prepare("
            SELECT u.nombre, u.email
            FROM usuarios u
            JOIN nutricionistas n ON u.id = n.id_usuario
            JOIN pacientes p ON n.id = p.id_nutricionista
            WHERE p.id_usuario = ?
        ");
        $stmt_nutri->execute([$user_id]);
        $details['nutricionista'] = $stmt_nutri->fetch(PDO::FETCH_ASSOC);
    }

    // 5. Devolver la respuesta combinada
    echo json_encode(['user' => $user, 'details' => $details]);

} catch (PDOException $e) {
    error_log("Error en get_user_details.php: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Error en la base de datos.']);
}
?>
