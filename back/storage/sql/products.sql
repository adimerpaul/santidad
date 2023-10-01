-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 18, 2023 at 01:23 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

-- SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
-- START TRANSACTION;
-- SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `santidad`
--

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

-- CREATE TABLE `products` (
--   `id` bigint(20) UNSIGNED NOT NULL,
--   `nombre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
--   `barra` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
--   `cantidad` int(11) NOT NULL DEFAULT 0,
--   `costo` double(10,2) DEFAULT NULL,
--   `precioAntes` double(10,2) DEFAULT NULL,
--   `precio` double(10,2) DEFAULT NULL,
--   `activo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVO',
--   `unidad` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UNIDAD',
--   `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'productDefault.jpg',
--   `descripcion` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
--   `category_id` bigint(20) UNSIGNED DEFAULT NULL,
--   `agencia_id` bigint(20) UNSIGNED DEFAULT NULL,
--   `created_at` timestamp NULL DEFAULT NULL,
--   `updated_at` timestamp NULL DEFAULT NULL
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `nombre`, `barra`, `cantidad`, `costo`, `precioAntes`, `precio`, `activo`, `unidad`, `imagen`, `descripcion`, `category_id`, `agencia_id`, `created_at`, `updated_at`) VALUES
(1, 'Prudential Total Talla M Unisex Para Adulto X 20 Unidades', NULL, 18, 0.00, 138.21, 110.60, 'ACTIVO', 'PAQUETE', '7861078302235_543x543.webp', 'Panales para incontingencias de 20 unidade talla M', 8, 1, '2023-08-18 10:33:48', '2023-08-18 10:40:03'),
(2, 'Prudential Total G Unisex Para Adulto X 20 Unidades', NULL, 1, NULL, 144.61, 115.70, 'ACTIVO', 'PAQUETE', '7861078302242_543x543.webp', 'Panales para la incontingencias de 20 unidades talla G', 8, 1, '2023-08-18 10:36:03', '2023-08-18 10:36:03'),
(3, 'Prudential Confort Talla M Unisex Para Adulto X 20 Unidades', NULL, 31, 0.00, 93.85, 75.10, 'ACTIVO', 'PAQUETE', '7861078303102_80cfb859-d4a3-4d63-a0c7-50745f7ee04c_543x543.webp', 'Protector para adultos super absorbentes.', 8, 1, '2023-08-18 10:36:51', '2023-08-18 10:37:03'),
(4, 'Prudential Confort G Unisex Para Adulto X 20 Unidades', NULL, 27, 0.00, 96.37, 77.10, 'ACTIVO', 'PAQUETE', '7861078303119_650d1f46-1274-4766-9f71-b3a3683b9104_543x543.webp', 'Panales para adultos ideal para manejar perdidas en cantidades moderadas. Doble nucleo de absorcion para mayor sensacion de sequedad. Barreras laterales que evitan fugas. Sistema de rapida absorcion que distribuye el liquido a lo largo del protector. Dos cintas tipo velcro en cada lado para adherirlas cuantas veces sean necesarias. Cubierta exterior tipo tela suave y respirable que protege la piel.', 8, 1, '2023-08-18 10:42:29', '2023-08-18 10:42:29'),
(5, 'Prudential Protector De Cama X 10 Unidades', NULL, 110, 0.00, 56.10, 44.90, 'ACTIVO', 'PAQUETE', '7861078302020_5e4fe270-4cc7-4d55-96fc-2ec5edb39ca0_543x543.webp', 'Protector de cama de 10 unidades', 8, 1, '2023-08-18 10:43:38', '2023-08-18 10:43:38'),
(6, 'Prudential Invisible Talla S Y M Unisex Para Adulto X 18 Unidades', NULL, 2, 0.00, 120.01, 96.00, 'ACTIVO', 'PAQUETE', '7861001847826_543x543.webp', 'Prudential invisible s m x 18 unid unisex p adulto', 8, 1, '2023-08-18 10:44:29', '2023-08-18 10:59:52'),
(7, 'Pompiglos Toallas Humedas Premium X 60 Unidades', NULL, 1, 0.00, 35.20, 21.10, 'ACTIVO', 'PAQUETE', '7770108100010_543x543.webp', 'Pompiglos toallas humedas premium x 60 unidades.', 6, 1, '2023-08-18 10:47:03', '2023-08-18 10:47:03'),
(8, 'Huggies One Done Refreshing Toallitas Humedas X 48 Unidades', NULL, 25, 0.00, 29.50, 20.00, 'ACTIVO', 'PAQUETE', '7702425800700_a34898b6-0419-4af4-ac82-6dcdfea028f7_543x543.webp', 'Las toallitas humedas Huggies naturally refreshing baby tienen un aroma a pepino fresco y te verde con una textura similar a una toallita. Estas toallitas no contienen alcohol y contienen ingredientes suaves como el aloe por lo que puede usar estas toallitas para limpiar mas que solo las nalgas de su bebe.', 6, 1, '2023-08-18 10:47:56', '2023-08-18 10:47:56'),
(9, 'Huggies Active Sec Talla M 5.5 A 9.5Kg X 68 Unidades', NULL, 29, 0.00, 100.00, 85.00, 'ACTIVO', 'PAQUETE', '7751493009843_24b73c26-71d6-46b6-94db-f01f2da10a41_543x543.webp', 'Panales desechables Huggies active sec con tecnologia xtra flex canales que se adaptan a los movimientos cintura y barreras elasticas. Proteccion total y flexibilidad.', 6, 1, '2023-08-18 10:49:47', '2023-08-18 10:49:54'),
(10, 'Huggies Toallitas Humedas Limpieza Cotidiana X 80 Unidades', NULL, 152, 0.00, 13.50, 10.00, 'ACTIVO', 'PAQUETE', '7702425805187_543x543.webp', 'Toallitas humedas Huggies limpieza cotidiana con aroma a manzanilla.', 6, 1, '2023-08-18 10:51:04', '2023-08-18 10:51:04'),
(11, 'Huggies Active Sec Talla Xg 12 A 15Kg X 48 Unidades', NULL, 1, 0.00, 94.50, 70.80, 'ACTIVO', 'PAQUETE', '7751493008402_543x543.webp', 'Panales desechables Huggies active sec con cintura y barreras elasticas. Proteccion total y confort.', 6, 1, '2023-08-18 10:52:13', '2023-08-18 10:52:13'),
(12, 'Huggies Active Sec Pants Talla G De 9 A 12.5Kg X 80 Unidades', NULL, 0, 0.00, 155.00, 116.20, 'ACTIVO', 'CAJA', '7702425805903_c09ab222-8ff4-4164-81fe-b0fc8e04212d_543x543.webp', 'Panales tipo pants con sistema autoajustable mas libertad de moviento. Proteccion total y confort con ajuste perfecto. Talla G para ninos de 9 a 12.5Kg.', 6, 1, '2023-08-18 10:53:59', '2023-08-18 10:53:59'),
(13, 'Collagen Cream Colageno X 112Gr', NULL, 28, 0.00, 190.00, 152.00, 'ACTIVO', 'FRASCO', '261137_543x543.webp', 'Collagen cream x 112gr colageno terbonova', 3, 1, '2023-08-18 10:56:42', '2023-08-18 10:56:42'),
(14, 'Gumys Vitamina C Gomita Masticable X 60 Masticables', NULL, 33, 0.00, 88.00, 72.40, 'ACTIVO', 'FRASCO', '817432010916_543x543.webp', 'Gumys vitamina c x 60 gomita mastic terbonova', 3, 1, '2023-08-18 10:57:59', '2023-08-18 10:57:59'),
(15, 'Live Collagen Femme Colageno Farmacorp X Sobre', NULL, 838, 0.00, NULL, 7.10, 'ACTIVO', 'SOBRE', '7862114221428_543x543.webp', 'Live collagen femme x 15 sobres colageno farmacorp', 3, 1, '2023-08-18 11:00:53', '2023-08-18 11:00:53'),
(16, 'Omega 3 6 9 Frasco X 60 Capsulas', NULL, 61, 0.00, 137.70, 117.00, 'ACTIVO', 'FRASCO', '265119_543x543.webp', 'Omega 3 6 9 fco x 60 cap terbonova', 3, 1, '2023-08-18 11:02:17', '2023-08-18 11:02:25'),
(17, 'Soy Isoflavone Concentrate Suplemento A Base De Soya X 90 Capsulas', NULL, 2, 0.00, 160.00, 136.00, 'ACTIVO', 'FRASCO', '048107019037_543x543.webp', 'Estrogenos de soya x 90 capsulas.', 3, 1, '2023-08-18 11:03:06', '2023-08-18 11:03:18'),
(18, 'Gnc Cla Acido Linoleico 2000Mg X 120 Capsulas', NULL, 19, 0.00, 224.00, 190.40, 'ACTIVO', 'FRASCO', '048107191511_9bd77b7f-0441-428f-a8f9-95b630557410_543x543.webp', 'Gnc Cla Acido Linoleico 2000Mg X 120 Capsulas', 3, 1, '2023-08-18 11:04:08', '2023-08-18 11:04:16'),
(19, 'Antifludes X Capsula', NULL, 551, 0.00, NULL, 3.30, 'ACTIVO', 'CAPSULAS', '10181_543x543.webp', 'Indicaciones Terapeuticas: Antigripal con accion antiviral analgesica antipiretica descongestiva y antihistaminica.\n\nPrincipio Activo: Amantadina 50mg-paracetamol 300mg-clorfenamina maleato 3mg', 3, 1, '2023-08-18 11:06:54', '2023-08-18 11:07:04'),
(20, 'Abrilar Ea 575 35Mg Hedera Helix Jarabe X 100Ml', NULL, 58, 0.00, NULL, 76.00, 'ACTIVO', 'FRASCO', '4104480705137_ade8f1be-89a1-42a9-8926-a1acb089281e_543x543.webp', 'Espectorante Natural Broncodilatador y Antitusivo', 3, 1, '2023-08-18 11:08:07', '2023-08-18 11:08:07'),
(21, 'Refrianex Dia Limon X Sobre', NULL, 1442, 0.00, NULL, 5.80, 'ACTIVO', 'FRASCO', '250942_543x543.webp', 'Resfriado y gripe.\n\nD Isoefedrina sulfato 60mg clorfeniramina maleato 4mg paracetamol 500mg.', 3, 1, '2023-08-18 11:08:55', '2023-08-18 11:08:55'),
(22, 'Elidol Benzocaina Y Mentol Miel Limon X Pastilla', NULL, 3018, 0.00, NULL, 2.34, 'ACTIVO', 'PASTILLA', '190217_543x543.webp', 'Pastillas para la garganta.\n\nBenzocaina 6mg-mentol 10mg- . Analgesico antiseptico bucofaringeo.', 3, 1, '2023-08-18 11:09:42', '2023-08-18 11:10:10'),
(23, 'Vitamin D3 Vitamina D3 2000Ui X 180 Capsulas Blandas', NULL, 167, 0.00, NULL, 80.00, 'ACTIVO', 'FRASCO', '048107123499_543x543.webp', 'Vitamin d3 vitamina d3 2000ui', 3, 1, '2023-08-18 11:10:49', '2023-08-18 11:10:49'),
(24, 'Alergical Sf Clorfeniramina Y Pseudoefedri X Tableta', NULL, 5, 0.00, NULL, 3.22, 'ACTIVO', 'TABLETAS', '7757310005746_543x543.webp', 'Alergical Sf Clorfeniramina Y Pseudoefedri X Tableta', 3, 1, '2023-08-18 11:11:30', '2023-08-18 11:12:11'),
(25, 'Farmax Agua Oxigenada 20 Volumen Cremosa X 90Ml', NULL, 41, 0.00, 6.65, 5.10, 'ACTIVO', 'UNIDAD', '7896902210561_590x590.webp', 'Perfecta para la preparacion de tinturas y la decoloracion de cabellos.', 5, 1, '2023-08-18 11:13:10', '2023-08-18 11:13:10'),
(26, 'Asepxia Jabon Carbon Efecto Purificante X 100Gr', NULL, 101, 0.00, 36.40, 29.10, 'ACTIVO', 'UNIDAD', '650240035401_599x599.webp', 'Jabon asepxia carbon detox es un jabon en barra con efecto purificante. esta especialmente disenado y recomendado para piel mixta con imperfecciones su nueva formula hidroforce presenta una mezcla unica de acido salicilico y glicolico combinado con extractos de ingredientes naturales que entre sus principales acciones destacan el reducir el tamano de los poros eliminar toxinas reducir puntos negros ademas disminuye la grasa y brillo de la cara.', 5, 1, '2023-08-18 11:13:52', '2023-08-18 11:13:52'),
(27, 'Asepxia Jabon Carbon Con Bicarbonato X 100Gr', NULL, 60, 0.00, 36.40, 29.10, 'ACTIVO', 'UNIDAD', '650240036965_c3362286-228f-44c4-b430-0981c8b93f16_599x599.webp', 'Asepxia Jabon Carbon Con Bicarbonato X 100Gr', 5, 1, '2023-08-18 11:14:44', '2023-08-18 11:14:44'),
(28, 'Capilatis Shampoo Puro Rubio Iluminador X 420Ml', NULL, 13, 0.00, 76.80, 61.40, 'ACTIVO', 'FRASCO', '7792640001150_627d7940-3bd0-4a9f-ac73-bbc5fbd10f13_590x590.webp', 'Capilatis Shampoo Puro Rubio Iluminador X 420Ml', 5, 1, '2023-08-18 11:20:38', '2023-08-18 11:20:38'),
(29, 'Capilatis Shampoo Tratante Con Ortiga Normal X 410Ml', NULL, 9, 0.00, 4.15, 43.30, 'ACTIVO', 'FRASCO', '7792640000320_599x599.webp', 'Shampoo tratante Capilatis ortiga fortalece la fibra capilar y contribuye a la oxigenacion del bulbo capilar logrando una profunda limpieza. Los componentes naturales de la Ortiga penetran en la cuticula del cabello hidratandola en profundidad y forman un film protector que la fortalece dandole mas cuerpo y brillo.', 5, 1, '2023-08-18 11:21:36', '2023-08-18 11:21:36'),
(30, 'Cicatricure Contorno Ojos Crema Gel Anti-Edad X 15Gr', NULL, 4, 0.00, 114.30, 91.40, 'ACTIVO', 'UNIDAD', '7798140258230_599x599.webp', 'Cicatricure Contorno Ojos Crema Gel Anti-Edad X 15Gr', 5, 1, '2023-08-18 11:22:19', '2023-08-18 11:22:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
-- ALTER TABLE `products`
--   ADD PRIMARY KEY (`id`),
--   ADD KEY `products_category_id_foreign` (`category_id`),
--   ADD KEY `products_agencia_id_foreign` (`agencia_id`);
--
-- --
-- -- AUTO_INCREMENT for dumped tables
-- --
--
-- --
-- -- AUTO_INCREMENT for table `products`
-- --
-- ALTER TABLE `products`
--   MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
-- ALTER TABLE `products`
--   ADD CONSTRAINT `products_agencia_id_foreign` FOREIGN KEY (`agencia_id`) REFERENCES `agencias` (`id`),
--   ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
-- COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
