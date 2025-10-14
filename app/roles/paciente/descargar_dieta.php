<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 3) {
    header('Location: ../../index.php');
    exit;
}

require_once __DIR__ . '/../../config.php';

// Recibir título y creado_en como parámetros para identificar la receta (ya que recetas no están ligadas al paciente por tabla)
$titulo = $_GET['titulo'] ?? '';
$creado_en = $_GET['creado_en'] ?? '';
if (empty($titulo) || empty($creado_en)) {
    header('Location: index.php?error=dieta_invalida');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT titulo, contenido, creado_en FROM recetas WHERE titulo = ? AND creado_en = ? AND publicado = 1 LIMIT 1");
    $stmt->execute([$titulo, $creado_en]);
    $receta = $stmt->fetch();
    if (!$receta) {
        header('Location: index.php?error=dieta_no_encontrada');
        exit;
    }

    // Generar un archivo de texto simple con la receta
    $filename = preg_replace('/[^a-zA-Z0-9-_]/', '_', $receta['titulo']) . '_' . date('Ymd_His', strtotime($receta['creado_en'])) . '.txt';
    $content = "Titulo: " . $receta['titulo'] . "\n\n" . $receta['contenido'];

    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $content;
    exit;
} catch (PDOException $e) {
    error_log("Error al descargar receta: " . $e->getMessage());
    header('Location: index.php?error=db_error');
    exit;
}

?>
