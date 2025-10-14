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

$comentario = trim($_POST['comentario'] ?? '');
$tipo_comida = $_POST['tipo_comida'] ?? '';

$allowed = ['desayuno','almuerzo','merienda','cena'];
if (!in_array($tipo_comida, $allowed, true)) {
    header('Location: index.php?error=tipo_invalido');
    exit;
}

if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    header('Location: index.php?error=subida_foto');
    exit;
}

$file = $_FILES['foto'];
// Validar tamaño (5MB) y tipo
if ($file['size'] > 5 * 1024 * 1024) {
    header('Location: index.php?error=archivo_grande');
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$ext = null;
switch ($mime) {
    case 'image/jpeg': $ext = '.jpg'; break;
    case 'image/png': $ext = '.png'; break;
    case 'image/webp': $ext = '.webp'; break;
    default:
        header('Location: index.php?error=formato_no_admitido');
        exit;
}

$uploadDir = __DIR__ . '/../../public/uploads/comidas';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Guardamos la ruta relativa sin doble slash para que sea consistente
$relativePath = 'public/uploads/comidas/' . uniqid('comida_') . $ext; // ruta relativa desde la raiz del proyecto
$fullpath = __DIR__ . '/../../' . $relativePath;

if (!move_uploaded_file($file['tmp_name'], $fullpath)) {
    header('Location: index.php?error=guardar_foto');
    exit;
}

try {
    // Insertar en la tabla `diario` (id_paciente, fecha_hora, tipo_comida, detalles, url_foto)
    $stmt = $pdo->prepare("INSERT INTO diario (id_paciente, fecha_hora, tipo_comida, detalles, url_foto) VALUES (?, NOW(), ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $tipo_comida, $comentario, '/' . $relativePath]);
    header('Location: index.php?exito=comida_subida');
    exit;
} catch (PDOException $e) {
    error_log("Error al guardar diario: " . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}

?>
