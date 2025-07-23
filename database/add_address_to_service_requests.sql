-- Add address column to service_requests table
-- Ejecutar este script en la base de datos teclocate_db

ALTER TABLE service_requests 
ADD COLUMN address VARCHAR(255) NOT NULL AFTER phone;
