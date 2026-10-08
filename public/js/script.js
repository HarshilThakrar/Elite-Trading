document.addEventListener('DOMContentLoaded', () => {
    // 1. Toggle Statistics Section
    const statsToggle = document.getElementById('stats-toggle');
    const statsSection = document.getElementById('stats-section');
    
    if (statsToggle && statsSection) {
        statsToggle.addEventListener('change', (e) => {
            if (e.target.checked) {
                statsSection.classList.remove('hidden');
            } else {
                statsSection.classList.add('hidden');
            }
        });
    }

    // 2. Table Row Selection Logic
    const selectAllCheckbox = document.querySelector('.select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const actionBar = document.getElementById('action-bar');
    const selectedNumberDisplay = document.getElementById('selected-number');
    const closeActionBtn = document.getElementById('close-action');

    function updateSelectionState() {
        let selectedCount = 0;
        
        rowCheckboxes.forEach(checkbox => {
            const row = checkbox.closest('tr');
            if (checkbox.checked) {
                row.classList.add('selected-row');
                selectedCount++;
            } else {
                row.classList.remove('selected-row');
            }
        });

        // Update Select All Checkbox state
        if (selectedCount === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (selectedCount === rowCheckboxes.length) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }

        // Show/Hide Action Bar
        if (selectedCount > 0) {
            selectedNumberDisplay.textContent = selectedCount;
            actionBar.classList.add('visible');
        } else {
            actionBar.classList.remove('visible');
        }
    }

    // Event Listeners for individual checkboxes
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectionState);
    });

    // Event Listener for Select All checkbox
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', (e) => {
            const isChecked = e.target.checked;
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateSelectionState();
        });
    }

    // Close Action Bar button
    if (closeActionBtn) {
        closeActionBtn.addEventListener('click', () => {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelectionState();
        });
    }

    // Initialize state on load
    updateSelectionState();
    // Sidebar Collapse Logic
    const collapseBtn = document.querySelector('.collapse-btn');
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    
    if (sidebar) {
        const toggleSidebar = () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('mobile-open');
                if (sidebarOverlay) sidebarOverlay.classList.toggle('active');
            } else {
                sidebar.classList.toggle('collapsed');
            }
        };
        
        if (collapseBtn) collapseBtn.addEventListener('click', toggleSidebar);
        if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleSidebar);
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('active');
            });
        }
    }
});