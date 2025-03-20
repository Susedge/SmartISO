// Scripts for SB Admin template functionality
window.addEventListener('DOMContentLoaded', event => {
    // Toggle the side navigation
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', event => {
            event.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
            localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
        });
    }
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Add active class to current navigation item
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPath || (href !== '/' && currentPath.startsWith(href))) {
            link.classList.add('active');
        }
    });

    // Add page transition spinner
    function createSpinner() {
        const spinnerDiv = document.createElement('div');
        spinnerDiv.id = 'page-spinner';
        spinnerDiv.innerHTML = `
            <div class="spinner-overlay">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        // Add styles for the spinner
        const style = document.createElement('style');
        style.textContent = `
            .spinner-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(255, 255, 255, 0.7);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
            }
        `;
        
        document.head.appendChild(style);
        document.body.appendChild(spinnerDiv);
        return spinnerDiv;
    }
    
    // Create spinner element (hidden by default)
    const spinner = createSpinner();
    spinner.style.display = 'none';
    
    // Show spinner when clicking on navigation links
    document.addEventListener('click', function(e) {
        // Check if the clicked element is a link that navigates to a new page
        const isLink = e.target.tagName === 'A' || 
                      (e.target.parentElement && e.target.parentElement.tagName === 'A');
        
        if (isLink) {
            const link = e.target.tagName === 'A' ? e.target : e.target.parentElement;
            const href = link.getAttribute('href');
            
            // Only show spinner for internal links that aren't hash links
            if (href && 
                href.indexOf('#') !== 0 && 
                !href.startsWith('javascript:') &&
                !e.ctrlKey && !e.metaKey && !e.shiftKey) {
                
                spinner.style.display = 'block';
                
                // If navigation hasn't happened after 8 seconds, hide the spinner
                // (safety measure in case navigation fails)
                setTimeout(() => {
                    spinner.style.display = 'none';
                }, 8000);
            }
        }
    });
});
