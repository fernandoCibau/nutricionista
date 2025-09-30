<?php

// 1. Iniciar la sesión para poder acceder a ella.
session_start();

// 2. Destruir todas las variables de la sesión.
$_SESSION = array();

// 3. Borrar la cookie de sesión del navegador.
// Esto es una medida de seguridad adicional.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finalmente, destruir la sesión en el servidor.
session_destroy();

// 5. Redirigir al usuario a la página de inicio de sesión con un mensaje de éxito.
header("Location: index.php?exito=logout");
exit;
?>