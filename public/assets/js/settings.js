//********Start of Settings Sidebar */
document.addEventListener('DOMContentLoaded', function() {
    // Get all menu items
    const menuItems = document.querySelectorAll('.menu-item');
    
    // Function to activate a tab
    function activateTab(sectionId) {
        // Remove active class from all menu items and sections
        document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
        document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
        
        // Find the target section and corresponding menu item
        const targetSection = document.getElementById(sectionId);
        const targetMenuItem = document.querySelector(`.menu-item[data-section="${sectionId}"]`);
        
        // Activate them if found
        if (targetSection && targetMenuItem) {
            targetMenuItem.classList.add('active');
            targetSection.classList.add('active');
            // Store the active tab in localStorage
            localStorage.setItem('activeTab', sectionId);
        }
    }
    
    // Add click event to each menu item
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            const sectionId = this.getAttribute('data-section');
            activateTab(sectionId);
        });
    });
    
    // Check if this is the first visit by looking for our localStorage marker
    const isFirstVisit = localStorage.getItem('visitedBefore') === null;
    
    if (isFirstVisit) {
        // First visit - activate first tab and set marker
        localStorage.setItem('visitedBefore', 'true');
        const firstMenuItem = document.querySelector('.menu-item');
        if (firstMenuItem) {
            const firstSectionId = firstMenuItem.getAttribute('data-section');
            activateTab(firstSectionId);
        }
    } else {
        // Subsequent visit - check for saved tab
        const savedTab = localStorage.getItem('activeTab');
        if (savedTab) {
            activateTab(savedTab);
        } else {
            // Fallback to first tab if no saved preference
            const firstMenuItem = document.querySelector('.menu-item');
            if (firstMenuItem) {
                const firstSectionId = firstMenuItem.getAttribute('data-section');
                activateTab(firstSectionId);
            }
        }
    }
});

//********Start of Api tabs */
document.addEventListener('DOMContentLoaded', function() {
    // API Tab Switching
    const apiTabs = document.querySelectorAll('.api-tab');
    apiTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            document.querySelectorAll('.api-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.api-tab-content').forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Toggle password visibility
    document.querySelectorAll('.btn-toggle-visibility').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });

});

