<?php
// URL de la imagen del marcador de Leaflet
$imageUrl = 'https://unpkg.com/leaflet@1.9.3/dist/images/marker-icon.png';

// Ruta donde guardar la imagen
$savePath = __DIR__ . '/assets/img/marker-icon.png';

// Descargar la imagen
$imageContent = file_get_contents($imageUrl);

// Guardar la imagen
if ($imageContent !== false) {
    file_put_contents($savePath, $imageContent);
    echo "Imagen descargada y guardada correctamente en: " . $savePath;
} else {
    echo "Error al descargar la imagen.";
}
?>
