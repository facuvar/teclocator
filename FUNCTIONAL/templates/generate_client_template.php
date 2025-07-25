<?php
// Verificar si PHPSpreadsheet está instalado
if (!file_exists('../vendor/autoload.php')) {
    die('Por favor, ejecute "composer install" en el directorio raíz para instalar las dependencias necesarias.');
}

require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Crear un nuevo objeto Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Plantilla de Clientes');

// Definir los encabezados según la imagen proporcionada
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
foreach ($headers as $index => $header) {
    $column = chr(65 + $index); // A, B, C, etc.
    $sheet->setCellValue($column . '1', $header);
}

// Agregar algunos datos de ejemplo
$exampleData = [
    ['1001', 'Edificio Central SA', 'Av. Corrientes', '1234', 'Buenos Aires', 'CABA', 'Argentina', '', '', 'Grupo A', '011-4567-8901'],
    ['1002', 'Torre Norte SRL', 'Av. del Libertador', '5678', 'Buenos Aires', 'CABA', 'Argentina', '', '', 'Grupo B', '011-2345-6789'],
    ['1003', 'Complejo Sur SA', 'Av. Independencia', '987', 'Buenos Aires', 'CABA', 'Argentina', '', '', 'Grupo A', '011-9876-5432']
];

// Agregar los datos de ejemplo
$row = 2;
foreach ($exampleData as $data) {
    foreach ($data as $index => $value) {
        $column = chr(65 + $index);
        $sheet->setCellValue($column . $row, $value);
    }
    $row++;
}

// Dar formato a los encabezados - usar colores similares a los de la imagen
$headerRange = 'A1:K1';
$sheet->getStyle($headerRange)->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '7AB648'], // Verde similar al de la imagen
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
]);

// Dar formato a las celdas de datos
$dataRange = 'A2:K' . ($row - 1);
$sheet->getStyle($dataRange)->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
]);

// Ajustar el ancho de las columnas automáticamente
foreach (range('A', 'K') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Crear el escritor para guardar el archivo
$writer = new Xlsx($spreadsheet);

// Establecer las cabeceras para la descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="clients_template.xlsx"');
header('Cache-Control: max-age=0');

// Guardar el archivo directamente en la salida
$writer->save('php://output');
exit;
?>
