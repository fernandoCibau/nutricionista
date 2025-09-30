<?php
/**
 * Archivo de configuración de la base de datos.
 * Centraliza los parámetros de conexión y crea el objeto PDO.
 */

// 1. Parámetros de conexión a la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'nutri');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// 2. Opciones de PDO para la conexión
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones en errores
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve arrays asociativos por defecto
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa preparaciones de consulta nativas
];

// 3. Crear la instancia de PDO para usarla en otros scripts
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // En un entorno real, aquí registrarías el error en un log y mostrarías un mensaje genérico.
    die("Error fatal: No se pudo conectar a la base de datos. Mensaje: " . $e->getMessage());
}