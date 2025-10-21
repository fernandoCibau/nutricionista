<?php
// cancelar_turno.php
session_start();
$wantsJson = (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
  if ($wantsJson) {
    http_response_code(401);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['ok'=>false,'msg'=>'No autorizado']);
  } else {
    header('Location: vista_turnos.php?type=danger&msg=' . urlencode('No autorizado'));
  }
  exit;
}

require_once '../../config.php';                  // Debe definir $pdo (PDO)
require_once '../../libs/vendor/autoload.php';

function json_out($arr) {
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode($arr); exit;
}

// Usuario -> nutricionista.id
$stmt = $pdo->prepare("SELECT id FROM nutricionistas WHERE id_usuario = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$nutri = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$nutri) {
  $msg = 'Nutricionista no configurado';
  if ($wantsJson) json_out(['ok'=>false,'msg'=>$msg]);
  header('Location: vista_turnos.php?type=danger&msg=' . urlencode($msg));
  exit;
}
$idNutri = (int)$nutri['id'];

// Obtener identificadores
$turnoId = 0;
if ($wantsJson) {
  $payload = json_decode(file_get_contents('php://input'), true);
  $turnoId = isset($payload['turno_id']) ? (int)$payload['turno_id'] : 0;
} else {
  // Compatibilidad con tu formulario anterior:
  // a) turno_id directo
  if (!empty($_POST['turno_id'])) {
    $turnoId = (int)$_POST['turno_id'];
  } else {
    // b) id_paciente + fecha_hora
    $idPaciente = isset($_POST['id_paciente']) ? (int)$_POST['id_paciente'] : 0;
    $fecha_hora = $_POST['fecha_hora'] ?? '';
    if ($idPaciente > 0 && $fecha_hora !== '') {
      $q = $pdo->prepare("SELECT id FROM turnos WHERE id_nutricionista = ? AND id_paciente = ? AND fecha_hora = ? LIMIT 1");
      $q->execute([$idNutri, $idPaciente, $fecha_hora]);
      $row = $q->fetch(PDO::FETCH_ASSOC);
      if ($row) $turnoId = (int)$row['id'];
    }
  }
}

if ($turnoId <= 0) {
  $msg = 'Parámetros inválidos';
  if ($wantsJson) json_out(['ok'=>false,'msg'=>$msg]);
  header('Location: vista_turnos.php?type=danger&msg=' . urlencode($msg));
  exit;
}

// Verificar pertenencia
$own = $pdo->prepare("SELECT id FROM turnos WHERE id = ? AND id_nutricionista = ? LIMIT 1");
$own->execute([$turnoId, $idNutri]);
if (!$own->fetch()) {
  $msg = 'Turno inexistente o sin permiso';
  if ($wantsJson) json_out(['ok'=>false,'msg'=>$msg]);
  header('Location: vista_turnos.php?type=danger&msg=' . urlencode($msg));
  exit;
}

// Cancelar
$up = $pdo->prepare("UPDATE turnos SET estado = 'cancelado' WHERE id = ?");
$ok = $up->execute([$turnoId]);

$msgOk = $ok ? 'Turno cancelado' : 'No se pudo cancelar el turno';
if ($wantsJson) {
  json_out(['ok'=>$ok, 'msg'=>$msgOk]);
} else {
  header('Location: vista_turnos.php?type=' . ($ok?'success':'danger') . '&msg=' . urlencode($msgOk));
}
