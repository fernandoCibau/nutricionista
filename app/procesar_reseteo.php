<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 1. Obtener los datos del formulario.
$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// 2. Validaciones básicas.
if (empty($token) || empty($password) || empty($password_confirm)) {
    header('Location: index.php'); // Redirigir si faltan datos clave.
    exit;
}

if (strlen($password) < 6) {
    header('Location: resetear.php?token=' . urlencode($token) . '&error=password_corta');
    exit;
}

if ($password !== $password_confirm) {
    header('Location: resetear.php?token=' . urlencode($token) . '&error=password_no_coincide');
    exit;
}

try {
    // 3. Hashear el token para buscarlo en la base de datos.
    $token_hash = hash('sha256', $token);

    // 4. Buscar el usuario por el token y verificar que no haya expirado.
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_token_exp > NOW()");
    $stmt->execute([$token_hash]); // Ahora $token_hash coincide con lo guardado en la BD
    $user = $stmt->fetch();

    if (!$user) {
        // Si no se encuentra el usuario o el token ha expirado.
        header('Location: recuperar.php?error=token_expirado');
        exit;
    }

    // 5. Hashear la nueva contraseña.
    $new_password_hash = password_hash($password, PASSWORD_DEFAULT);

    // 6. Actualizar la contraseña y BORRAR el token.
    $updateStmt = $pdo->prepare(
        "UPDATE usuarios SET password = ?, reset_token = NULL, reset_token_exp = NULL WHERE id = ?"
    );
    $updateStmt->execute([$new_password_hash, $user['id']]);

    // 7. Redirigir al login con un mensaje de éxito.
    header('Location: index.php?exito=password_actualizada');
    exit;

} catch (PDOException $e) {
    error_log("Error al resetear contraseña: " . $e->getMessage());
    header('Location: resetear.php?token=' . urlencode($token) . '&error=db_error');
    exit;
}
?>
