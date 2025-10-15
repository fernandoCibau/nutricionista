
-- Base de datos: nutri (ejecutar CREATE DATABASE si hace falta)
-- Script actualizado: incluye tabla roles y FK desde usuarios -> roles
-- Adaptado y traducido al castellano para MySQL/MariaDB

DROP TABLE IF EXISTS archivos_plan;
DROP TABLE IF EXISTS habitos;
DROP TABLE IF EXISTS diario;
DROP TABLE IF EXISTS consultas;
DROP TABLE IF EXISTS turnos;
DROP TABLE IF EXISTS recetas;
DROP TABLE IF EXISTS pacientes;
DROP TABLE IF EXISTS nutricionistas;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS roles;

-- --------------------------------------------------------
-- Tabla: roles (lista de roles con su id)
-- --------------------------------------------------------
CREATE TABLE roles (
  id INT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL UNIQUE,
  descripcion VARCHAR(255),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: usuarios (cuentas de acceso)
-- Ahora referencia roles(id) en lugar de usar ENUM 'rol'
-- --------------------------------------------------------
CREATE TABLE usuarios (
  id BIGINT NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role_id INT NOT NULL, -- FK a roles.id
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_usuarios_role_id (role_id),
  CONSTRAINT fk_usuarios_roles FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: nutricionistas (perfil del profesional)
-- Relacionada opcionalmente con una cuenta en usuarios
-- --------------------------------------------------------
CREATE TABLE nutricionistas (
  id BIGINT NOT NULL AUTO_INCREMENT,
  id_usuario BIGINT NULL, -- si el nutricionista también tiene cuenta de usuario
  nombre VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  estado ENUM('activa','pendiente') NOT NULL DEFAULT 'pendiente',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_nutricionistas_id_usuario (id_usuario),
  CONSTRAINT fk_nutricionistas_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: pacientes
-- --------------------------------------------------------
CREATE TABLE pacientes (
  id BIGINT NOT NULL AUTO_INCREMENT,
  id_usuario BIGINT NULL,          -- si el paciente tiene cuenta de usuario
  id_nutricionista BIGINT NULL,    -- nutricionista asignado
  nombre_completo VARCHAR(255) NOT NULL,
  dni VARCHAR(50) UNIQUE,
  fecha_nacimiento DATE,
  email VARCHAR(255),
  telefono VARCHAR(50),
  estado ENUM('activo','alta') NOT NULL DEFAULT 'activo',
  objetivo_principal TEXT,
  progreso_objetivo INT NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_pacientes_id_usuario (id_usuario),
  INDEX idx_pacientes_id_nutricionista (id_nutricionista),
  CONSTRAINT fk_pacientes_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_pacientes_nutricionista FOREIGN KEY (id_nutricionista) REFERENCES nutricionistas(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT chk_progreso_objetivo CHECK (progreso_objetivo >= 0 AND progreso_objetivo <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: recetas (publicadas por nutricionistas)
-- --------------------------------------------------------
CREATE TABLE recetas (
  id BIGINT NOT NULL AUTO_INCREMENT,
  id_nutricionista BIGINT NOT NULL,
  titulo VARCHAR(255) NOT NULL,
  contenido TEXT,
  tags VARCHAR(255),
  publicado TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_recetas_id_nutricionista (id_nutricionista),
  CONSTRAINT fk_recetas_nutricionista FOREIGN KEY (id_nutricionista) REFERENCES nutricionistas(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: turnos (appointments)
-- --------------------------------------------------------
CREATE TABLE turnos (
  id BIGINT NOT NULL AUTO_INCREMENT,
  id_nutricionista BIGINT NOT NULL,
  id_paciente BIGINT NOT NULL,
  fecha_hora DATETIME NOT NULL,
  estado ENUM('pendiente','confirmado','cancelado') NOT NULL DEFAULT 'pendiente',
  senia DECIMAL(10,2) DEFAULT 0.00,
  pagado TINYINT(1) NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_turnos_id_nutricionista (id_nutricionista),
  INDEX idx_turnos_id_paciente (id_paciente),
  CONSTRAINT fk_turnos_nutricionista FOREIGN KEY (id_nutricionista) REFERENCES nutricionistas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_turnos_paciente FOREIGN KEY (id_paciente) REFERENCES pacientes(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: consultas (registros de consulta)
-- --------------------------------------------------------
CREATE TABLE consultas (
  id BIGINT NOT NULL AUTO_INCREMENT,
  id_paciente BIGINT NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  peso_kg DECIMAL(6,2),
  altura_cm DECIMAL(5,1),
  masa_muscular_pct DECIMAL(5,2),
  comentarios TEXT,
  habitos_cumplidos TEXT,
  objetivos_trabajados TEXT,
  PRIMARY KEY (id),
  INDEX idx_consultas_id_paciente (id_paciente),
  CONSTRAINT fk_consultas_paciente FOREIGN KEY (id_paciente) REFERENCES pacientes(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: diario (entradas del paciente)
-- --------------------------------------------------------
CREATE TABLE diario (
  id BIGINT NOT NULL AUTO_INCREMENT,
  id_paciente BIGINT NOT NULL,
  fecha_hora DATETIME NOT NULL,
  tipo_comida ENUM('desayuno','almuerzo','merienda','cena','snack') NOT NULL,
  detalles TEXT,
  url_foto TEXT,
  PRIMARY KEY (id),
  INDEX idx_diario_id_paciente (id_paciente),
  CONSTRAINT fk_diario_paciente FOREIGN KEY (id_paciente) REFERENCES pacientes(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: habitos
-- --------------------------------------------------------
CREATE TABLE habitos (
  id BIGINT NOT NULL AUTO_INCREMENT,
  id_paciente BIGINT NOT NULL,
  nombre VARCHAR(255) NOT NULL,
  color VARCHAR(50),
  visibilidad ENUM('publico','privado') NOT NULL DEFAULT 'publico',
  creado_por ENUM('paciente','nutricionista') NOT NULL,
  racha_dias INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  INDEX idx_habitos_id_paciente (id_paciente),
  CONSTRAINT fk_habitos_paciente FOREIGN KEY (id_paciente) REFERENCES pacientes(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: archivos_plan (archivos de planes subidos)
-- --------------------------------------------------------
CREATE TABLE archivos_plan (
  id BIGINT NOT NULL AUTO_INCREMENT,
  id_paciente BIGINT NOT NULL,
  nombre_archivo VARCHAR(255) NOT NULL,
  url_archivo TEXT NOT NULL,
  fecha_subida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_archivos_plan_id_paciente (id_paciente),
  CONSTRAINT fk_archivos_plan_paciente FOREIGN KEY (id_paciente) REFERENCES pacientes(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
