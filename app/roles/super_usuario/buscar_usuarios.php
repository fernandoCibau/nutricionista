<?php
/**
 * Endpoint AJAX para buscar usuarios.
 * Devuelve una lista de usuarios en formato JSON.
 * Accesible solo por el superadmin.
 */

session_start();
header('Content-Type: application/json');

// 1. Seguridad: Verificar sesión y rol de superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 1) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}

require_once '../../config.php';

// 2. Obtener parámetros de la URL
$search_query = trim($_GET['q'] ?? '');
$filtro_rol = $_GET['rol'] ?? 'todos';

try {
    // 3. Construir la consulta SQL
    $sql = "
        SELECT 
            u.id, u.nombre, u.email, u.creado_en, u.role_id, u.id_estado,
            r.nombre AS nombre_rol,
            e.nombre AS estado_nombre,
            un.nombre AS nutricionista_nombre,
            p.dni
        FROM usuarios u
        JOIN roles r ON u.role_id = r.id
        LEFT JOIN estados e ON u.id_estado = e.id
        LEFT JOIN pacientes p ON p.id_usuario = u.id
        LEFT JOIN nutricionistas n ON n.id = p.id_nutricionista
        LEFT JOIN usuarios un ON un.id = n.id_usuario
    ";

    $where_clauses = [];
    $params = [];

    if ($filtro_rol !== 'todos') {
        $rol_buscar = $filtro_rol === 'super_usuario' ? 'superadmin' : $filtro_rol;
        $where_clauses[] = "r.nombre = ?";
        $params[] = $rol_buscar;
    }

    if ($search_query !== '') {
        $where_clauses[] = "(u.nombre LIKE ? OR u.email LIKE ? OR p.dni LIKE ?)";
        array_push($params, "%$search_query%", "%$search_query%", "%$search_query%");
    }

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(' AND ', $where_clauses);
    }

    $sql .= " ORDER BY u.nombre ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['usuarios' => $usuarios]);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    error_log("Error en buscar_usuarios.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error en la base de datos.']);
}
?>