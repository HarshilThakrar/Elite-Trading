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
        
        if (rowCheckboxes && rowCheckboxes.length > 0) {
            rowCheckboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                if (checkbox.checked) {
                    if (row) row.classList.add('selected-row');
                    selectedCount++;
                } else {
                    if (row) row.classList.remove('selected-row');
                }
            });
        }

        // Update Select All Checkbox state
        if (selectAllCheckbox) {
            if (selectedCount === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (rowCheckboxes && rowCheckboxes.length > 0 && selectedCount === rowCheckboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }

        // Show/Hide Action Bar
        if (actionBar) {
            if (selectedCount > 0) {
                if (selectedNumberDisplay) selectedNumberDisplay.textContent = selectedCount;
                actionBar.classList.add('visible');
            } else {
                actionBar.classList.remove('visible');
            }
        }
    }

    // Event Listeners for individual checkboxes
    if (rowCheckboxes && rowCheckboxes.length > 0) {
        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectionState);
        });
    }

    // Event Listener for Select All checkbox
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', (e) => {
            const isChecked = e.target.checked;
            if (rowCheckboxes && rowCheckboxes.length > 0) {
                rowCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
            }
            updateSelectionState();
        });
    }

    // Close Action Bar button
    if (closeActionBtn) {
        closeActionBtn.addEventListener('click', () => {
            if (rowCheckboxes && rowCheckboxes.length > 0) {
                rowCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            }
            updateSelectionState();
        });
    }

    // Initialize state on load only when relevant elements are present
    if (selectAllCheckbox || (rowCheckboxes && rowCheckboxes.length > 0)) {
        updateSelectionState();
    }

    // 3. Sidebar Collapse & Mobile Menu Logic
    const collapseBtn = document.querySelector('.collapse-btn');
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    
    if (sidebar) {
        const closeSidebarMobile = () => {
            sidebar.classList.remove('mobile-open');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
            document.body.classList.remove('sidebar-mobile-open');
        };

        const toggleSidebar = (e) => {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            if (window.innerWidth <= 991) {
                const isOpen = sidebar.classList.toggle('mobile-open');
                if (sidebarOverlay) {
                    if (isOpen) {
                        sidebarOverlay.classList.add('active');
                    } else {
                        sidebarOverlay.classList.remove('active');
                    }
                }
                document.body.classList.toggle('sidebar-mobile-open', isOpen);
            } else {
                sidebar.classList.toggle('collapsed');
            }
        };
        
        if (collapseBtn && !collapseBtn.dataset.bound) {
            collapseBtn.dataset.bound = "1";
            collapseBtn.addEventListener('click', (e) => {
                if (window.innerWidth <= 991) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeSidebarMobile();
                } else {
                    sidebar.classList.toggle('collapsed');
                }
            });
        }

        if (mobileMenuBtn && !mobileMenuBtn.dataset.bound) {
            mobileMenuBtn.dataset.bound = "1";
            mobileMenuBtn.addEventListener('click', toggleSidebar);
        }

        if (sidebarOverlay && !sidebarOverlay.dataset.bound) {
            sidebarOverlay.dataset.bound = "1";
            sidebarOverlay.addEventListener('click', (e) => {
                e.preventDefault();
                closeSidebarMobile();
            });
        }

        // Close sidebar on link click in mobile view
        const sidebarLinks = sidebar.querySelectorAll('a.menu-item');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 991) {
                    closeSidebarMobile();
                }
            });
        });

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
                closeSidebarMobile();
            }
        });

        // Handle window resizing
        window.addEventListener('resize', () => {
            if (window.innerWidth > 991 && sidebar.classList.contains('mobile-open')) {
                closeSidebarMobile();
            }
        });
    }
});