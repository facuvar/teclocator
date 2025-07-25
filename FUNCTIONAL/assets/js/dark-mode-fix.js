/**
 * Script para forzar el tema oscuro y mejorar la legibilidad
 */
document.addEventListener('DOMContentLoaded', function() {
    // Forzar estilos para tablas
    const tableCells = document.querySelectorAll('.table td');
    tableCells.forEach(cell => {
        cell.style.color = '#ffffff';
        cell.style.fontWeight = '700';
        cell.style.textShadow = '0px 0px 1px rgba(0,0,0,0.5)';
    });
    
    // Forzar estilos para encabezados de tabla
    const tableHeaders = document.querySelectorAll('.table th');
    tableHeaders.forEach(header => {
        header.style.color = '#ffffff';
        header.style.fontWeight = '700';
        header.style.backgroundColor = '#333333';
    });
    
    // Aplicar estilos específicos a datos de clientes
    const clientData = document.querySelectorAll('.client-data');
    clientData.forEach(data => {
        data.style.color = '#ffffff';
        data.style.fontWeight = '700';
        data.style.fontSize = '1.05em';
    });
    
    // Aplicar estilos a coordenadas
    const coordinates = document.querySelectorAll('.coordinates');
    coordinates.forEach(coord => {
        coord.style.backgroundColor = 'rgba(33, 150, 243, 0.3)';
        coord.style.padding = '4px 8px';
        coord.style.borderRadius = '4px';
        coord.style.color = '#ffffff';
        coord.style.fontWeight = '700';
    });
    
    console.log('Dark mode fixes applied');
});
