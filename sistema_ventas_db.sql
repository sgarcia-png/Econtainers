-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-11-2026 a las 12:07:46
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_ventas_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administradora`
--

CREATE TABLE `administradora` (
  `id` int(11) NOT NULL,
  `rol` varchar(50) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `administradora`
--

INSERT INTO `administradora` (`id`, `rol`, `dni`, `password`) VALUES
(1, 'Administradora', '48165797', '$2y$10$jpFb/fIQ3ZlvHnM86d5kIuO2A9loAsEppyCWIFcWMphpvxD064qMm');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `DNI` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`DNI`, `nombre`, `apellido`, `telefono`, `email`) VALUES
('40111222', 'Juan', 'Perez', '2915000001', 'juan.perez@ejemplo.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenedor`
--

CREATE TABLE `contenedor` (
  `codigo` varchar(30) NOT NULL,
  `deposito` varchar(100) DEFAULT NULL,
  `id_tipo` int(11) NOT NULL,
  `estado` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `contenedor`
--

INSERT INTO `contenedor` (`codigo`, `deposito`, `id_tipo`, `estado`) VALUES
('CONT-001', 'Pringles', 1, 'Vendido'),
('CONT-002', 'Pringles', 1, 'Vendido'),
('CONT-PRUEBA-001', 'Pringles', 1, 'Vendido'),
('CONT-PRUEBA-002', 'Depósito de prueba', 1, 'Vendido'),
('CONT-PRUEBA-004', 'Pringles', 1, 'Disponible'),
('CONT-PRUEBA-005', 'Bahía Blanca', 2, 'Disponible'),
('CONT-PRUEBA-006', 'Pringles', 3, 'Disponible');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentacion`
--

CREATE TABLE `documentacion` (
  `nro_doc` int(11) NOT NULL,
  `tipo_doc` varchar(100) NOT NULL,
  `archivo` varchar(255) NOT NULL,
  `nro_venta` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `documentacion`
--

INSERT INTO `documentacion` (`nro_doc`, `tipo_doc`, `archivo`, `nro_venta`) VALUES
(1, 'Factura', 'factura_prueba.pdf', 1),
(2, 'Factura', '9e9aa7814545d1a9abbe7504e62a9beb.pdf', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago`
--

CREATE TABLE `pago` (
  `nro_pago` int(11) NOT NULL,
  `fecha_pago` date NOT NULL,
  `monto_pago` decimal(12,2) NOT NULL,
  `forma_pago` varchar(50) NOT NULL,
  `nro_venta` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pago`
--

INSERT INTO `pago` (`nro_pago`, `fecha_pago`, `monto_pago`, `forma_pago`, `nro_venta`) VALUES
(1, '2026-10-09', 500000.00, 'Transferencia', 1),
(2, '2026-10-23', 200000.00, 'Efectivo', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `renglon_venta`
--

CREATE TABLE `renglon_venta` (
  `id_renglon` int(11) NOT NULL,
  `precio` decimal(12,2) NOT NULL,
  `codigo_contenedor` varchar(30) NOT NULL,
  `nro_venta` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `renglon_venta`
--

INSERT INTO `renglon_venta` (`id_renglon`, `precio`, `codigo_contenedor`, `nro_venta`) VALUES
(1, 1500000.00, 'CONT-001', 1),
(2, 1500000.00, 'CONT-002', 2),
(3, 1500000.00, 'CONT-PRUEBA-001', 3),
(4, 1500000.00, 'CONT-PRUEBA-002', 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_contenedor`
--

CREATE TABLE `tipo_contenedor` (
  `id_tipo` int(11) NOT NULL,
  `nombre_tipo` varchar(100) NOT NULL,
  `valor` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_contenedor`
--

INSERT INTO `tipo_contenedor` (`id_tipo`, `nombre_tipo`, `valor`) VALUES
(1, 'Contenedor 20 pies', 1500000.00),
(2, 'Contenedor 40 pies', 2500000.00),
(3, '40HC', 3500000.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `nro_venta` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `monto_total` decimal(12,2) NOT NULL,
  `estado_pago` varchar(50) NOT NULL,
  `dni_cliente` varchar(20) NOT NULL,
  `eliminado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`nro_venta`, `fecha`, `monto_total`, `estado_pago`, `dni_cliente`, `eliminado`) VALUES
(1, '2026-10-09', 1500000.00, 'Parcial', '40111222', 0),
(2, '2026-10-19', 1500000.00, 'Pendiente', '40111222', 0),
(3, '2026-10-23', 1500000.00, 'Pendiente', '40111222', 0),
(4, '2026-10-26', 1500000.00, 'Pendiente', '40111222', 0);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administradora`
--
ALTER TABLE `administradora`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`DNI`);

--
-- Indices de la tabla `contenedor`
--
ALTER TABLE `contenedor`
  ADD PRIMARY KEY (`codigo`),
  ADD KEY `fk_contenedor_tipo` (`id_tipo`);

--
-- Indices de la tabla `documentacion`
--
ALTER TABLE `documentacion`
  ADD PRIMARY KEY (`nro_doc`),
  ADD KEY `fk_documentacion_venta` (`nro_venta`);

--
-- Indices de la tabla `pago`
--
ALTER TABLE `pago`
  ADD PRIMARY KEY (`nro_pago`),
  ADD KEY `fk_pago_venta` (`nro_venta`);

--
-- Indices de la tabla `renglon_venta`
--
ALTER TABLE `renglon_venta`
  ADD PRIMARY KEY (`id_renglon`),
  ADD KEY `fk_renglon_contenedor` (`codigo_contenedor`),
  ADD KEY `fk_renglon_venta` (`nro_venta`);

--
-- Indices de la tabla `tipo_contenedor`
--
ALTER TABLE `tipo_contenedor`
  ADD PRIMARY KEY (`id_tipo`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`nro_venta`),
  ADD KEY `fk_ventas_cliente` (`dni_cliente`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administradora`
--
ALTER TABLE `administradora`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `documentacion`
--
ALTER TABLE `documentacion`
  MODIFY `nro_doc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `pago`
--
ALTER TABLE `pago`
  MODIFY `nro_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `renglon_venta`
--
ALTER TABLE `renglon_venta`
  MODIFY `id_renglon` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tipo_contenedor`
--
ALTER TABLE `tipo_contenedor`
  MODIFY `id_tipo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `nro_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `contenedor`
--
ALTER TABLE `contenedor`
  ADD CONSTRAINT `fk_contenedor_tipo` FOREIGN KEY (`id_tipo`) REFERENCES `tipo_contenedor` (`id_tipo`);

--
-- Filtros para la tabla `documentacion`
--
ALTER TABLE `documentacion`
  ADD CONSTRAINT `fk_documentacion_venta` FOREIGN KEY (`nro_venta`) REFERENCES `ventas` (`nro_venta`);

--
-- Filtros para la tabla `pago`
--
ALTER TABLE `pago`
  ADD CONSTRAINT `fk_pago_venta` FOREIGN KEY (`nro_venta`) REFERENCES `ventas` (`nro_venta`);

--
-- Filtros para la tabla `renglon_venta`
--
ALTER TABLE `renglon_venta`
  ADD CONSTRAINT `fk_renglon_contenedor` FOREIGN KEY (`codigo_contenedor`) REFERENCES `contenedor` (`codigo`),
  ADD CONSTRAINT `fk_renglon_venta` FOREIGN KEY (`nro_venta`) REFERENCES `ventas` (`nro_venta`);

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_ventas_cliente` FOREIGN KEY (`dni_cliente`) REFERENCES `cliente` (`DNI`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
