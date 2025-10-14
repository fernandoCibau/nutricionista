<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 3) {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../../config.php';

$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$new2 = $_POST['new_password_confirm'] ?? '';

if (empty($current) || empty($new) || $new !== $new2) {
    header('Location: index.php?error=campos_vacios');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($current, $user['password'])) {
        header('Location: index.php?error=password_incorrecta');
        exit;
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    $upd = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    $upd->execute([$hash, $_SESSION['user_id']]);

    header('Location: index.php?exito=password_cambiada');
    exit;
} catch (PDOException $e) {
    error_log("Error al cambiar password: " . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}

?>
