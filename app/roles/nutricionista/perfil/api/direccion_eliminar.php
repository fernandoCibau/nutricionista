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
  $id  = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
  if (!$id) { echo json_encode(['success'=>false,'message'=>'ID inválido']); exit; }

  // Validar pertenencia
  $q = $pdo->prepare("SELECT d.id FROM nutricionista_direcciones d JOIN nutricionistas n ON n.id = d.id_nutricionista WHERE d.id = ? AND n.id_usuario = ? LIMIT 1");
  $q->execute([$id, $uid]);
  if (!$q->fetch()) { echo json_encode(['success'=>false,'message'=>'No encontrado']); exit; }

  $del = $pdo->prepare("DELETE FROM nutricionista_direcciones WHERE id = ? LIMIT 1");
  $del->execute([$id]);

  echo json_encode(['success'=>true]);
} catch (Throwable $e) {
  error_log('perfil/direccion_eliminar: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Error interno']);
}

