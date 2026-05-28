-- Ejecutar en libre_mercado_db si la tabla sucursales aún no tiene estas filas
-- Verificar antes con: SELECT * FROM sucursales;

INSERT IGNORE INTO sucursales (id_suc, nombre_suc) VALUES
    (1, 'Sucursal Norte'),
    (2, 'Sucursal Centro'),
    (3, 'Sucursal Sur');

-- También agregar stock inicial para las sucursales nuevas (ajustar id_prod y stock según tus productos)
-- Ejemplo: si tienes productos con id_prod 1, 2, 3 — reemplaza los valores según corresponda
-- INSERT IGNORE INTO sucursales_stock (id_suc, id_prod, stock) VALUES
--     (2, 1, 50), (2, 2, 30), (2, 3, 20),   -- Centro
--     (3, 1, 40), (3, 2, 25), (3, 3, 15);    -- Sur
