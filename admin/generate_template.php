<?php
/**
 * Script para generar una plantilla Excel para la importación de clientes
 */
require_once '../includes/init.php';

// Cargar el autoloader de Composer explícitamente
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
}

// Crear instancia de Auth y verificar si es administrador
$auth = new Auth();
$auth->requireAdmin();

// Verificar si se ha instalado PHPSpreadsheet
if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    die('Error: La biblioteca PHPSpreadsheet no está instalada correctamente. Ejecute "composer require phpoffice/phpspreadsheet" en el directorio raíz.');
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Crear una nueva hoja de cálculo
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Definir los encabezados
$headers = [
    'Nro. Cliente',
    'Razón Social',
    'Calle',
    'Número',
    'Localidad',
    'Provincia',
    'País',
    'Latitud',
    'Longitud',
    'Grupo/Vendedor',
    'Teléfono'
];

// Establecer los encabezados en la primera fila
foreach ($headers as $columnIndex => $header) {
    $column = chr(65 + $columnIndex); // Convertir índice a letra (A, B, C, etc.)
    $sheet->setCellValue($column . '1', $header);
}

// Dar formato a los encabezados
$headerRange = 'A1:' . chr(65 + count($headers) - 1) . '1';
$sheet->getStyle($headerRange)->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4F81BD'],
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
    ],
]);

// Añadir datos de ejemplo en la segunda fila
$exampleData = [
    '12345',
    'Empresa Ejemplo S.A.',
    'Av. Rivadavia',
    '1234',
    'Buenos Aires',
    'CABA',
    'Argentina',
    '-34.603722',
    '-58.381592',
    'Grupo A',
    '011-4567-8901'
];

foreach ($exampleData as $columnIndex => $value) {
    $column = chr(65 + $columnIndex);
    $sheet->setCellValue($column . '2', $value);
}

// Dar formato a los datos de ejemplo
$dataRange = 'A2:' . chr(65 + count($exampleData) - 1) . '2';
$sheet->getStyle($dataRange)->applyFromArray([
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E9EDF5'],
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'BFBFBF'],
        ],
    ],
]);

// Ajustar el ancho de las columnas automáticamente
foreach (range('A', chr(65 + count($headers) - 1)) as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Establecer el nombre de la hoja
$sheet->setTitle('Plantilla Clientes');

// Crear el objeto writer
$writer = new Xlsx($spreadsheet);

// Establecer las cabeceras HTTP para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="plantilla_importacion_clientes.xlsx"');
header('Cache-Control: max-age=0');

// Guardar el archivo directamente en la salida
$writer->save('php://output');
exit;
?>
