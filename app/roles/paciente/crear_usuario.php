<?php
// Importar las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1. Iniciar la sesión y verificar el rol de paciente
session_start();
// Los pacientes no pueden crear otros usuarios. Revocar acceso.
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 3) {
    header('Location: ../../index.php');
    exit;
}

// Denegamos explícitamente la acción para usuarios con rol paciente
header('Location: index.php?error=permiso_denegado');
exit;

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

    // 8. Generar un token para el primer cambio de contraseña
    $token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $expira = date('Y-m-d H:i:s', time() + 600); // 10 minutos de validez

    // 9. Guardar el token en la base de datos para el nuevo usuario
    $updateStmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_token_exp = ? WHERE id = ?");
    $updateStmt->execute([$token_hash, $expira, $user_id]);

    // 10. Enviar el email de bienvenida y para establecer contraseña
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
        $mail->setFrom('no-reply@nutriapp.com', 'NutriApp');
        $mail->addAddress($email, $nombre);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = '¡Bienvenido a NutriApp! Establece tu contraseña';
        $mail->Body    = "
            Hola " . htmlspecialchars($nombre) . ",<br><br>
            Se ha creado una cuenta para ti en NutriApp. Para empezar, necesitas establecer tu contraseña.<br><br>
            Haz clic en el siguiente enlace para crear tu contraseña segura:<br>
            <a href='" . $link . "'>" . $link . "</a><br><br>
            Este enlace es de un solo uso y expirará en 24 horas.<br><br>
            ¡Te damos la bienvenida a bordo!";
        $mail->AltBody = "Hola " . htmlspecialchars($nombre) . ",\n\nSe ha creado una cuenta para ti en NutriApp. Para empezar, copia y pega el siguiente enlace en tu navegador para crear tu contraseña:\n" . $link . "\n\nEste enlace expirará en 24 horas.";

        $mail->send();

    } catch (Exception $e) {
        // Si el email falla, revertimos la creación del usuario para no dejar cuentas inaccesibles
        $pdo->rollBack();
        error_log("Error al enviar correo de bienvenida: {$mail->ErrorInfo}");
        header('Location: index.php?error=email_fallido');
        exit;
    }

    // 11. Si todo fue exitoso, confirmar la transacción
    $pdo->commit();
    header('Location: index.php?exito=usuario_creado');
    exit;

} catch (PDOException $e) {
    // Si hay un error de base de datos, revertir la transacción
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al crear usuario: " . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}
?>
