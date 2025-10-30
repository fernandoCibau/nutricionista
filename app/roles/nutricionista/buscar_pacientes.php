<?php
// buscar_pacientes.php
session_start();
require_once '../../config.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$q = trim((string)($_GET['q'] ?? ''));
if ($q === '' || strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Mapear usuario -> nutricionista.id
    $stNutri = $pdo->prepare("SELECT id FROM nutricionistas WHERE id_usuario = ? LIMIT 1");
    $stNutri->execute([$_SESSION['user_id']]);
    $nutri = $stNutri->fetch(PDO::FETCH_ASSOC);
    if (!$nutri) { echo json_encode([]); exit; }
    $idNutri = (int)$nutri['id'];

    // Buscar pacientes del nutricionista por nombre o DNI (solo activos si corresponde)
    $like = '%' . $q . '%';
    $sql = "
        SELECT p.id AS id, u.nombre AS nombre, p.dni AS dni
        FROM pacientes p
        JOIN usuarios u ON p.id_usuario = u.id
        LEFT JOIN estados e ON u.id_estado = e.id
        WHERE p.id_nutricionista = ?
          AND (u.nombre LIKE ? OR p.dni LIKE ?)
          AND (e.nombre IS NULL OR e.nombre = 'activo')
        ORDER BY u.nombre ASC
        LIMIT 20
    ";
    $st = $pdo->prepare($sql);
    $st->execute([$idNutri, $like, $like]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    // Estructura de salida simple
    $out = array_map(function($r){
        return [
            'id' => (int)$r['id'],
            'nombre' => $r['nombre'],
            'dni' => $r['dni']
        ];
    }, $rows);

    echo json_encode($out);
} catch (Throwable $e) {
    error_log('buscar_pacientes: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([]);
}

