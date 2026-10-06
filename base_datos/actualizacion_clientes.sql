-- =====================================================================
--  Actualización: control de clientes por teléfono + dirección del mapa.
--  Úsela si YA tiene la base fresas_db con datos y no quiere perderlos
--  (fresas_db.sql la crea desde cero y borra los pedidos).
--  phpMyAdmin -> base fresas_db -> pestaña "Importar" -> este archivo.
--  Importarla una sola vez.
-- =====================================================================
USE fresas_db;
SET NAMES utf8mb4;

-- 1. La tabla de clientes: un registro por número de celular.
CREATE TABLE IF NOT EXISTS clientes (
  telefono      VARCHAR(15)  PRIMARY KEY,
  nombre        VARCHAR(60)  NOT NULL,
  estado        ENUM('nuevo','verificado','bloqueado') NOT NULL DEFAULT 'nuevo',
  notas         VARCHAR(200) NULL,
  primer_pedido DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado   DATETIME     NULL
) ENGINE=InnoDB;

-- 2. Se llena con los celulares de los pedidos que ya existen.
INSERT IGNORE INTO clientes (telefono, nombre, primer_pedido)
SELECT telefono, MIN(cliente), MIN(fecha) FROM pedidos GROUP BY telefono;

-- 3. Quien ya recibió un pedido queda verificado.
UPDATE clientes SET estado = 'verificado'
 WHERE estado = 'nuevo'
   AND telefono IN (SELECT telefono FROM pedidos WHERE estado = 'entregado');

-- 4. Desde ahora todo pedido debe tener su cliente (llave foránea).
ALTER TABLE pedidos
  ADD CONSTRAINT fk_pedidos_cliente FOREIGN KEY (telefono) REFERENCES clientes(telefono);

-- 5. La dirección que sale en el mapa del inicio (se cambia en el panel).
INSERT IGNORE INTO configuracion (clave, valor, descripcion) VALUES
('direccion', 'Medellín, Antioquia, Colombia', 'Dirección del negocio (la que sale en el mapa)');
