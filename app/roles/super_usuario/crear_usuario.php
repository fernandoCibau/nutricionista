<?php
// Importar las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. Iniciar la sesión y verificar el rol de superadmin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 1) {
    header('Location: ../../index.php');
    exit;
}

// 2. Verificar que la solicitud sea por método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 3. Incluir dependencias
require_once '../../config.php';
require_once '../../libs/vendor/autoload.php';

// 4. Obtener y validar los datos del formulario
$nombre = trim($_POST['user_name'] ?? '');
$email = trim($_POST['user_email'] ?? '');
$password = $_POST['user_password'] ?? ''; // Contraseña temporal
$role_id = filter_var($_POST['user_role_id'] ?? '', FILTER_VALIDATE_INT);
$nutricionista_id = filter_var($_POST['nutricionista_id'] ?? null, FILTER_VALIDATE_INT);

if (empty($nombre) || empty($email) || empty($password) || $role_id === false) {
    header('Location: index.php?error=campos_vacios');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.php?error=email_invalido');
    exit;
}

// Iniciar una transacción para asegurar la integridad de los datos
$pdo->beginTransaction();

try {
    // 5. Verificar si el email ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        header('Location: index.php?error=email_existente');
        exit;
    }

    // 6. Hashear la contraseña temporal
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // 7. Insertar el nuevo usuario en la base de datos
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, role_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nombre, $email, $password_hash, $role_id]);
    $user_id = $pdo->lastInsertId();

    // Crear registro adicional según el rol del usuario
if ($role_id === 2) { // Suponiendo que 2 es el ID para nutricionista
    // Si es nutricionista
    $stmtNutri = $pdo->prepare("INSERT INTO nutricionistas (id_usuario, estado) VALUES (?, 'pendiente')");
    $stmtNutri->execute([$user_id]);
}

if ($role_id === 3 && $nutricionista_id) { // Suponiendo que 3 es el ID para paciente
    // Si es paciente, lo asociamos al nutricionista seleccionado.
    // Necesitamos el ID de la tabla 'nutricionistas', no el 'id_usuario' del nutricionista.
    $stmtGetNutriTableId = $pdo->prepare("SELECT id FROM nutricionistas WHERE id_usuario = ?");
    $stmtGetNutriTableId->execute([$nutricionista_id]);
    $id_nutri_tabla = $stmtGetNutriTableId->fetchColumn();

    if ($id_nutri_tabla) {
        $stmtPaciente = $pdo->prepare("INSERT INTO pacientes (id_usuario, id_nutricionista, estado) VALUES (?, ?, 'activo')");
        $stmtPaciente->execute([$user_id, $id_nutri_tabla]);
    }
    // Considerar qué hacer si no se encuentra el id_nutri_tabla (ej. log de error)
}

    // 8. Generar un token para el primer cambio de contraseña del usuario principal
    sendWelcomeEmail($user_id, $nombre, $email, $pdo);

    // 11. Si todo fue exitoso, confirmar la transacción
    $pdo->commit();
    header('Location: index.php?exito=usuario_creado');
    exit;

} catch (Exception $e) { // Catch PHPMailer exceptions as well
    // Si hay un error de base de datos o de envío de email, revertir la transacción
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al crear usuario: " . $e->getMessage());
    // Check if the error was from email sending
    if (strpos($e->getMessage(), 'Error al enviar correo de bienvenida') !== false) {
        header('Location: index.php?error=email_fallido');
    } else {
        header('Location: index.php?error=db_error');
    }
    exit;
}

/**
 * Helper function to send welcome email with password reset link.
 * @param int $user_id The ID of the user to send the email to.
 * @param string $user_name The name of the user.
 * @param string $user_email The email of the user.
 * @param PDO $pdo The PDO database connection object.
 * @throws Exception If email sending fails.
 */
function sendWelcomeEmail($user_id, $user_name, $user_email, $pdo) {
    // Generar un token para el primer cambio de contraseña
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $expira = date('Y-m-d H:i:s', time() + 86400); // 24 horas de validez

    // Guardar el token en la base de datos para el nuevo usuario
    $updateStmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_token_exp = ? WHERE id = ?");
    $updateStmt->execute([$token_hash, $expira, $user_id]);

    // Enviar el email de bienvenida y para establecer contraseña
    $link = "http://localhost/nutri/app/resetear.php?token=" . $token;
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP (Gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'fcibau846@alumnos.frh.utn.edu.ar'; // Tu usuario de Gmail
        $mail->Password   = 'xawdnpemsrindpiq'; // Tu contraseña de aplicación de Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        // Emisor y Destinatario
        $mail->setFrom('no-reply@nutriapp.com', 'NutriApp'); // Replace with your actual sender email
        $mail->addAddress($user_email, $user_name);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = '¡Bienvenido a NutriApp! Establece tu contraseña';
        $mail->Body    = "
            Hola " . htmlspecialchars($user_name) . ",<br><br>
            Se ha creado una cuenta para ti en NutriApp. Para empezar, necesitas establecer tu contraseña.<br><br>
            Haz clic en el siguiente enlace para crear tu contraseña segura:<br>
            <a href='" . $link . "'>" . $link . "</a><br><br>
            Este enlace es de un solo uso y expirará en 24 horas.<br><br>
            ¡Te damos la bienvenida a bordo!";
        $mail->AltBody = "Hola " . htmlspecialchars($user_name) . ",\n\nSe ha creado una cuenta para ti en NutriApp. Para empezar, copia y pega el siguiente enlace en tu navegador para crear tu contraseña:\n" . $link . "\n\nEste enlace expirará en 24 horas.";

        $mail->send();

    } catch (Exception $e) { // Catch PHPMailer exceptions
        // Re-throw the exception to be caught by the main try-catch block
        throw new Exception("Error al enviar correo de bienvenida: {$mail->ErrorInfo}");
    }
}
?>
        $stmtNutri = $pdo->prepare("INSERT INTO nutricionistas (id_usuario, estado) VALUES (?, 'pendiente')");
        $stmtNutri->execute([$user_id]);
    }

    if ($role_id === $paciente_role_id) {
        // Insert into pacientes table, linking to the assigned nutritionist
        $stmtPaciente = $pdo->prepare("
            INSERT INTO pacientes (id_usuario, id_nutricionista, estado)
            VALUES (?, ?, 'activo')
        ");
        // Note: id_nutricionista in 'pacientes' table refers to the ID from the 'nutricionistas' table, not 'usuarios' table.
        // We need to get the 'nutricionistas.id' from the 'usuarios.id' of the assigned nutritionist.
        $stmt_get_nutri_table_id = $pdo->prepare("SELECT id FROM nutricionistas WHERE id_usuario = ? LIMIT 1");
        $stmt_get_nutri_table_id->execute([$final_assigned_nutricionista_user_id]);
        $nutri_table_id = $stmt_get_nutri_table_id->fetchColumn();

        if (!$nutri_table_id) {
            $pdo->rollBack();
            header('Location: index.php?error=nutri_table_id_not_found');
            exit;
        }

        $stmtPaciente->execute([$user_id, $nutri_table_id]);
    }

    // 8. Generar un token para el primer cambio de contraseña del usuario principal
    sendWelcomeEmail($user_id, $nombre, $email, $pdo);

    // 11. Si todo fue exitoso, confirmar la transacción
    $pdo->commit();
    header('Location: index.php?exito=usuario_creado');
    exit;

} catch (Exception $e) { // Catch PHPMailer exceptions as well
    // Si hay un error de base de datos o de envío de email, revertir la transacción
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al crear usuario: " . $e->getMessage());
    // Check if the error was from email sending
    if (strpos($e->getMessage(), 'Error al enviar correo de bienvenida') !== false) {
        header('Location: index.php?error=email_fallido');
    } else {
        header('Location: index.php?error=db_error');
    }
    exit;
}

/**
 * Helper function to send welcome email with password reset link.
 * @param int $user_id The ID of the user to send the email to.
 * @param string $user_name The name of the user.
 * @param string $user_email The email of the user.
 * @param PDO $pdo The PDO database connection object.
 * @throws Exception If email sending fails.
 */
function sendWelcomeEmail($user_id, $user_name, $user_email, $pdo) {
    // Generar un token para el primer cambio de contraseña
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $expira = date('Y-m-d H:i:s', time() + 86400); // 24 horas de validez

    // Guardar el token en la base de datos para el nuevo usuario
    $updateStmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_token_exp = ? WHERE id = ?");
    $updateStmt->execute([$token_hash, $expira, $user_id]);

    // Enviar el email de bienvenida y para establecer contraseña
    $link = "http://localhost/nutri/app/resetear.php?token=" . $token;
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP (Gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'fcibau846@alumnos.frh.utn.edu.ar'; // Tu usuario de Gmail
        $mail->Password   = 'xawdnpemsrindpiq'; // Tu contraseña de aplicación de Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        // Emisor y Destinatario
        $mail->setFrom('no-reply@nutriapp.com', 'NutriApp'); // Replace with your actual sender email
        $mail->addAddress($user_email, $user_name);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = '¡Bienvenido a NutriApp! Establece tu contraseña';
        $mail->Body    = "
            Hola " . htmlspecialchars($user_name) . ",<br><br>
            Se ha creado una cuenta para ti en NutriApp. Para empezar, necesitas establecer tu contraseña.<br><br>
            Haz clic en el siguiente enlace para crear tu contraseña segura:<br>
            <a href='" . $link . "'>" . $link . "</a><br><br>
            Este enlace es de un solo uso y expirará en 24 horas.<br><br>
            ¡Te damos la bienvenida a bordo!";
        $mail->AltBody = "Hola " . htmlspecialchars($user_name) . ",\n\nSe ha creado una cuenta para ti en NutriApp. Para empezar, copia y pega el siguiente enlace en tu navegador para crear tu contraseña:\n" . $link . "\n\nEste enlace expirará en 24 horas.";

        $mail->send();

    } catch (Exception $e) { // Catch PHPMailer exceptions
        // Re-throw the exception to be caught by the main try-catch block
        throw new Exception("Error al enviar correo de bienvenida: {$mail->ErrorInfo}");
    }
}
?>
