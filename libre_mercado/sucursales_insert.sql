CREATE TABLE IF NOT EXISTS sucursales (
    id_suc     INT          AUTO_INCREMENT PRIMARY KEY,
    nombre_suc VARCHAR(100) NOT NULL,
    activa     TINYINT(1)   DEFAULT 1
);

INSERT INTO sucursales (id_suc, nombre_suc) VALUES
    (1, 'Sucursal Norte'),
    (2, 'Sucursal Centro'),
    (3, 'Sucursal Sur');