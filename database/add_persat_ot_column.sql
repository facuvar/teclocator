-- Add persat_ot column to tickets table
-- Ejecutar este script en la base de datos teclocate_db

ALTER TABLE tickets ADD COLUMN persat_ot VARCHAR(50) NULL AFTER description;
