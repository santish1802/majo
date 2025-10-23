-- Exportación de datos del 2025-10-22
-- Generado: 2025-10-23 04:31:48
-- Base de datos: majocafe_system

SET FOREIGN_KEY_CHECKS=0;

-- Tabla: pedidos
INSERT INTO `pedidos` (`id`, `fecha_pedido`, `total`, `descuento`, `notas`, `estado`, `ubicacion`, `metodo_pago`) VALUES
(491, '2025-10-22 16:20:51', 24.00, 0.00, NULL, 'completado', 'Mesa 3', 'Pendiente'),
(492, '2025-10-22 16:21:04', 2.00, 0.00, NULL, 'completado', 'Libre', 'Pendiente'),
(493, '2025-10-22 17:11:43', 31.00, 0.00, NULL, 'completado', 'Mesa 3', 'Pendiente'),
(494, '2025-10-22 17:12:17', 22.00, 0.00, NULL, 'completado', 'Mesa 4', 'Pendiente'),
(495, '2025-10-22 17:19:12', 28.00, 0.00, NULL, 'completado', 'Mesa 2', 'Pendiente'),
(496, '2025-10-22 17:40:36', 14.00, 0.00, NULL, 'completado', 'Mesa 1', 'Pendiente'),
(497, '2025-10-22 18:02:01', 16.00, 0.00, NULL, 'completado', 'Libre', 'Pendiente'),
(498, '2025-10-22 18:10:56', 41.00, 0.00, NULL, 'completado', 'Mesa 4', 'Pendiente'),
(499, '2025-10-22 18:17:21', 14.00, 0.00, 'Pago fue al yape Yuli 😉', 'completado', 'Libre', 'Pendiente'),
(500, '2025-10-22 19:28:04', 27.00, 0.00, NULL, 'completado', 'Mesa 4', 'Pendiente'),
(501, '2025-10-22 19:50:09', 2.00, 0.00, NULL, 'completado', 'Libre', 'Pendiente'),
(502, '2025-10-22 19:57:42', 83.00, 0.00, NULL, 'completado', 'Mesa 2', 'Pendiente'),
(503, '2025-10-22 20:18:38', 6.00, 0.00, NULL, 'completado', 'Libre', 'Pendiente'),
(504, '2025-10-22 20:30:12', 24.00, 0.00, NULL, 'pendiente', 'Mesa 3', 'Pendiente');

-- Tabla: pedido_detalle
INSERT INTO `pedido_detalle` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `precio_modificado`, `cantidad_modificada`, `modificacion_tipo`, `modificacion_valor`, `notas_item`) VALUES
(1932, 491, 23, 1, 10.00, NULL, 0, NULL, 0.00, NULL),
(1933, 491, 36, 1, 14.00, NULL, 0, NULL, 0.00, NULL),
(1934, 492, 55, 1, 2.00, NULL, 0, NULL, 0.00, NULL),
(1943, 493, 44, 1, 12.00, NULL, 0, NULL, 0.00, NULL),
(1944, 493, 2, 2, 7.00, 4.00, 1, 'soles', -3.00, 'promoción de torta + café por S/. 4'),
(1945, 493, 42, 1, 8.00, NULL, 0, NULL, 0.00, NULL),
(1938, 494, 48, 1, 10.00, NULL, 0, NULL, 0.00, NULL),
(1939, 494, 24, 1, 12.00, NULL, 0, NULL, 0.00, NULL),
(1946, 495, 5, 1, 10.00, NULL, 0, NULL, 0.00, NULL),
(1947, 495, 30, 1, 12.00, NULL, 0, NULL, 0.00, NULL),
(1948, 495, 52, 1, 6.00, NULL, 0, NULL, 0.00, NULL),
(1949, 496, 43, 1, 14.00, NULL, 0, NULL, 0.00, NULL),
(1950, 497, 2, 1, 7.00, 4.00, 1, 'soles', -3.00, 'promoción de torta + café por 4'),
(1951, 497, 44, 1, 12.00, NULL, 0, NULL, 0.00, NULL),
(1956, 498, 2, 1, 7.00, NULL, 0, NULL, 0.00, NULL),
(1957, 498, 5, 1, 10.00, NULL, 0, NULL, 0.00, NULL),
(1958, 498, 19, 1, 12.00, NULL, 0, NULL, 0.00, NULL),
(1959, 498, 56, 1, 12.00, NULL, 0, NULL, 0.00, NULL),
(1955, 499, 43, 1, 14.00, NULL, 0, NULL, 0.00, NULL),
(1960, 500, 2, 2, 7.00, NULL, 0, NULL, 0.00, NULL),
(1961, 500, 41, 1, 7.00, NULL, 0, NULL, 0.00, NULL),
(1962, 500, 50, 1, 6.00, NULL, 0, NULL, 0.00, NULL),
(1963, 501, 55, 1, 2.00, NULL, 0, NULL, 0.00, NULL),
(1981, 502, 28, 1, 14.00, NULL, 0, NULL, 0.00, NULL),
(1982, 502, 48, 1, 10.00, NULL, 0, NULL, 0.00, NULL),
(1983, 502, 22, 1, 10.00, NULL, 0, NULL, 0.00, NULL),
(1984, 502, 3, 1, 9.00, NULL, 0, NULL, 0.00, 'Leche Deslactozada'),
(1985, 502, 46, 1, 14.00, NULL, 0, NULL, 0.00, NULL),
(1986, 502, 41, 1, 7.00, NULL, 0, NULL, 0.00, NULL),
(1987, 502, 57, 1, 5.00, NULL, 0, NULL, 0.00, NULL),
(1988, 502, 43, 1, 14.00, NULL, 0, NULL, 0.00, NULL),
(1970, 503, 52, 1, 6.00, NULL, 0, NULL, 0.00, NULL),
(1978, 504, 3, 1, 9.00, NULL, 0, NULL, 0.00, NULL),
(1979, 504, 2, 1, 7.00, NULL, 0, NULL, 0.00, NULL),
(1980, 504, 42, 1, 8.00, NULL, 0, NULL, 0.00, NULL);

-- Tabla: pedido_pagos
INSERT INTO `pedido_pagos` (`id`, `pedido_id`, `metodo_pago`, `monto`, `fecha_pago`) VALUES
(586, 492, 'Efectivo', 2.00, '2025-10-22 16:21:33'),
(587, 491, 'Efectivo', 24.00, '2025-10-22 16:21:41'),
(588, 493, 'Tarjeta', 31.00, '2025-10-22 17:22:15'),
(589, 494, 'QR', 22.00, '2025-10-22 17:43:41'),
(590, 495, 'QR', 28.00, '2025-10-22 17:53:50'),
(591, 496, 'Efectivo', 14.00, '2025-10-22 17:54:06'),
(592, 497, 'QR', 16.00, '2025-10-22 18:17:50'),
(593, 499, 'QR', 14.00, '2025-10-22 18:19:02'),
(594, 501, 'Efectivo', 2.00, '2025-10-22 19:50:26'),
(595, 500, 'Efectivo', 27.00, '2025-10-22 19:53:00'),
(596, 498, 'Tarjeta', 41.00, '2025-10-22 20:11:37'),
(597, 503, 'QR', 6.00, '2025-10-22 20:18:57'),
(598, 502, 'Efectivo', 83.00, '2025-10-22 20:51:54');

SET FOREIGN_KEY_CHECKS=1;
