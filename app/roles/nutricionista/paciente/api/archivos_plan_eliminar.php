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
$archivoId  = (int)($_POST['archivo_id'] ?? 0);

if ($pacienteId <= 0 || $archivoId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

try {
    // Validar pertenencia y obtener URL
    $st = $pdo->prepare("SELECT ap.url_archivo
                         FROM archivos_plan ap
                         JOIN pacientes p ON p.id = ap.id_paciente
                         JOIN nutricionistas n ON n.id = p.id_nutricionista
                         WHERE ap.id = ? AND ap.id_paciente = ? AND n.id_usuario = ?
                         LIMIT 1");
    $st->execute([$archivoId, $pacienteId, $_SESSION['user_id']]);
    $url = $st->fetchColumn();
    if (!$url) {
        echo json_encode(['success' => false, 'message' => 'Archivo no encontrado']);
        exit;
    }

    // Borrar registro
    $del = $pdo->prepare("DELETE FROM archivos_plan WHERE id = ? LIMIT 1");
    $del->execute([$archivoId]);

    // Intentar borrar archivo físico si es ruta interna
    if (strpos($url, 'uploads/dietas/') === 0) {
        $projectRoot = dirname(__DIR__, 5); // .../nutricionista
        $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $url);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Archivo eliminado']);
} catch (Throwable $e) {
    error_log('archivos_plan_eliminar: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al eliminar archivo']);
}

