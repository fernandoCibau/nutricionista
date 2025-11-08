<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
  http_response_code(403);
  echo json_encode(['success'=>false,'message'=>'No autorizado']);
  exit;
}

require_once '../../../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success'=>false,'message'=>'Método inválido']);
  exit;
}

try {
  $uid = (int)$_SESSION['user_id'];
  $stn = $pdo->prepare("SELECT id FROM nutricionistas WHERE id_usuario = ? LIMIT 1");
  $stn->execute([$uid]);
  $nutri = $stn->fetch(PDO::FETCH_ASSOC);
  if (!$nutri) { echo json_encode(['success'=>false,'message'=>'Nutricionista no encontrado']); exit; }
  $nid = (int)$nutri['id'];

  $provincia = isset($_POST['provincia']) ? trim($_POST['provincia']) : '';
  $localidad = isset($_POST['localidad']) ? trim($_POST['localidad']) : '';
  $calle     = isset($_POST['calle']) ? trim($_POST['calle']) : '';
  $numero    = isset($_POST['numero']) ? trim($_POST['numero']) : '';

  if ($provincia === '' || $localidad === '' || $calle === '' || $numero === '') {
    echo json_encode(['success'=>false,'message'=>'Completa todos los campos']);
    exit;
  }

  // Evitar duplicados por unique key
  $ins = $pdo->prepare("INSERT INTO nutricionista_direcciones (id_nutricionista, provincia, localidad, calle, numero) VALUES (?,?,?,?,?)");
  $ins->execute([$nid, $provincia, $localidad, $calle, $numero]);

  echo json_encode(['success'=>true,'message'=>'Dirección agregada']);
} catch (PDOException $e) {
  if ((int)$e->getCode() === 23000) { // Duplicate
    echo json_encode(['success'=>false,'message'=>'La dirección ya existe']);
  } else {
    error_log('perfil/direccion_agregar: '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Error interno']);
  }
}

