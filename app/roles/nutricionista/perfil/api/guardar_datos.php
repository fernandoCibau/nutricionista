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
  $nombre = isset($_POST['nombre']) ? trim((string)$_POST['nombre']) : '';
  $email  = isset($_POST['email'])  ? trim((string)$_POST['email'])  : '';

  // Leer actuales
  $st = $pdo->prepare("SELECT nombre, email FROM usuarios WHERE id = ? LIMIT 1");
  $st->execute([$uid]);
  $curr = $st->fetch(PDO::FETCH_ASSOC);
  if (!$curr) { echo json_encode(['success'=>false,'message'=>'Usuario no encontrado']); exit; }

  $nuevo_nombre = ($nombre !== '') ? $nombre : $curr['nombre'];
  $nuevo_email_in = ($email !== '') ? $email : $curr['email'];

  $nuevo_email = $nuevo_email_in;
  $email_cambiado = ($nuevo_email_in !== $curr['email']);
  if ($email_cambiado && !filter_var($nuevo_email_in, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success'=>false,'message'=>'Email inválido']);
    exit;
  }

  if ($email_cambiado) {
    $chk = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
    $chk->execute([$nuevo_email, $uid]);
    if ($chk->fetch()) {
      echo json_encode(['success'=>false,'message'=>'El email ya está en uso']);
      exit;
    }
  }

  $up = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?");
  $up->execute([$nuevo_nombre, $nuevo_email, $uid]);

  // Refrescar nombre en sesión si cambió
  $_SESSION['user_nombre'] = $nuevo_nombre;

  echo json_encode(['success'=>true,'message'=>'Datos actualizados']);
} catch (Throwable $e) {
  error_log('perfil/guardar_datos: '.$e->getMessage());
  echo json_encode(['success'=>false,'message'=>'Error interno']);
}
