<?php
// crear_turno.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
  http_response_code(401);
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(['success'=>false,'message'=>'No autorizado']); exit;
}
require_once '../../config.php';                  // Debe definir $pdo (PDO)
header('Content-Type: application/json; charset=UTF-8');

// Leer desde FormData (POST) o JSON
$payload = null;
if (!empty($_POST)) {
  $fecha_hora  = $_POST['fecha_hora'] ?? null;
  $id_paciente = isset($_POST['id_paciente']) ? (int)$_POST['id_paciente'] : 0;
  $senia       = isset($_POST['senia']) && $_POST['senia'] !== '' ? (float)$_POST['senia'] : 0.00;
  $pagado      = isset($_POST['pagado']) ? 1 : 0;
  $monto       = isset($_POST['monto']) && $_POST['monto'] !== '' ? (float)$_POST['monto'] : null;
} else {
  $payload = json_decode(file_get_contents('php://input'), true) ?: [];
  $fecha_hora  = $payload['fecha_hora'] ?? null; // 'YYYY-MM-DD HH:MM:SS' (hora local)
  $id_paciente = isset($payload['id_paciente']) ? (int)$payload['id_paciente'] : 0;
  $senia       = isset($payload['senia']) && $payload['senia'] !== '' ? (float)$payload['senia'] : 0.00;
  $pagado      = isset($payload['pagado']) ? (int)((bool)$payload['pagado']) : 0;
  $monto       = isset($payload['monto']) && $payload['monto'] !== '' ? (float)$payload['monto'] : null;
}

// Normalizar formato datetime-local: 'YYYY-MM-DDTHH:MM' -> 'YYYY-MM-DD HH:MM:SS'
if ($fecha_hora) {
  $fecha_hora = str_replace('T', ' ', $fecha_hora);
  if (strlen($fecha_hora) === 16) { // YYYY-MM-DD HH:MM
    $fecha_hora .= ':00';
  }
}

if (!$fecha_hora || !$id_paciente) {
  echo json_encode(['success'=>false,'message'=>'Datos incompletos']); exit;
}

// Usuario -> nutricionista.id
$stmt = $pdo->prepare("SELECT id FROM nutricionistas WHERE id_usuario = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$nutri = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$nutri) { echo json_encode(['success'=>false,'message'=>'Nutricionista no configurado']); exit; }
$idNutri = (int)$nutri['id'];

// Validar que el paciente pertenezca a este nutricionista
$chk = $pdo->prepare("SELECT 1 FROM pacientes WHERE id = ? AND id_nutricionista = ? LIMIT 1");
$chk->execute([$id_paciente, $idNutri]);
if (!$chk->fetch()) {
  echo json_encode(['success'=>false,'message'=>'El paciente no pertenece a tu matrÃ­cula']); exit;
}

// Evitar solapamientos de ~30 minutos (no permitir otro turno cuyo inicio esté a menos de 30min)
$conf = $pdo->prepare("SELECT 1
                        FROM turnos
                        WHERE id_nutricionista = ?
                          AND ABS(TIMESTAMPDIFF(MINUTE, fecha_hora, ?)) < 30
                        LIMIT 1");
$conf->execute([$idNutri, $fecha_hora]);
if ($conf->fetch()) {
  echo json_encode(['success'=>false,'message'=>'Existe un turno dentro de 30 minutos de ese horario']); exit;
}

// Insert ajustado al esquema actual (sin columnas estado/monto)
$ins = $pdo->prepare("INSERT INTO turnos (id_nutricionista, id_paciente, fecha_hora, senia, pagado, estado)
                      VALUES (?, ?, ?, ?, ?, ?)");
$ok = $ins->execute([$idNutri, $id_paciente, $fecha_hora, $senia, $pagado, 'programado']);

echo json_encode(['success'=>$ok, 'message'=>$ok?'Turno creado':'No se pudo crear el turno']);


