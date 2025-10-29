<?php
// Importar las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1) Iniciar sesión y verificar rol de NUTRICIONISTA (rol_id = 2)
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    header('Location: ../../index.php');
    exit;
}

// 2) Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 3) Incluir dependencias
require_once '../../config.php';                  // Debe definir $pdo (PDO)
require_once '../../libs/vendor/autoload.php';    // PHPMailer via Composer

// 4) Obtener y validar datos del formulario
$nombre   = trim($_POST['user_name'] ?? '');
$email    = trim($_POST['user_email'] ?? '');
$password = $_POST['user_password'] ?? ''; // Contraseña temporal
$role_id  = filter_var($_POST['user_role_id'] ?? '', FILTER_VALIDATE_INT);

// Datos opcionales para la tabla 'pacientes'
$dni              = trim($_POST['dni'] ?? '');
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null; // 'YYYY-MM-DD'
$telefono         = trim($_POST['telefono'] ?? '');
$objetivo         = trim($_POST['objetivo_principal'] ?? '');

// Validar rol: debe ser 'paciente'
$stmtRole = $pdo->prepare("SELECT nombre FROM roles WHERE id = ? LIMIT 1");
$stmtRole->execute([$role_id]);
$roleRow = $stmtRole->fetch(PDO::FETCH_ASSOC);

if (!$roleRow || mb_strtolower($roleRow['nombre']) !== 'paciente') {
    header('Location: index.php?error=rol_invalido');
    exit;
}

if (empty($nombre) || empty($email) || empty($password) || $role_id === false) {
    header('Location: index.php?error=campos_vacios');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.php?error=email_invalido');
    exit;
}

try {
    // Forzar excepciones PDO si no está habilitado en config.php
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 5) Iniciar transacción
    $pdo->beginTransaction();

    // 6) Verificar si el email ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->rollBack();
        header('Location: index.php?error=email_existente');
        exit;
    }

    // 7) Hashear contraseña temporal
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // 8) Insertar en 'usuarios' con estado 'activo'
    $estadoActivoId = (int)$pdo->query("SELECT id FROM estados WHERE nombre = 'activo' LIMIT 1")->fetchColumn();
    $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, email, password, role_id, id_estado)
            VALUES (?, ?, ?, ?, ?)
        ");
    $stmt->execute([$nombre, $email, $password_hash, $role_id, $estadoActivoId ?: null]);
    $user_id = (int)$pdo->lastInsertId();

    // 8.1) Obtener el ID del nutricionista (tabla 'nutricionistas') del usuario logueado
    $nutriStmt = $pdo->prepare("SELECT id FROM nutricionistas WHERE id_usuario = ? LIMIT 1");
    $nutriStmt->execute([$_SESSION['user_id']]);
    $nutriRow = $nutriStmt->fetch(PDO::FETCH_ASSOC);

    if (!$nutriRow) {
        // No hay fila de nutricionista configurada para el usuario actual: revertimos
        $pdo->rollBack();
        header('Location: index.php?error=nutri_no_configurado');
        exit;
    }
    $idNutricionista = (int)$nutriRow['id'];

    // 8.2) Crear fila en 'pacientes' enlazada al nuevo usuario y al nutricionista creador
    $pacStmt = $pdo->prepare("
        INSERT INTO pacientes (id_usuario, id_nutricionista, dni, fecha_nacimiento, telefono, objetivo_principal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $pacStmt->execute([
        $user_id,
        $idNutricionista,
        $dni !== '' ? $dni : null,
        $fecha_nacimiento ?: null,
        $telefono !== '' ? $telefono : null,
        $objetivo !== '' ? $objetivo : null
    ]);

    // 9) Generar token para primer cambio de contraseña (24 horas)
    $token      = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $token);
    $expira     = date('Y-m-d H:i:s', time() + 86400); // 24 * 60 * 60

    // 10) Guardar token en el usuario
    $updateStmt = $pdo->prepare("
        UPDATE usuarios SET reset_token = ?, reset_token_exp = ? WHERE id = ?
    ");
    $updateStmt->execute([$token_hash, $expira, $user_id]);

    // 11) Enviar email de bienvenida con enlace para establecer contraseña
    $link = "http://localhost/nutri/app/resetear.php?token=" . $token;
    $mail = new PHPMailer(true);

    // Configuración SMTP (Gmail)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'fcibau846@alumnos.frh.utn.edu.ar'; // Usuario Gmail
    $mail->Password   = 'xawdnpemsrindpiq';                  // Contraseña de aplicación
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    // Remitente y destinatario
    $mail->setFrom('no-reply@nutriapp.com', 'NutriApp');
    $mail->addAddress($email, $nombre);

    // Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = '¡Bienvenido a NutriApp! Establece tu contraseña';
    $mail->Body    = "
        Hola " . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . ",<br><br>
        Se ha creado una cuenta para ti en <strong>NutriApp</strong> como <em>paciente</em>.<br><br>
        Para empezar, establece tu contraseña haciendo clic en el siguiente enlace:<br>
        <a href='" . $link . "'>" . $link . "</a><br><br>
        Este enlace es de un solo uso y expirará en <strong>24 horas</strong>.<br><br>
        ¡Te damos la bienvenida!
    ";
    $mail->AltBody = "Hola " . $nombre . ",
Se ha creado una cuenta para ti en NutriApp como paciente.
Para empezar, copia y pega este enlace en tu navegador para crear tu contraseña:
" . $link . "

Este enlace expirará en 24 horas.";

    try {
        $mail->send();
    } catch (Exception $e) {
        // Si falla el envío, revertimos todo para no dejar cuentas inaccesibles
        $pdo->rollBack();
        error_log("Error al enviar correo de bienvenida: {$mail->ErrorInfo}");
        header('Location: index.php?error=email_fallido');
        exit;
    }

    // 12) Confirmar la transacción
    $pdo->commit();
    header('Location: index.php?exito=usuario_creado');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al crear usuario/paciente: " . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}
