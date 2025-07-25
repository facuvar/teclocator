/**
 * Sidebar toggle functionality for mobile devices
 * Version: 1.1 (with force refresh)
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Sidebar toggle script loaded');
    
    // Get elements
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    // Create overlay element if it doesn't exist
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
        console.log('Overlay created');
    }
    
    // Toggle sidebar when button is clicked
    if (sidebarToggle) {
        console.log('Toggle button found');
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Toggle button clicked');
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
    } else {
        console.error('Sidebar toggle button not found!');
    }
    
    // Close sidebar when overlay is clicked
    overlay.addEventListener('click', function() {
        console.log('Overlay clicked');
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    });
    
    // Close sidebar when window is resized to desktop size
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        }
    });
    
    // Force immediate application of styles
    if (sidebar) {
        console.log('Sidebar found:', sidebar);
        // Force a reflow
        void sidebar.offsetWidth;
    } else {
        console.error('Sidebar element not found!');
    }
});
