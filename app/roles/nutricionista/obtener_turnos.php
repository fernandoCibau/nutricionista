<?php
header('Content-Type: application/json');

session_start();
require_once '../../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    echo json_encode([]);
    exit;
}

try {
    $stmtNutri = $pdo->prepare("SELECT id FROM nutricionistas WHERE id_usuario = ? LIMIT 1");
    $stmtNutri->execute([$_SESSION['user_id']]);
    $nutri = $stmtNutri->fetch(PDO::FETCH_ASSOC);

    if (!$nutri) {
        echo json_encode([]);
        exit;
    }
    $idNutricionista = (int)$nutri['id'];

    $params = [$idNutricionista];
    $sql = "
        SELECT 
            t.id, 
            u.nombre AS title, 
            t.fecha_hora AS start, 
            t.estado,
            t.id_paciente,
            t.senia,
            t.pagado
        FROM turnos t
        JOIN pacientes p ON t.id_paciente = p.id
        JOIN usuarios u ON p.id_usuario = u.id
        WHERE t.id_nutricionista = ?
    ";

    if (isset($_GET['search_query']) && !empty($_GET['search_query'])) {
        $search_query = '%' . $_GET['search_query'] . '%';
        $sql .= " AND (u.nombre LIKE ? OR p.dni LIKE ?)";
        $params[] = $search_query;
        $params[] = $search_query;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $eventos = [];
    foreach ($turnos as $turno) {
        $color = '#007bff'; // azul para pendiente
        if ($turno['estado'] === 'confirmado') {
            $color = '#28a745'; // verde
        } elseif ($turno['estado'] === 'cancelado') {
            $color = '#dc3545'; // rojo
        }

        $eventos[] = [
            'id'    => $turno['id'],
            'title' => $turno['title'],
            'start' => $turno['start'],
            'color' => $color,
            'extendedProps' => [
                'id_paciente' => $turno['id_paciente'],
                'senia' => $turno['senia'],
                'pagado' => $turno['pagado'],
                'estado' => $turno['estado']
            ]
        ];
    }

    echo json_encode($eventos);

} catch (PDOException $e) {
    error_log("Error en obtener_turnos.php: " . $e->getMessage());
    echo json_encode([]);
}