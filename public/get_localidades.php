<?php
// Este script es llamado por una petición fetch desde el frontend (ver app/roles/super_usuario/index.js).
// Recibe el ID de una provincia, busca en la base de datos todas las localidades que pertenecen a esa provincia,
// y devuelve un JSON con las localidades encontradas.

require_once '../app/config.php';

header('Content-Type: application/json');

if (!isset($_GET['provincia_id']) || !ctype_digit($_GET['provincia_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de provincia inválido']);
    exit;
}

$provincia_id = $_GET['provincia_id'];

try {
    $stmt = $pdo->prepare("SELECT id, nombre FROM localidades WHERE id_prov = ? ORDER BY nombre");
    $stmt->execute([$provincia_id]);
    $localidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($localidades);
} catch (PDOException $e) {
    http_response_code(500);
    // En un entorno de producción, sería mejor loguear el error que mostrarlo
    echo json_encode(['error' => 'Error al consultar la base de datos']);
    error_log($e->getMessage());
}
