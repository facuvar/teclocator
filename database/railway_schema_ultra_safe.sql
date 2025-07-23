SET FOREIGN_KEY_CHECKS=0;

-- Step 1: Create tables explicitly with InnoDB engine

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20),
    `role` ENUM('admin', 'technician') NOT NULL,
    `zone` ENUM('Norte', 'Sur', 'Este', 'Oeste') NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `clients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `business_name` VARCHAR(150) NOT NULL,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `address` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT NOT NULL,
    `technician_id` INT NOT NULL,
    `description` TEXT NOT NULL,
    `status` ENUM('pending', 'in_progress', 'completed', 'not_completed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `visits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `start_time` DATETIME NULL,
    `end_time` DATETIME NULL,
    `comments` TEXT NULL,
    `completion_status` ENUM('success', 'failure') NULL,
    `failure_reason` TEXT NULL,
    `latitude` DECIMAL(10, 8) NULL,
    `longitude` DECIMAL(11, 8) NULL
) ENGINE=InnoDB;

-- Step 2: Insert data

INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES 
('Administrador', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `zone`) VALUES 
('Técnico Demo', 'tecnico@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123456789', 'technician', 'Norte');

INSERT INTO `clients` (`name`, `business_name`, `latitude`, `longitude`, `address`) VALUES
('Edificio Central', 'Consorcios SA', -34.603722, -58.381592, 'Av. Corrientes 1234, Buenos Aires');

-- Step 3: Add foreign key constraints

ALTER TABLE `tickets`
ADD CONSTRAINT `fk_tickets_client_id`
FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE;

ALTER TABLE `tickets`
ADD CONSTRAINT `fk_tickets_technician_id`
FOREIGN KEY (`technician_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

ALTER TABLE `visits`
ADD CONSTRAINT `fk_visits_ticket_id`
FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS=1; 