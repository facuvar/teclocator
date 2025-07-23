-- Actualizar la tabla de visitas para incluir coordenadas de geolocalización
ALTER TABLE visits 
ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER failure_reason,
ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude;

-- Actualizar la estructura de la tabla en el esquema principal
UPDATE visits SET latitude = NULL, longitude = NULL WHERE id > 0;
