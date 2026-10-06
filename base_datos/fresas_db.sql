-- =====================================================================
--  MWH Fresas con Crema — Base de datos del proyecto final
--  Importar en phpMyAdmin: pestaña "Importar" -> escoger base_datos/fresas_db.sql -> "Continuar".
--  Crea la base "fresas_db" desde cero. OJO: borra las tablas si ya existían.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS fresas_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE fresas_db;
SET NAMES utf8mb4;   -- sin esto las tildes y las ñ se dañan al importar

-- Se borran en orden inverso a como dependen unas de otras.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS detalle_toppings, detalle_pedidos, pedidos, clientes, productos, toppings, salsas, usuarios, configuracion;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. USUARIOS del panel. La clave NUNCA se guarda: se guarda su huella
--    SHA-256 calculada con una "sal" distinta para cada persona.
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(60)  NOT NULL,
  correo     VARCHAR(100) NOT NULL UNIQUE,
  clave_hash CHAR(64)     NOT NULL,
  sal        CHAR(32)     NOT NULL,
  rol        ENUM('admin','vendedor') NOT NULL DEFAULT 'vendedor',
  activo     TINYINT(1)   NOT NULL DEFAULT 1,
  creado     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. PRODUCTOS: los vasos que vende el negocio. El precio depende de
--    cuántos toppings incluye (1 topping $13.000, 2 toppings $17.000).
--    disponible = hoy se puede pedir · activo = sigue en el menú
-- ---------------------------------------------------------------------
CREATE TABLE productos (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  nombre             VARCHAR(60)  NOT NULL UNIQUE,
  descripcion        VARCHAR(200) NOT NULL DEFAULT '',
  toppings_incluidos TINYINT      NOT NULL,
  precio             INT UNSIGNED NOT NULL,
  imagen             VARCHAR(150) NOT NULL DEFAULT '',
  disponible         TINYINT(1)   NOT NULL DEFAULT 1,
  activo             TINYINT(1)   NOT NULL DEFAULT 1,
  CHECK (toppings_incluidos BETWEEN 1 AND 4),
  CHECK (precio > 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. TOPPINGS y 4. SALSAS. El color es el que usa el dibujo del vaso en
--    la página "Personaliza": si se cambia aquí, el vaso cambia solo.
--    La FORMA dice cómo se dibuja cada topping (barquillo, galleta...).
-- ---------------------------------------------------------------------
CREATE TABLE toppings (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(40) NOT NULL UNIQUE,
  color      CHAR(7)     NOT NULL DEFAULT '#E54895',
  forma      ENUM('barquillo','galleta','gomita','masmelo','queso','chispas') NOT NULL DEFAULT 'chispas',
  disponible TINYINT(1)  NOT NULL DEFAULT 1,
  activo     TINYINT(1)  NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE salsas (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(40) NOT NULL UNIQUE,
  color      CHAR(7)     NOT NULL DEFAULT '#E54895',
  disponible TINYINT(1)  NOT NULL DEFAULT 1,
  activo     TINYINT(1)  NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. CLIENTES: un registro por número de celular. El cliente NO crea
--    cuenta: se guarda solo la primera vez que pide. Sirve para saber,
--    al ver un pedido, si es alguien que ya compró (verificado), alguien
--    nuevo (primera vez) o un número que pide y no paga (bloqueado).
--    estado = verificado se pone solo cuando un pedido suyo se entrega.
-- ---------------------------------------------------------------------
CREATE TABLE clientes (
  telefono      VARCHAR(15)  PRIMARY KEY,
  nombre        VARCHAR(60)  NOT NULL,
  estado        ENUM('nuevo','verificado','bloqueado') NOT NULL DEFAULT 'nuevo',
  notas         VARCHAR(200) NULL,
  primer_pedido DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado   DATETIME     NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. PEDIDOS: el encabezado (quién pide, cómo lo recibe, cuánto pagó).
--    telefono es llave foránea hacia clientes: todo pedido tiene cliente.
-- ---------------------------------------------------------------------
CREATE TABLE pedidos (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  cliente   VARCHAR(60)  NOT NULL,
  telefono  VARCHAR(15)  NOT NULL,
  entrega   ENUM('recoger','domicilio') NOT NULL DEFAULT 'recoger',
  direccion VARCHAR(150) NULL,
  notas     VARCHAR(200) NULL,
  total     INT UNSIGNED NOT NULL,
  estado    ENUM('pendiente','preparando','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  fecha     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (telefono) REFERENCES clientes(telefono)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. DETALLE_PEDIDOS: un renglón por cada vaso del pedido.
--    El precio se COPIA aquí: si mañana sube, los pedidos viejos no cambian.
-- ---------------------------------------------------------------------
CREATE TABLE detalle_pedidos (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id   INT NOT NULL,
  producto_id INT NOT NULL,
  salsa_id    INT NOT NULL,
  precio      INT UNSIGNED NOT NULL,
  FOREIGN KEY (pedido_id)   REFERENCES pedidos(id),
  FOREIGN KEY (producto_id) REFERENCES productos(id),
  FOREIGN KEY (salsa_id)    REFERENCES salsas(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. DETALLE_TOPPINGS: tabla intermedia. Un vaso lleva varios toppings y
--    un topping está en muchos vasos (relación muchos a muchos).
-- ---------------------------------------------------------------------
CREATE TABLE detalle_toppings (
  detalle_id INT NOT NULL,
  topping_id INT NOT NULL,
  PRIMARY KEY (detalle_id, topping_id),
  FOREIGN KEY (detalle_id) REFERENCES detalle_pedidos(id),
  FOREIGN KEY (topping_id) REFERENCES toppings(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. CONFIGURACION: datos del negocio que se editan desde el panel,
--    sin tocar el código (nombre, WhatsApp, horario...).
-- ---------------------------------------------------------------------
CREATE TABLE configuracion (
  clave       VARCHAR(30)  PRIMARY KEY,
  valor       VARCHAR(255) NOT NULL,
  descripcion VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- =====================================================================
--  DATOS
-- =====================================================================

-- Claves de prueba: admin123 y vender123. CAMBIARLAS antes de sustentar
-- (en el panel: "Cambiar mi clave"). SHA2() calcula la misma huella que PHP.
INSERT INTO usuarios (nombre, correo, clave_hash, sal, rol) VALUES
('Administrador', 'admin@mwhfresas.com',    SHA2(CONCAT('9f2c1a7e4b8d3f60a1c5e7b9d2f4a6c8', 'admin123'), 256), '9f2c1a7e4b8d3f60a1c5e7b9d2f4a6c8', 'admin'),
('Vendedor',      'vendedor@mwhfresas.com', SHA2(CONCAT('3e7a9c1b5d2f8e4a6c0b7d9f1a3e5c7b', 'vender123'), 256), '3e7a9c1b5d2f8e4a6c0b7d9f1a3e5c7b', 'vendedor');

INSERT INTO productos (id, nombre, descripcion, toppings_incluidos, precio, imagen) VALUES
(1, 'Fresas con 1 topping',  'Fresas frescas, crema de la casa, un topping y la salsa que más te guste.', 1, 13000, 'assets/img/img2.png'),
(2, 'Fresas con 2 toppings', 'Fresas frescas, crema de la casa, dos toppings y la salsa que más te guste.', 2, 17000, 'assets/img/img5.png');

INSERT INTO toppings (id, nombre, color, forma) VALUES
(1, 'Barquillos', '#D9A55B', 'barquillo'),
(2, 'Gomitas',    '#FF5FA2', 'gomita'),
(3, 'Galletas',   '#7A4A2A', 'galleta'),
(4, 'Masmellos',  '#FFC9E0', 'masmelo'),
(5, 'Queso',      '#FFD95A', 'queso');

INSERT INTO salsas (id, nombre, color) VALUES
(1, 'Chocolate', '#5A2D0C'),
(2, 'Arequipe',  '#C27C2C'),
(3, 'Fresa',     '#E0245E'),
(4, 'Mora',      '#6E1E4F');

INSERT INTO configuracion (clave, valor, descripcion) VALUES
('nombre',      'MWH Fresas',                             'Nombre del negocio'),
('lema',        'Endulza tus momentos con el mejor sabor', 'Frase de la portada'),
('whatsapp',    '573238263347',                            'WhatsApp con 57 y sin espacios'),
('ciudad',      'Medellín, Colombia',                      'Ciudad que sale en el pie de página'),
('horario',     'Todos los días de 2:00 p. m. a 8:00 p. m.', 'Horario de atención'),
('integrantes', 'Nombres de los integrantes del grupo',    'Quiénes hicieron la página (pie de página)'),
('direccion',   'Medellín, Antioquia, Colombia',           'Dirección del negocio (la que sale en el mapa)');

-- Pedidos de ejemplo de los últimos 7 días, para que el panel y los
-- reportes se vean con datos. Las fechas son relativas al día en que se importa.
-- Mientras se cargan los ejemplos se apaga la revisión de llaves foráneas,
-- porque los clientes se crean DESPUÉS, a partir de los pedidos.
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO pedidos (id, cliente, telefono, entrega, direccion, notas, total, estado, fecha) VALUES
(1, 'Valentina Ríos', '3104567821', 'domicilio', 'Calle 38 # 12-61', NULL, 17000, 'entregado', CONCAT(CURDATE() - INTERVAL 6 DAY, ' 15:10:00')),
(2, 'Samuel Ortiz', '3002148890', 'recoger', NULL, NULL, 47000, 'entregado', CONCAT(CURDATE() - INTERVAL 6 DAY, ' 17:40:00')),
(3, 'Mariana López', '3217789012', 'recoger', NULL, NULL, 13000, 'entregado', CONCAT(CURDATE() - INTERVAL 5 DAY, ' 16:05:00')),
(4, 'Juan Pablo Gil', '3156670043', 'domicilio', 'Calle 41 # 80-48', NULL, 26000, 'cancelado', CONCAT(CURDATE() - INTERVAL 4 DAY, ' 14:30:00')),
(5, 'Sara Montoya', '3014452278', 'domicilio', 'Calle 66 # 65-67', NULL, 17000, 'entregado', CONCAT(CURDATE() - INTERVAL 4 DAY, ' 18:20:00')),
(6, 'Daniel Restrepo', '3128890011', 'recoger', NULL, NULL, 13000, 'entregado', CONCAT(CURDATE() - INTERVAL 3 DAY, ' 15:55:00')),
(7, 'Luisa Cardona', '3187745120', 'recoger', NULL, NULL, 13000, 'entregado', CONCAT(CURDATE() - INTERVAL 2 DAY, ' 16:45:00')),
(8, 'Tomás Zapata', '3045561209', 'recoger', NULL, NULL, 30000, 'entregado', CONCAT(CURDATE() - INTERVAL 2 DAY, ' 19:05:00')),
(9, 'Isabella Henao', '3229014476', 'recoger', NULL, NULL, 34000, 'entregado', CONCAT(CURDATE() - INTERVAL 1 DAY, ' 15:30:00')),
(10, 'Mateo Arango', '3136678854', 'recoger', NULL, NULL, 17000, 'entregado', CONCAT(CURDATE() - INTERVAL 1 DAY, ' 17:15:00')),
(11, 'Camila Vélez', '3009981223', 'domicilio', 'Calle 66 # 51-29', NULL, 30000, 'preparando', NOW() - INTERVAL 40 MINUTE),
(12, 'Andrés Muñoz', '3162204455', 'domicilio', 'Calle 78 # 30-52', NULL, 26000, 'pendiente', NOW() - INTERVAL 10 MINUTE),
(13, 'Juan Pablo Gil', '3156670043', 'domicilio', 'Calle 41 # 80-48', NULL, 13000, 'cancelado', CONCAT(CURDATE() - INTERVAL 2 DAY, ' 18:40:00')),
(14, 'Pedro Pérez', '3000000001', 'domicilio', 'Calle 1 # 1-1', 'Nadie contestó en la dirección', 17000, 'cancelado', CONCAT(CURDATE() - INTERVAL 3 DAY, ' 20:10:00'));

INSERT INTO detalle_pedidos (id, pedido_id, producto_id, salsa_id, precio) VALUES
(1, 1, 2, 1, 17000),
(2, 2, 2, 1, 17000),
(3, 2, 2, 2, 17000),
(4, 2, 1, 1, 13000),
(5, 3, 1, 3, 13000),
(6, 4, 1, 3, 13000),
(7, 4, 1, 2, 13000),
(8, 5, 2, 3, 17000),
(9, 6, 1, 3, 13000),
(10, 7, 1, 1, 13000),
(11, 8, 2, 3, 17000),
(12, 8, 1, 4, 13000),
(13, 9, 2, 2, 17000),
(14, 9, 2, 2, 17000),
(15, 10, 2, 2, 17000),
(16, 11, 2, 1, 17000),
(17, 11, 1, 3, 13000),
(18, 12, 1, 3, 13000),
(19, 12, 1, 4, 13000),
(20, 13, 1, 2, 13000),
(21, 14, 2, 1, 17000);

INSERT INTO detalle_toppings (detalle_id, topping_id) VALUES
(1, 4),
(1, 5),
(2, 2),
(2, 3),
(3, 1),
(3, 3),
(4, 3),
(5, 3),
(6, 4),
(7, 2),
(8, 5),
(8, 2),
(9, 3),
(10, 4),
(11, 2),
(11, 5),
(12, 2),
(13, 3),
(13, 4),
(14, 3),
(14, 1),
(15, 1),
(15, 3),
(16, 1),
(16, 3),
(17, 3),
(18, 5),
(19, 3),
(20, 1),
(21, 2),
(21, 4);

-- Clientes de ejemplo, sacados de los pedidos: un registro por celular.
INSERT INTO clientes (telefono, nombre, primer_pedido)
SELECT telefono, MIN(cliente), MIN(fecha) FROM pedidos GROUP BY telefono;

-- Quien ya recibió (y pagó) un pedido queda verificado.
UPDATE clientes SET estado = 'verificado'
 WHERE telefono IN (SELECT telefono FROM pedidos WHERE estado = 'entregado');

-- Un número que el negocio bloqueó: pidió, no contestó y no pagó.
UPDATE clientes SET estado = 'bloqueado', notas = 'Pidió a domicilio y nunca contestó. No aceptar más pedidos.'
 WHERE telefono = '3000000001';

SET FOREIGN_KEY_CHECKS = 1;
