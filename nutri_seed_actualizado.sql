
-- Archivo: nutri_seed_actualizado.sql (datos de ejemplo usando tabla roles)
USE nutri;

-- Roles
INSERT INTO roles (nombre, descripcion) VALUES
('superadmin', 'Acceso total al sistema'),
('nutricionista', 'Cuenta profesional de nutricionista'),
('paciente', 'Cuenta de paciente');

-- Usuarios (role_id refiere a roles.id)
INSERT INTO usuarios (nombre, email, password, role_id) VALUES
('Admin Principal','admin@nutri.test','password123', 1),
('Lucia Gutierrez','lucia@nutri.test','password123', 2),
('Mateo Alvarez','mateo@nutri.test','password123', 2),
('Sofia Perez','sofia@nutri.test','password123', 3),
('Carlos Ruiz','carlos@nutri.test','password123', 3);

-- Nutricionistas (vinculados a usuarios 2 y 3)
INSERT INTO nutricionistas (id_usuario, nombre, email, estado) VALUES
(2, 'Lucía Gutiérrez', 'lucia@nutri.test', 'activa'),
(3, 'Mateo Álvarez', 'mateo@nutri.test', 'activa');

-- Pacientes (vinculados a usuarios y nutricionistas)
INSERT INTO pacientes (id_usuario, id_nutricionista, nombre_completo, dni, fecha_nacimiento, email, telefono, estado, objetivo_principal, progreso_objetivo) VALUES
(4, 1, 'Sofía Pérez', '30111222', '1995-04-12', 'sofia@correo.test', '1155550001', 'activo', 'Perder 5 kg', 20),
(5, 2, 'Carlos Ruiz', '30122333', '1988-09-01', 'carlos@correo.test', '1155550002', 'activo', 'Aumentar masa muscular', 10),
(NULL, 1, 'Paciente Demo', '00000000', '2000-01-01', 'demo@correo.test', '1155550003', 'activo', 'Mejorar hábitos', 0);

-- Recetas
INSERT INTO recetas (id_nutricionista, titulo, contenido, tags, publicado) VALUES
(1, 'Ensalada Mediterránea', 'Lechuga, tomate, pepino, aceitunas, queso feta. Aliño: aceite y limón.', 'ensalada,rapido,vegetariano', 1),
(1, 'Batido Proteico', 'Leche, plátano, proteína en polvo, avena.', 'batido,proteina,desayuno', 1),
(2, 'Pechuga a la plancha y quinoa', 'Pechuga de pollo, quinoa, verduras al vapor.', 'almuerzo,proteina', 1);

-- Turnos (citas)
INSERT INTO turnos (id_nutricionista, id_paciente, fecha_hora, estado, senia, pagado) VALUES
(1, 1, '2025-09-10 10:00:00', 'confirmado', 500.00, 1),
(2, 2, '2025-09-12 15:30:00', 'pendiente', 0.00, 0),
(1, 3, '2025-09-20 09:00:00', 'cancelado', 0.00, 0);

-- Consultas (registros)
INSERT INTO consultas (id_paciente, fecha, peso_kg, altura_cm, masa_muscular_pct, comentarios, habitos_cumplidos, objetivos_trabajados) VALUES
(1, '2025-09-10 10:30:00', 68.50, 165.0, 28.50, 'Buena adherencia la última semana.', 'Caminatas 4 veces/semana', 'Reducir 0.5 kg/semana'),
(2, '2025-09-12 16:00:00', 82.00, 178.0, 32.00, 'Necesita incrementar proteínas tras entrenamiento.', 'Entrenamiento 3x/semana', 'Aumentar 1 kg muscular mensual');

-- Diario (entradas alimentarias)
INSERT INTO diario (id_paciente, fecha_hora, tipo_comida, detalles, url_foto) VALUES
(1, '2025-09-09 08:30:00', 'desayuno', 'Avena con fruta y miel', NULL),
(1, '2025-09-09 13:00:00', 'almuerzo', 'Pollo con ensalada y arroz integral', NULL),
(2, '2025-09-11 20:00:00', 'cena', 'Salmón al horno con verduras', NULL);

-- Habitos
INSERT INTO habitos (id_paciente, nombre, color, visibilidad, creado_por, racha_dias) VALUES
(1, 'Caminar 30 min', '#28a745', 'publico', 'paciente', 7),
(1, 'Beber 2L agua', '#007bff', 'publico', 'nutricionista', 14),
(2, 'Entrenamiento fuerza', '#ffc107', 'privado', 'paciente', 3);

-- Archivos de plan (planfiles)
INSERT INTO archivos_plan (id_paciente, nombre_archivo, url_archivo) VALUES
(1, 'plan_semana_1.pdf', '/uploads/plan_semana_1.pdf'),
(2, 'plan_mensual.pdf', '/uploads/plan_mensual.pdf');
