CREATE DATABASE IF NOT EXISTS sigel_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE sigel_db;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    intentos_fallidos INT NOT NULL DEFAULT 0,
    eliminado_en DATETIME NULL,

    FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    eliminado_en DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    estado VARCHAR(30) NOT NULL,
    version VARCHAR(50) NULL,
    eliminado_en DATETIME NULL,

    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB;

CREATE TABLE equipo_imagenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,

    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
) ENGINE=InnoDB;

CREATE TABLE prestamos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profesor_id INT NOT NULL,
    operador_id INT NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'solicitado',
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    dias_mora INT NOT NULL DEFAULT 0,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (profesor_id) REFERENCES usuarios(id),
    FOREIGN KEY (operador_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE prestamo_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prestamo_id INT NOT NULL,
    equipo_id INT NOT NULL,
    fecha_devolucion DATETIME NULL,

    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id),
    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
) ENGINE=InnoDB;

CREATE TABLE auth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    selector VARCHAR(50) NOT NULL UNIQUE,
    validator_hash VARCHAR(64) NOT NULL,
    expira_en DATETIME NOT NULL,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE bitacora (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    accion VARCHAR(20) NOT NULL,
    entidad VARCHAR(50) NOT NULL,
    entidad_id INT NULL,
    valores_antes JSON NULL,
    valores_despues JSON NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;