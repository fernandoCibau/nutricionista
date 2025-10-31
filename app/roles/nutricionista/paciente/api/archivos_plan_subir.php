<?php
session_start();
require_once '../../../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$pacienteId = (int)($_POST['paciente_id'] ?? 0);
$nombreAmigable = trim($_POST['nombre_archivo'] ?? '');

if ($pacienteId <= 0 || !isset($_FILES['pdf'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos o archivo.']);
    exit;
}

try {
    // Validar pertenencia del paciente
    $st = $pdo->prepare("SELECT p.id FROM pacientes p JOIN nutricionistas n ON n.id=p.id_nutricionista WHERE p.id=? AND n.id_usuario=? LIMIT 1");
    $st->execute([$pacienteId, $_SESSION['user_id']]);
    if (!$st->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Paciente no encontrado']);
        exit;
    }

    // Validar archivo PDF
    $file = $_FILES['pdf'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Error al subir el archivo.']);
        exit;
    }
    $maxBytes = 15 * 1024 * 1024; // 15 MB
    if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
        echo json_encode(['success' => false, 'message' => 'Archivo inválido o demasiado grande (máx 15MB).']);
        exit;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos PDF.']);
        exit;
    }

    // Preparar carpeta destino relativa al proyecto: uploads/dietas
    $projectRoot = dirname(__DIR__, 5); // .../nutricionista
    $uploadsDir = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'dietas';
    if (!is_dir($uploadsDir)) {
        if (!mkdir($uploadsDir, 0775, true) && !is_dir($uploadsDir)) {
            throw new RuntimeException('No se pudo crear la carpeta de destino.');
        }
    }

    // Nombre de archivo seguro
    $safeBase = 'paciente_' . $pacienteId . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
    $destName = $safeBase . '.pdf';
    $destPath = $uploadsDir . DIRECTORY_SEPARATOR . $destName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new RuntimeException('No se pudo mover el archivo.');
    }

    // Guardar en BD ruta relativa desde la raíz del proyecto
    $relativeUrl = 'uploads/dietas/' . $destName; // ej: uploads/dietas/paciente_1_20251030_101010_abc123.pdf
    $nombreBD = $nombreAmigable !== '' ? $nombreAmigable : (pathinfo($file['name'], PATHINFO_FILENAME));

    $ins = $pdo->prepare("INSERT INTO archivos_plan (id_paciente, nombre_archivo, url_archivo) VALUES (?,?,?)");
    $ins->execute([$pacienteId, $nombreBD, $relativeUrl]);

    echo json_encode(['success' => true, 'message' => 'PDF subido correctamente', 'url' => $relativeUrl, 'nombre' => $nombreBD]);
} catch (Throwable $e) {
    error_log('archivos_plan_subir: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al procesar el archivo.']);
}

