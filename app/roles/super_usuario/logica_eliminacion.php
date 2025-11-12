<?php
/**
 * Lógica para eliminaciones en cascada.
 * Este script es incluido por otros y asume que $pdo está disponible.
 */

if (!isset($pdo)) {
    die('Error: La conexión a la base de datos no está disponible.');
}

/**
 * Elimina un paciente y todos sus datos asociados de forma segura.
 *
 * @param int $paciente_user_id ID de usuario del paciente.
 * @param PDO $pdo Objeto de conexión a la base de datos.
 * @return void
 * @throws PDOException
 */
function eliminarPacienteCompleto(int $paciente_user_id, PDO $pdo): void {
    // Obtener el ID de la tabla 'pacientes'
    $stmt = $pdo->prepare("SELECT id FROM pacientes WHERE id_usuario = ?");
    $stmt->execute([$paciente_user_id]);
    $paciente_id = $stmt->fetchColumn();

    // La mayoría de las tablas relacionadas con 'pacientes.id' usan ON DELETE CASCADE.
    // Esto significa que la base de datos eliminará automáticamente los registros en:
    // - diario
    // - archivos_plan
    // - consultas
    // - habitos (y habit_completados por transitividad)
    // - facturas
    // - pagos
    // - metodos_pago

    if ($paciente_id) {
        // Solo necesitamos eliminar manualmente los turnos, ya que no tienen ON DELETE CASCADE.
        $pdo->prepare("DELETE FROM turnos WHERE id_paciente = ?")->execute([$paciente_id]);
        
        // Finalmente, eliminamos el registro de la tabla 'pacientes'.
        $pdo->prepare("DELETE FROM pacientes WHERE id = ?")->execute([$paciente_id]);
    }

    // Por último, eliminamos el registro de la tabla 'usuarios'.
    $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$paciente_user_id]);
}

/**
 * Elimina un nutricionista y todos sus datos asociados, incluyendo sus pacientes.
 *
 * @param int $nutri_user_id ID de usuario del nutricionista.
 * @param PDO $pdo Objeto de conexión a la base de datos.
 * @return int El número de pacientes eliminados.
 * @throws PDOException
 */
function eliminarNutricionistaCompleto(int $nutri_user_id, PDO $pdo): int {
    // Obtener el ID de la tabla 'nutricionistas'
    $pacientes_eliminados_count = 0;
    $stmt = $pdo->prepare("SELECT id FROM nutricionistas WHERE id_usuario = ?");
    $stmt->execute([$nutri_user_id]);
    $nutri_id = $stmt->fetchColumn();

    if ($nutri_id) {
        // 1. Encontrar todos los pacientes de este nutricionista
        $stmt_pacientes = $pdo->prepare("SELECT id_usuario FROM pacientes WHERE id_nutricionista = ?");
        $stmt_pacientes->execute([$nutri_id]);
        $pacientes_user_ids = $stmt_pacientes->fetchAll(PDO::FETCH_COLUMN);

        $pacientes_eliminados_count = count($pacientes_user_ids);
        // 2. Eliminar cada paciente y sus datos asociados
        // La función eliminarPacienteCompleto se encargará de todo lo relacionado al paciente.
        foreach ($pacientes_user_ids as $paciente_user_id) {
            eliminarPacienteCompleto((int)$paciente_user_id, $pdo);
        }

        // 3. Eliminar otros datos del nutricionista que no se borran en cascada.
        // La tabla 'recetas' tiene ON DELETE CASCADE, por lo que no es necesario aquí.
        $pdo->prepare("DELETE FROM turnos WHERE id_nutricionista = ?")->execute([$nutri_id]);
        // Las tablas 'jornadas' y 'dias_no_laborales' no existen en tu script SQL, las he quitado.
        // La tabla 'dietas' tampoco existe.

        // 4. Finalmente, eliminar de la tabla 'nutricionistas'
        $pdo->prepare("DELETE FROM nutricionistas WHERE id = ?")->execute([$nutri_id]);
    }

    // 5. Eliminar el registro principal del usuario
    $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$nutri_user_id]);

    return $pacientes_eliminados_count;
}

/**
 * Obtiene el nombre del rol de un usuario.
 *
 * @param int $user_id
 * @param PDO $pdo
 * @return string|null
 */
function obtenerRolUsuario(int $user_id, PDO $pdo): ?string {
    $stmt = $pdo->prepare("SELECT r.nombre FROM roles r JOIN usuarios u ON u.role_id = r.id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn() ?: null;
}