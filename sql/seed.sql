USE sigel_db;

INSERT INTO roles (nombre) VALUES
('admin'),
('operador'),
('profesor');


INSERT INTO usuarios
(rol_id, nombre, username, password_hash, activo)
VALUES
(1, 'Administrador SIGEL', 'admin', '$2y$10$4zxDTtT.xc7qT9YQSVa27enM/nKJqdlDGXCxJIroyIrkpxtiAsEfK', 1),

(2, 'Operador SIGEL', 'operador', '$2y$10$34aHBBNFFq8IexUjIy.Mheboywov/b6lgqI6EfKGL6mJN1.LCADYa', 1),

(3, 'Profesor Prueba', 'profesor1', NULL, 1);



INSERT INTO categorias (nombre) VALUES
('Computadoras'),
('Proyectores'),
('Tabletas'),
('Accesorios');


INSERT INTO equipos
(categoria_id, codigo, nombre, estado, version)
VALUES
(1, 'EQ-001', 'Laptop Dell', 'disponible', 'Latitude'),

(1, 'EQ-002', 'Laptop HP', 'disponible', 'ProBook'),

(2, 'EQ-003', 'Proyector Epson', 'disponible', 'PowerLite'),

(4, 'EQ-004', 'Mouse Logitech', 'disponible', 'M185');


