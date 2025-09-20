-- =============================================
-- SCRIPT COMPLETO DE BASE DE DATOS - INDUMAQHER
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = "-05:00";

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS `indumaqher_portfolio`
DEFAULT CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `indumaqher_portfolio`;

-- =============================================
-- TABLA: users - USUARIOS ADMINISTRADORES
-- =============================================

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','super_admin') DEFAULT 'admin',
  `first_name` varchar(100) DEFAULT '',
  `last_name` varchar(100) DEFAULT '',
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  INDEX `idx_users_active` (`is_active`),
  INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLA: categories - CATEGORÍAS DE MÁQUINAS
-- =============================================

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#1a365d',
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `machines_count` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  INDEX `idx_categories_active` (`is_active`),
  FULLTEXT KEY `idx_categories_search` (`name`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLA: technologies - TECNOLOGÍAS
-- =============================================

CREATE TABLE `technologies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('control','mechanical','electrical','software','safety','other') DEFAULT 'other',
  `is_active` tinyint(1) DEFAULT 1,
  `machines_count` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  INDEX `idx_technologies_active` (`is_active`),
  FULLTEXT KEY `idx_technologies_search` (`name`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLA: machines - MÁQUINAS PRINCIPALES
-- =============================================

CREATE TABLE `machines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `model` varchar(100) DEFAULT '',
  `slug` varchar(250) NOT NULL,
  `description` text NOT NULL,
  `short_description` varchar(500) DEFAULT '',
  `category_id` int(11) DEFAULT NULL,
  `main_image` text DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `inquiries_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  INDEX `idx_machines_status` (`status`),
  INDEX `idx_machines_featured` (`featured`),
  INDEX `idx_machines_category_status` (`category_id`, `status`),
  FULLTEXT KEY `idx_machines_search` (`name`, `description`),
  CONSTRAINT `fk_machines_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_machines_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLA: machine_specifications - ESPECIFICACIONES
-- =============================================

CREATE TABLE `machine_specifications` (
  `machine_id` int(11) NOT NULL,
  `capacity` varchar(100) DEFAULT NULL,
  `speed` varchar(100) DEFAULT NULL,
  `power` varchar(100) DEFAULT NULL,
  `width` decimal(8,2) DEFAULT NULL,
  `height` decimal(8,2) DEFAULT NULL,
  `depth` decimal(8,2) DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `materials` json DEFAULT NULL,
  `certifications` json DEFAULT NULL,
  `additional_specs` json DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`machine_id`),
  CONSTRAINT `fk_specs_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLA: machine_pricing - PRECIOS
-- =============================================

CREATE TABLE `machine_pricing` (
  `machine_id` int(11) NOT NULL,
  `base_price` decimal(12,2) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `price_range` enum('economico','medio','premium') DEFAULT NULL,
  `is_quote_only` tinyint(1) DEFAULT 1,
  `price_notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`machine_id`),
  CONSTRAINT `fk_pricing_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TABLA: inquiries - CONSULTAS DE CLIENTES
-- =============================================

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `machine_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_company` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','contacted','quoted','closed','cancelled') DEFAULT 'new',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `assigned_to` int(11) DEFAULT NULL,
  `source` varchar(50) DEFAULT 'website',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `machine_id` (`machine_id`),
  KEY `assigned_to` (`assigned_to`),
  INDEX `idx_inquiries_status` (`status`),
  FULLTEXT KEY `idx_inquiries_search` (`customer_name`, `customer_company`, `message`),
  CONSTRAINT `fk_inquiries_machine` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inquiries_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DATOS INICIALES
-- =============================================

-- Usuario administrador inicial (password: admin123)
INSERT INTO `users` (`username`, `email`, `password`, `role`, `first_name`, `last_name`, `is_active`) VALUES
('admin', 'admin@indumaqher.com', '$2b$12$LQv3c1yqBwlJnyOhEeOKxOcQVhM5CaGnGllxQFDhLODMKjTU2iUmy', 'super_admin', 'Administrador', 'Sistema', 1);

-- Categorías iniciales
INSERT INTO `categories` (`name`, `slug`, `description`, `color`, `icon`, `sort_order`, `is_active`) VALUES
('Empacadoras', 'empacadoras', 'Máquinas empacadoras verticales y horizontales para diversos productos industriales', '#1a365d', 'fas fa-box', 1, 1),
('Selladoras', 'selladoras', 'Equipos de sellado térmico y soldadura de empaques flexibles', '#2c5282', 'fas fa-compress-alt', 2, 1),
('Transportadores', 'transportadores', 'Sistemas de transporte y manejo automatizado de materiales', '#38a169', 'fas fa-conveyor-belt', 3, 1),
('Control Industrial', 'control-industrial', 'Sistemas de control, automatización y monitoreo industrial', '#e53e3e', 'fas fa-cogs', 4, 1);

-- Tecnologías iniciales
INSERT INTO `technologies` (`name`, `slug`, `description`, `category`, `is_active`) VALUES
('PLC Siemens', 'plc-siemens', 'Controladores lógicos programables Siemens para automatización industrial', 'control', 1),
('HMI Táctil', 'hmi-tactil', 'Interfaces humano-máquina con pantallas táctiles para control intuitivo', 'control', 1),
('Variadores de Frecuencia', 'variadores-frecuencia', 'Dispositivos para control preciso de velocidad de motores eléctricos', 'electrical', 1),
('Sensores Fotoeléctricos', 'sensores-fotoelectricos', 'Sensores ópticos para detección precisa de productos', 'control', 1),
('Motores Trifásicos', 'motores-trifasicos', 'Motores eléctricos trifásicos de alta eficiencia energética', 'electrical', 1),
('Soldadura Ultrasónica', 'soldadura-ultrasonica', 'Tecnología de sellado por ultrasonido para empaques', 'mechanical', 1);

-- Máquinas de ejemplo SIN IMÁGENES
INSERT INTO `machines` (`name`, `model`, `slug`, `description`, `short_description`, `category_id`, `main_image`, `status`, `featured`, `sort_order`, `created_by`) VALUES
('Empacadora Vertical EV-200', 'EV-200-2024', 'empacadora-vertical-ev-200', 'Empacadora vertical de alta velocidad diseñada para productos granulados como café, azúcar, cereales y productos químicos. Incluye sistema de dosificación volumétrica de alta precisión, sellado térmico por impulso y control PLC con pantalla táctil HMI de 10 pulgadas.', 'Empacadora vertical para productos granulados con control PLC y dosificación de alta precisión', 1, NULL, 'published', 1, 1, 1),
('Selladora Continua SC-150', 'SC-150-PRO', 'selladora-continua-sc-150', 'Selladora continua de banda para empaques flexibles con sistema de soldadura por impulso. Ideal para industria alimentaria y farmacéutica. Incluye control de temperatura digital, velocidad variable y sistema de enfriamiento por aire forzado.', 'Selladora continua profesional para empaques flexibles con control digital', 2, NULL, 'published', 1, 2, 1),
('Transportador Modular TM-300', 'TM-300-SS', 'transportador-modular-tm-300', 'Sistema de transporte modular fabricado en acero inoxidable 304. Diseño higiénico para industria alimentaria con banda de PVC grado alimentario. Velocidad variable mediante variador de frecuencia y motor trifásico de alta eficiencia.', 'Sistema de transporte modular en acero inoxidable para industria alimentaria', 3, NULL, 'published', 0, 3, 1);

-- Especificaciones técnicas
INSERT INTO `machine_specifications` (`machine_id`, `capacity`, `speed`, `power`, `width`, `height`, `depth`, `weight`, `materials`, `certifications`) VALUES
(1, '200 bolsas/hora', '50 m/min', '5 kW', 120.00, 200.00, 80.00, 450.00, '["Acero inoxidable 304", "Acero al carbón pintado"]', '["CE", "ISO 9001"]'),
(2, '150 m/min', 'Variable 0-150', '2.5 kW', 180.00, 120.00, 60.00, 85.00, '["Aluminio anodizado", "Acero inoxidable"]', '["CE", "UL Listed"]'),
(3, '500 kg/h', '0.5-2.0 m/s', '3 kW', 300.00, 90.00, 80.00, 180.00, '["Acero inoxidable 304", "PVC grado alimentario"]', '["NSF", "FDA", "CE"]');

SET FOREIGN_KEY_CHECKS = 1;

-- Mensaje de confirmación
SELECT 
  'Base de datos creada exitosamente' as Status,
  'admin@indumaqher.com / admin123' as Credenciales_Admin,
  COUNT(*) as Total_Tablas
FROM information_schema.tables 
WHERE table_schema = 'indumaqher_portfolio';
