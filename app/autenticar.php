<?php
/**
 * Script para procesar el inicio de sesión.
 * Verifica las credenciales del usuario contra la base de datos.
 */

// 1. Iniciar la sesión para poder guardar los datos del usuario si el login es exitoso.
session_start();

// 2. Incluir el archivo de configuración para obtener la conexión a la BD ($pdo).
require_once 'config.php';

// 3. Asegurarse de que el script se ejecuta por una petición GET desde el formulario.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si alguien intenta acceder a este archivo directamente, lo redirigimos al login.
    header('Location: index.php');
    exit;
}

// 4. Obtener los datos del formulario. Usamos trim() para eliminar espacios en blanco.
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// 5. Validar que los campos no estén vacíos.
if (empty($email) || empty($password)) {
    // Redirigir de vuelta al login con un mensaje de error.
    header('Location: index.php?error=campos_vacios');
    exit;
}

try {
    // 6. Buscar al usuario por su email en la base de datos.
    $stmt = $pdo->prepare("SELECT id, nombre, email, password, role_id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]); // Se pasa el email como parámetro aquí
    $user = $stmt->fetch();

    // 7. Verificar si se encontró un usuario y si la contraseña es correcta.
    // usamos password_verify() porque la contraseña en la BD está hasheada.
    if ($user && password_verify($password, $user['password'])) {
        // ¡Credenciales correctas!

        // 8. Regenerar el ID de sesión por seguridad.
        session_regenerate_id(true);

        // 9. Guardar los datos del usuario en la sesión.
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_rol'] = $user['role_id']; // Guardamos el rol para control de acceso

        // 10. Redirigir según el rol del usuario.
        if ($user['role_id'] === 1) { // Suponiendo que 1 es el ID para super usuario
            header('Location: roles/super_usuario/index.php');
        } elseif ($user['role_id'] === 2) { // Suponiendo que 2 es el ID para nutricionista
            header('Location: roles/nutricionista/index.php');
        } elseif ($user['role_id'] === 3) { // Suponiendo que 3 es el ID para paciente
            header('Location: roles/paciente/index.php');
        }
        exit;
    } else {
        // Si el usuario no existe o la contraseña es incorrecta.
        header('Location: index.php?error=credenciales_invalidas');
        exit;
    }
} catch (PDOException $e) {
    // En caso de un error de base de datos, redirigir con un error genérico.
    // En un entorno real, registrarías el error en un log.
    error_log("Error de login: " . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}