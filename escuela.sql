CREATE DATABASE IF NOT EXISTS web_escuela;
USE web_escuela;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(20) NOT NULL UNIQUE
);
INSERT INTO roles (nombre) VALUES ('admin'), ('profe'), ('estudiante');

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    contraseña VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    correo VARCHAR(100) UNIQUE,
    foto_perfil VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

CREATE TABLE cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    profesor_id INT NOT NULL,
    imagen VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (profesor_id) REFERENCES usuarios(id)
);

CREATE TABLE estudiante_curso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    nota DECIMAL(5,2),
    FOREIGN KEY (estudiante_id) REFERENCES usuarios(id),
    FOREIGN KEY (curso_id) REFERENCES cursos(id),
    UNIQUE(estudiante_id, curso_id)
);

CREATE TABLE reset_password_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expiracion DATETIME NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    fecha DATE NOT NULL,
    presente BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (estudiante_id) REFERENCES usuarios(id),
    FOREIGN KEY (curso_id) REFERENCES cursos(id)
);

INSERT INTO usuarios (nombre, apellido, usuario, contraseña, rol_id, correo)
VALUES 
('Admin', 'General', 'admin', SHA2('123a', 256), 1, 'admin@escuela.com'),
('Dario', 'López', 'prof', SHA2('123p', 256), 2, 'prof@escuela.com'),
('Alan', 'Garcia', 'est1', SHA2('1231', 256), 3, 'est1@escuela.com'),
('Rudolf', 'Hess', 'est2', SHA2('1232', 256), 3, 'est2@escuela.com'),
('Shoshiro', 'Honda', 'est3', SHA2('1233', 256), 3, 'est3@escuela.com');

INSERT INTO cursos (nombre, descripcion, profesor_id)
VALUES 
('Matemática Avanzada', 'Curso de álgebra, cálculo y lógica matemática.', 2),
('Programación Web', 'Curso sobre HTML, CSS, JavaScript y frameworks.', 2),
('Base de Datos', 'Curso sobre MySQL, modelado y SQL.', 2);

