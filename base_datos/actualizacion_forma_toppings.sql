-- =====================================================================
--  Actualización: forma de los toppings para el dibujo del vaso.
--  Úsela si YA tiene la base fresas_db con datos y no quiere perderlos
--  (fresas_db.sql la crea desde cero y borra los pedidos).
--  phpMyAdmin -> base fresas_db -> pestaña "Importar" -> este archivo.
-- =====================================================================
USE fresas_db;
SET NAMES utf8mb4;

ALTER TABLE toppings
  ADD COLUMN forma ENUM('barquillo','galleta','gomita','masmelo','queso','chispas')
      NOT NULL DEFAULT 'chispas' AFTER color;

-- Se le asigna la forma a los toppings que ya existen, según su nombre.
UPDATE toppings SET forma = 'barquillo' WHERE nombre LIKE '%barquillo%';
UPDATE toppings SET forma = 'galleta'   WHERE nombre LIKE '%galleta%' OR nombre LIKE '%oreo%';
UPDATE toppings SET forma = 'gomita'    WHERE nombre LIKE '%gomita%';
UPDATE toppings SET forma = 'masmelo'   WHERE nombre LIKE '%masmel%' OR nombre LIKE '%malvavisco%';
UPDATE toppings SET forma = 'queso'     WHERE nombre LIKE '%queso%';
