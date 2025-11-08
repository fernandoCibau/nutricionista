<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
  http_response_code(403);
  echo json_encode(['success'=>false,'message'=>'No autorizado']);
  exit;
}

require_once '../../../../config.php';

try {
  $uid = (int)$_SESSION['user_id'];

  $st = $pdo->prepare("SELECT id, nombre, email FROM usuarios WHERE id = ? LIMIT 1");
  $st->execute([$uid]);
  $usuario = $st->fetch(PDO::FETCH_ASSOC);
  if (!$usuario) { echo json_encode(['success'=>false,'message'=>'Usuario no encontrado']); exit; }

  $stn = $pdo->prepare("SELECT id FROM nutricionistas WHERE id_usuario = ? LIMIT 1");
  $stn->execute([$uid]);
  $nutri = $stn->fetch(PDO::FETCH_ASSOC);
  $nutriId = $nutri ? (int)$nutri['id'] : 0;

  $dirs = [];
  if ($nutriId) {
    $stD = $pdo->prepare("SELECT id, provincia, localidad, calle, numero FROM nutricionista_direcciones WHERE id_nutricionista = ? ORDER BY provincia, localidad, calle, numero");
    $stD->execute([$nutriId]);
    $dirs = $stD->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }

  echo json_encode(['success'=>true,'usuario'=>$usuario,'direcciones'=>$dirs]);
} catch (Throwable $e) {
  error_log('perfil/mis_datos: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Error interno']);
}
