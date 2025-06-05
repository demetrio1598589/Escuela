CREATE DATABASE IF NOT EXISTS web_escuela;
USE web_escuela;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(20) NOT NULL UNIQUE
);
INSERT INTO roles (nombre) VALUES ('Administrador'), ('Profesor'), ('Estudiante');

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    session_id VARCHAR(255) DEFAULT NULL,
    rol_id INT NOT NULL,
    correo VARCHAR(100) UNIQUE,
    foto_perfil VARCHAR(255) DEFAULT NULL,
    foto MEDIUMBLOB DEFAULT NULL,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

CREATE TABLE usuarios_temp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (usuario),
    UNIQUE KEY (correo)
);

CREATE TABLE tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    fecha_token DATETIME DEFAULT CURRENT_TIMESTAMP,
    usado BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
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

CREATE TABLE asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    curso_id INT NOT NULL,
    fecha DATE NOT NULL,
    presente BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (estudiante_id) REFERENCES usuarios(id),
    FOREIGN KEY (curso_id) REFERENCES cursos(id)
);

-- Admin
INSERT INTO usuarios (nombre, apellido, usuario, contrasena, rol_id, correo, foto_perfil)
VALUES 
('Admin', 'General', 'admin', SHA2('123a', 256), 1, 'admin@escuela.com', NULL);

-- Profesores
INSERT INTO usuarios (nombre, apellido, usuario, contrasena, rol_id, correo, foto_perfil)
VALUES
('Linus', 'Torvalds', 'prof1', SHA2('123p', 256), 2, 'linus@escuela.com', 'https://cdn.britannica.com/99/124299-050-4B4D509F/Linus-Torvalds-2012.jpg'),
('Bill', 'Gates', 'prof2', SHA2('123p', 256), 2, 'bill@escuela.com', 'https://npr.brightspotcdn.com/dims3/default/strip/false/crop/4000x2667+0+0/resize/1100/quality/50/format/jpeg/?url=http%3A%2F%2Fnpr-brightspot.s3.amazonaws.com%2F82%2Ffb%2F62f7bcdd47329b5419411e9a7471%2Fbill-gates-portrait-at-npr.jpg'),
('Chema', 'Alonso', 'prof3', SHA2('123p', 256), 2, 'ada@escuela.com', 'https://cdn.forbes.com.mx/2017/04/chema_alonso_telefonica-640x360.jpg');

-- Estudiantes
INSERT INTO usuarios (nombre, apellido, usuario, contrasena, rol_id, correo, foto_perfil)
VALUES
('Harry', 'Potter', 'est1', SHA2('123', 256), 3, 'harry@hogwarts.edu', 'https://i.pinimg.com/736x/21/57/ce/2157cea592ab6d047cc909966e59d9bb.jpg'),
('Hermione', 'Granger', 'est2', SHA2('123', 256), 3, 'hermione@hogwarts.edu', 'https://i.pinimg.com/originals/8b/1a/73/8b1a7396a3ffa50b006a9338508540a7.jpg'),
('Ron', 'Weasley', 'est3', SHA2('123', 256), 3, 'ron@hogwarts.edu', 'https://i.pinimg.com/736x/d7/48/17/d7481701f7bf8a42bb73b53a0b53d775.jpg'),
('Luna', 'Lovegood', 'est4', SHA2('123', 256), 3, 'luna@hogwarts.edu', 'https://contentful.harrypotter.com/usf1vwtuqyxm/t6GVMDanqSKGOKaCWi8oi/74b6816d9f913623419b98048ec87d25/LunaLovegood_WB_F5_LunaLovegoodPromoCloseUp_Promo_080615_Port.jpg?q=75&fm=jpg&w=2560'),
('Cho', 'Chang', 'est5', SHA2('123', 256), 3, 'cho@hogwarts.edu', 'https://los40.com/resizer/v2/52RIR5MDLJI6NKPHPAVVDL2PEQ.jpg?auth=a430fde4ccf1df0b757f5fb5abca3aac93504836ed311b0fb7c6f995473cf75c&quality=70&width=1200');


INSERT INTO cursos (nombre, descripcion, profesor_id)
VALUES 
('Programación Web', 'Curso sobre HTML, CSS, JavaScript y frameworks.', 2),
('Programación en Ensamblador', 'Segmentación de memoria, y llamadas al sistema en arquitecturas como x86 o ARM.', 4),
('Base de Datos', 'Curso sobre MySQL, modelado y SQL.', 2),
('Estructuras de Datos', 'Curso sobre listas, pilas, colas, árboles y grafos.', 2),
('Redes de Computadoras', 'Curso sobre protocolos, modelos OSI y configuración de redes.', 4),
('Sistemas Operativos', 'Curso sobre gestión de procesos, memoria y sistemas de archivos.', 3),
('Desarrollo de Aplicaciones Móviles', 'Curso sobre creación de apps para Android e iOS.', 3);
