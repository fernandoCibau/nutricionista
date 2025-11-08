<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 2) {
    header('Location: ../../index.php');
    exit;
}

require_once __DIR__ . '/../../config.php';

// Crear o actualizar las tablas de hábitos
try {
    // Tabla principal de hábitos
    $pdo->exec("CREATE TABLE IF NOT EXISTS habitos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_paciente INT NOT NULL,
        id_nutricionista INT NOT NULL,
        descripcion TEXT NOT NULL,
        tipo VARCHAR(50) NOT NULL DEFAULT 'personalizado',
        estado VARCHAR(20) NOT NULL DEFAULT 'activo',
        meta_diaria INT DEFAULT 1,
        creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_paciente) REFERENCES pacientes(id) ON DELETE CASCADE,
        FOREIGN KEY (id_nutricionista) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tabla de seguimiento diario
    $pdo->exec("CREATE TABLE IF NOT EXISTS habit_completados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_habito INT NOT NULL,
        id_paciente INT NOT NULL,
        fecha DATE NOT NULL,
        cantidad INT DEFAULT 1,
        notas TEXT,
        creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_hab_fecha (id_habito, id_paciente, fecha),
        FOREIGN KEY (id_habito) REFERENCES habitos(id) ON DELETE CASCADE,
        FOREIGN KEY (id_paciente) REFERENCES pacientes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    header('Location: gestionar_habitos.php?success=tablas_actualizadas');
    exit;
} catch (PDOException $e) {
    error_log("Error al actualizar tablas de hábitos: " . $e->getMessage());
    header('Location: gestionar_habitos.php?error=db_error');
    exit;
}