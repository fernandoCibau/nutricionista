<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../../config.php';

$hora_inicio = trim($_POST['hora_inicio'] ?? '');
$hora_fin = trim($_POST['hora_fin'] ?? '');
$dias_semana = $_POST['dias_semana'] ?? []; // array de dias: 1-7

if (empty($hora_inicio) || empty($hora_fin) || empty($dias_semana)) {
    header('Location: index.php?error=campos_vacios');
    exit;
}

$check = $pdo->query("SHOW TABLES LIKE 'jornadas'");
if (!$check || $check->rowCount() === 0) {
    header('Location: index.php?error=tabla_jornadas_no_exist');
    exit;
}

try {
    // Guardar: simplificamos y guardamos una única fila por nutricionista reemplazando la anterior
    $del = $pdo->prepare("DELETE FROM jornadas WHERE id_nutricionista = ?");
    $del->execute([$_SESSION['user_id']]);

    $ins = $pdo->prepare("INSERT INTO jornadas (id_nutricionista, hora_inicio, hora_fin, dias_semana, creado_en) VALUES (?, ?, ?, ?, NOW())");
    $ins->execute([$_SESSION['user_id'], $hora_inicio, $hora_fin, implode(',', $dias_semana)]);

    header('Location: index.php?exito=jornada_guardada');
    exit;
} catch (PDOException $e) {
    error_log('Error al guardar jornada: ' . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}

?>
