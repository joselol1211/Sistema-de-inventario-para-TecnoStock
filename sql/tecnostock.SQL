DROP DATABASE IF EXISTS tecnostock;
CREATE DATABASE tecnostock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tecnostock;

CREATE TABLE Usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    clave VARCHAR(255) NOT NULL,
    estado VARCHAR(20) DEFAULT 'Activo'
);


CREATE TABLE Categoria (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado VARCHAR(20) DEFAULT 'Activo'
);

CREATE TABLE Producto (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10, 2) NOT NULL CHECK (precio >= 0),
    stock_actual INT NOT NULL DEFAULT 0 CHECK (stock_actual >= 0),
    stock_minimo INT NOT NULL DEFAULT 0 CHECK (stock_minimo >= 0),
    estado VARCHAR(20) DEFAULT 'Activo',
    id_categoria INT,
    FOREIGN KEY (id_categoria) REFERENCES Categoria(id_categoria)
);


CREATE TABLE Movimiento (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tipo VARCHAR(20) NOT NULL, 
    cantidad INT NOT NULL CHECK (cantidad > 0),
    observacion TEXT,
    id_producto INT NOT NULL,
    id_usuario INT NOT NULL,
    FOREIGN KEY (id_producto) REFERENCES Producto(id_producto),
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
);


INSERT INTO Usuario (nombre, correo, clave, estado) VALUES 
('Admin Principal', 'admin@tecnostock.cl', '123456', 'Activo'),
('Bodeguero Central', 'bodega@tecnostock.cl', '123456', 'Activo'),
('Usuario Antiguo', 'inactivo@tecnostock.cl', '123456', 'Inactivo');


INSERT INTO Categoria (nombre, descripcion) VALUES 
('Audífonos', 'Audífonos inalámbricos y con cable'),
('Cargadores', 'Cargadores de pared, auto y portátiles'),
('Cables', 'Cables de carga, video y adaptadores');


INSERT INTO Producto (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria) VALUES 
('AUD-001', 'Audífonos Bluetooth Pro', 'Color negro, cancelación de ruido', 45000, 25, 10, 'Activo', 1), 
('CAR-001', 'Cargador Rápido 20W', 'Cargador USB-C blanco', 12000, 4, 15, 'Activo', 2), 
('CAB-001', 'Cable HDMI 2 metros', 'Cable trenzado 4K', 5000, 50, 20, 'Activo', 3), 
('CAB-002', 'Cable Tipo C a Tipo C', '1 metro', 4500, 0, 10, 'Activo', 3), 
('AUD-002', 'Audífonos Básicos', 'Conector 3.5mm', 3000, 5, 5, 'Inactivo', 1); 


INSERT INTO Movimiento (tipo, cantidad, observacion, id_producto, id_usuario) VALUES 
('Entrada', 25, 'Inventario inicial de apertura', 1, 1), 
('Entrada', 10, 'Recepción de compra al proveedor', 2, 2), 
('Salida', 6, 'Venta a cliente corporativo', 2, 1),
('Entrada', 50, 'Llegada de contenedor', 3, 2),
('Entrada', 5, 'Stock recuperado de otra tienda', 5, 1);


