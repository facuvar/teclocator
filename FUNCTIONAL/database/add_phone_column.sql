-- Script para añadir la columna de teléfono a la tabla de clientes
ALTER TABLE clients ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL AFTER address;
