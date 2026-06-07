-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 07-06-2026 a las 22:40:20
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `libre_mercado_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `cliente` varchar(255) NOT NULL,
  `rut` varchar(20) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `cliente`, `rut`, `email`, `telefono`, `direccion`) VALUES
(1, 'Juan Pérez', NULL, 'juan@email.com', NULL, NULL),
(2, 'Joselyn Montaño', NULL, 'joselyn.montano@userena.cl', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras`
--

CREATE TABLE `compras` (
  `id_compra` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `id_sucursal` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` varchar(20) DEFAULT 'recibida'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_compras`
--

CREATE TABLE `detalle_compras` (
  `id_detalle` int(11) NOT NULL,
  `id_compra` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas`
--

CREATE TABLE `detalle_ventas` (
  `id_detalle` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_ventas`
--

INSERT INTO `detalle_ventas` (`id_detalle`, `id_venta`, `id_producto`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 1, 2, 850.00, 1700.00),
(2, 2, 1, 1, 850.00, 850.00),
(3, 3, 2, 1, 550000.00, 550000.00),
(4, 6, 2, 1, 550000.00, 550000.00),
(5, 7, 1, 1, 850000.00, 850000.00),
(6, 8, 2, 1, 550000.00, 550000.00),
(7, 9, 2, 1, 550000.00, 550000.00),
(8, 11, 1, 1, 850000.00, 850000.00),
(9, 12, 2, 1, 550000.00, 550000.00),
(10, 12, 1, 1, 850000.00, 850000.00),
(11, 13, 2, 1, 550000.00, 550000.00),
(12, 13, 1, 1, 850000.00, 850000.00),
(13, 14, 2, 1, 550000.00, 550000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL,
  `producto` varchar(150) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `categoria` varchar(100) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_producto`, `producto`, `precio_unitario`, `descripcion`, `activo`, `created_at`, `categoria`, `creado_en`) VALUES
(1, 'Laptop Asus', 850000.00, '', 1, '2026-05-26 00:43:22', NULL, '2026-05-28 17:46:21'),
(2, 'PlayStation 5', 550000.00, NULL, 1, '2026-05-26 01:23:34', NULL, '2026-05-28 17:46:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

CREATE TABLE `proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `proveedor` varchar(255) NOT NULL,
  `rut` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock`
--

CREATE TABLE `stock` (
  `id_stock` int(11) NOT NULL,
  `id_sucursal` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 5,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `stock`
--

INSERT INTO `stock` (`id_stock`, `id_sucursal`, `id_producto`, `cantidad`, `stock_minimo`, `actualizado_en`) VALUES
(1, 1, 1, 1, 5, '2026-05-30 23:47:25'),
(2, 1, 2, 7, 5, '2026-05-31 18:54:36'),
(3, 2, 1, 49, 5, '2026-05-31 18:43:00'),
(4, 2, 2, 29, 5, '2026-05-31 18:43:00'),
(5, 3, 1, 38, 5, '2026-05-31 18:37:06'),
(6, 3, 2, 22, 5, '2026-05-31 18:37:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursales`
--

CREATE TABLE `sucursales` (
  `id_sucursal` int(11) NOT NULL,
  `sucursal` varchar(100) NOT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `ciudad` varchar(100) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sucursales`
--

INSERT INTO `sucursales` (`id_sucursal`, `sucursal`, `activa`, `ciudad`, `direccion`, `telefono`) VALUES
(1, 'Sucursal Norte', 1, NULL, NULL, NULL),
(2, 'Sucursal Centro', 1, NULL, NULL, NULL),
(3, 'Sucursal Sur', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('administrador','cliente','operador') DEFAULT 'cliente',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_sucursal` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `usuario`, `nombre`, `email`, `password_hash`, `rol`, `activo`, `created_at`, `id_sucursal`) VALUES
(1, NULL, 'Juan Pérez', 'juan@email.com', 'hash_simulado', 'cliente', 1, '2026-05-26 00:45:10', NULL),
(2, NULL, 'Joselyn Montaño', 'joselyn.montano@userena.cl', 'hash_simulado_pago', 'cliente', 1, '2026-05-26 01:25:55', NULL),
(3, NULL, 'Emilia N', 'emilia@gmail.com', 'hash_simulado_pago', 'cliente', 1, '2026-05-28 23:56:19', NULL),
(4, NULL, 'Pedro K', 'pedro@gmail.com', 'hash_simulado_pago', 'cliente', 1, '2026-05-30 23:57:09', NULL),
(5, NULL, 'Juan J', 'juan@gmail.com', 'hash_simulado_pago', 'cliente', 1, '2026-05-31 18:22:13', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id_venta` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_sucursal` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `fecha_venta` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) DEFAULT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `estado` varchar(20) DEFAULT 'completada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id_venta`, `id_cliente`, `id_sucursal`, `total`, `fecha_venta`, `id_usuario`, `fecha`, `estado`) VALUES
(1, 1, 1, 1700.00, '2026-05-26 00:48:02', NULL, '2026-05-28 13:46:21', 'completada'),
(2, 1, 1, 850.00, '2026-05-26 01:10:22', NULL, '2026-05-28 13:46:21', 'completada'),
(3, 2, 1, 550000.00, '2026-05-26 01:25:55', NULL, '2026-05-28 13:46:21', 'completada'),
(6, 3, 3, 550000.00, '2026-05-28 23:56:19', NULL, '2026-05-28 19:56:19', 'completada'),
(7, 2, 1, 850000.00, '2026-05-30 23:47:25', NULL, '2026-05-30 19:47:25', 'completada'),
(8, 2, 1, 550000.00, '2026-05-30 23:52:39', NULL, '2026-05-30 19:52:39', 'completada'),
(9, 4, 3, 550000.00, '2026-05-30 23:57:09', NULL, '2026-05-30 19:57:09', 'completada'),
(11, 5, 3, 850000.00, '2026-05-31 18:22:13', NULL, '2026-05-31 14:22:13', 'completada'),
(12, 5, 3, 1400000.00, '2026-05-31 18:37:06', NULL, '2026-05-31 14:37:06', 'completada'),
(13, 5, 2, 1400000.00, '2026-05-31 18:43:00', NULL, '2026-05-31 14:43:00', 'completada'),
(14, 3, 1, 550000.00, '2026-05-31 18:54:36', NULL, '2026-05-31 14:54:36', 'completada');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_productos_sucursal`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_productos_sucursal` (
`id_producto` int(11)
,`producto` varchar(150)
,`precio_unitario` decimal(10,2)
,`descripcion` text
,`stock` int(11)
,`id_sucursal` int(11)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_stock_consolidado`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_stock_consolidado` (
`id_producto` int(11)
,`producto` varchar(150)
,`precio` decimal(10,2)
,`nombre_suc` varchar(100)
,`stock` int(11)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_totales_globales`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_totales_globales` (
`total_ventas` bigint(21)
,`ingresos_globales` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_ultimas_ventas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_ultimas_ventas` (
`id_venta` int(11)
,`cliente` varchar(255)
,`nombre_suc` varchar(100)
,`total` decimal(10,2)
,`fecha` datetime
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_ventas_por_sucursal`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vista_ventas_por_sucursal` (
`nombre_suc` varchar(100)
,`id_sucursal` int(11)
,`total_ventas` bigint(21)
,`ingresos_totales` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_productos_sucursal`
--
DROP TABLE IF EXISTS `vista_productos_sucursal`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_productos_sucursal`  AS SELECT `p`.`id_producto` AS `id_producto`, `p`.`producto` AS `producto`, `p`.`precio_unitario` AS `precio_unitario`, `p`.`descripcion` AS `descripcion`, `st`.`cantidad` AS `stock`, `st`.`id_sucursal` AS `id_sucursal` FROM (`productos` `p` join `stock` `st` on(`p`.`id_producto` = `st`.`id_producto`)) WHERE `p`.`activo` = 1 ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_stock_consolidado`
--
DROP TABLE IF EXISTS `vista_stock_consolidado`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_stock_consolidado`  AS SELECT `p`.`id_producto` AS `id_producto`, `p`.`producto` AS `producto`, `p`.`precio_unitario` AS `precio`, `s`.`sucursal` AS `nombre_suc`, `st`.`cantidad` AS `stock` FROM ((`productos` `p` join `stock` `st` on(`p`.`id_producto` = `st`.`id_producto`)) join `sucursales` `s` on(`st`.`id_sucursal` = `s`.`id_sucursal`)) WHERE `p`.`activo` = 1 ORDER BY `p`.`id_producto` ASC, `s`.`id_sucursal` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_totales_globales`
--
DROP TABLE IF EXISTS `vista_totales_globales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_totales_globales`  AS SELECT count(0) AS `total_ventas`, coalesce(sum(`ventas`.`total`),0) AS `ingresos_globales` FROM `ventas` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_ultimas_ventas`
--
DROP TABLE IF EXISTS `vista_ultimas_ventas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_ultimas_ventas`  AS SELECT `v`.`id_venta` AS `id_venta`, `u`.`nombre` AS `cliente`, `s`.`sucursal` AS `nombre_suc`, `v`.`total` AS `total`, `v`.`fecha` AS `fecha` FROM ((`ventas` `v` join `usuarios` `u` on(`v`.`id_cliente` = `u`.`id_usuario`)) join `sucursales` `s` on(`v`.`id_sucursal` = `s`.`id_sucursal`)) ORDER BY `v`.`id_venta` DESC LIMIT 0, 10 ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_ventas_por_sucursal`
--
DROP TABLE IF EXISTS `vista_ventas_por_sucursal`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_ventas_por_sucursal`  AS SELECT `s`.`sucursal` AS `nombre_suc`, `s`.`id_sucursal` AS `id_sucursal`, count(`v`.`id_venta`) AS `total_ventas`, coalesce(sum(`v`.`total`),0) AS `ingresos_totales` FROM (`sucursales` `s` left join `ventas` `v` on(`s`.`id_sucursal` = `v`.`id_sucursal`)) GROUP BY `s`.`id_sucursal`, `s`.`sucursal` ORDER BY `s`.`id_sucursal` ASC ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `uk_email_cli` (`email`),
  ADD UNIQUE KEY `uk_rut_cli` (`rut`);

--
-- Indices de la tabla `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id_compra`);

--
-- Indices de la tabla `detalle_compras`
--
ALTER TABLE `detalle_compras`
  ADD PRIMARY KEY (`id_detalle`);

--
-- Indices de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_venta` (`id_venta`),
  ADD KEY `id_prod` (`id_producto`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`id_proveedor`),
  ADD UNIQUE KEY `uk_rut_prov` (`rut`);

--
-- Indices de la tabla `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id_stock`),
  ADD KEY `id_prod` (`id_producto`);

--
-- Indices de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  ADD PRIMARY KEY (`id_sucursal`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id_venta`),
  ADD KEY `id_cli` (`id_cliente`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `compras`
--
ALTER TABLE `compras`
  MODIFY `id_compra` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_compras`
--
ALTER TABLE `detalle_compras`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `stock`
--
ALTER TABLE `stock`
  MODIFY `id_stock` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  MODIFY `id_sucursal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD CONSTRAINT `detalle_ventas_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_ventas_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `stock`
--
ALTER TABLE `stock`
  ADD CONSTRAINT `stock_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
