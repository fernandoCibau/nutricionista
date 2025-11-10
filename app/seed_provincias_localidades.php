<?php
require_once 'config.php'; // Adjust path as needed

try {
    // Disable foreign key checks temporarily
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');

    // Clear existing data (optional, for re-seeding)
    $pdo->exec('TRUNCATE TABLE localidades;');
    $pdo->exec('TRUNCATE TABLE provincias;');

    // Insert sample provinces
    $stmt_provincia = $pdo->prepare("INSERT INTO provincias (nombre) VALUES (?)");
    $provincias = [
        'Buenos Aires',
        'Córdoba',
        'Santa Fe'
    ];
    foreach ($provincias as $provincia_nombre) {
        $stmt_provincia->execute([$provincia_nombre]);
    }

    // Get inserted province IDs
    $stmt_get_provincias = $pdo->query("SELECT id, nombre FROM provincias");
    $provincias_map = [];
    while ($row = $stmt_get_provincias->fetch(PDO::FETCH_ASSOC)) {
        $provincias_map[$row['nombre']] = $row['id'];
    }

    // Insert sample localities
    $stmt_localidad = $pdo->prepare("INSERT INTO localidades (nombre, ID_PROV) VALUES (?, ?)");
    $localidades_data = [
        'Buenos Aires' => ['La Plata', 'Mar del Plata', 'Bahía Blanca'],
        'Córdoba' => ['Córdoba Capital', 'Villa Carlos Paz', 'Río Cuarto'],
        'Santa Fe' => ['Rosario', 'Santa Fe Capital', 'Rafaela']
    ];

    foreach ($localidades_data as $provincia_nombre => $localidades_list) {
        $provincia_id = $provincias_map[$provincia_nombre];
        foreach ($localidades_list as $localidad_nombre) {
            $stmt_localidad->execute([$localidad_nombre, $provincia_id]);
        }
    }

    // Re-enable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');

    echo "Provincias y Localidades de ejemplo insertadas correctamente.\n";

} catch (PDOException $e) {
    echo "Error al insertar datos de ejemplo: " . $e->getMessage() . "\n";
    error_log("Error al insertar datos de ejemplo: " . $e->getMessage());
}
?>