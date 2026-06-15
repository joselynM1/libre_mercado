-- Ejecutar en los 3 nodos si la base ya existia antes de agregar
-- la columna "activo" a la tabla proveedores (borrado logico de
-- proveedores). Si vas a recrear los contenedores desde cero
-- (docker compose down -v) este script NO es necesario, porque
-- el esquema nuevo ya la incluye.

ALTER TABLE proveedores ADD COLUMN IF NOT EXISTS activo TINYINT(1) DEFAULT 1;
