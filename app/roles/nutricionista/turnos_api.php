<?php
// turnos_api.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
  http_response_code(401);
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(['ok'=>false, 'msg'=>'No autorizado']);
  exit;
}

require_once '../../config.php';                  // Debe definir $pdo (PDO)
require_once '../../libs/vendor/autoload.php';
header('Content-Type: application/json; charset=UTF-8');

// 1) Mapear usuario -> nutricionista.id
$stmt = $pdo->prepare("SELECT id FROM nutricionistas WHERE id_usuario = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$nutri = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$nutri) {
  echo json_encode([]); // sin eventos
  exit;
}
$idNutricionista = (int)$nutri['id'];

// (Opcional) Podés filtrar por rango si FullCalendar te manda start/end:
// $start = $_GET['start'] ?? null; // ISO
// $end   = $_GET['end']   ?? null;

$sql = "SELECT t.id, t.fecha_hora, t.estado, t.pagado, t.senia, t.monto,
               p.id AS paciente_id, u.nombre AS paciente_nombre
        FROM turnos t
        JOIN pacientes p ON p.id = t.id_paciente
        LEFT JOIN usuarios u ON u.id = p.id_usuario
        WHERE t.id_nutricionista = ?
        ORDER BY t.fecha_hora ASC";
$st = $pdo->prepare($sql);
$st->execute([$idNutricionista]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// Adaptar a FullCalendar
$events = array_map(function($r){
  return [
    'id'    => (string)$r['id'],
    'title' => $r['paciente_nombre'] ? $r['paciente_nombre'] : ('Paciente #' . $r['paciente_id']),
    'start' => date('c', strtotime($r['fecha_hora'])), // ISO 8601
    'extendedProps' => [
      'estado' => $r['estado'],
      'pagado' => (int)$r['pagado'] === 1,
      'senia'  => $r['senia'],
      'monto'  => $r['monto']
    ]
  ];
}, $rows);

echo json_encode($events);
