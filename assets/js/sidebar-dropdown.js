/**
 * BuildConnect Collapsible Dropdown Sidebar Navigation System
 * Controls accordion expansion/collapse, active group persistence, and smooth interaction
 * across Admin, Client, Worker, and Contractor dashboards.
 */

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.bc-sidebar, .dashboard-sidebar');
    if (!sidebar) return;

    const storageKey = 'buildconnect_sidebar_open_groups';

    // Helper: read open groups from sessionStorage
    const getStoredGroups = () => {
        try {
            const raw = sessionStorage.getItem(storageKey);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            return [];
        }
    };

    // Helper: save open groups to sessionStorage
    const setStoredGroups = (groups) => {
        try {
            sessionStorage.setItem(storageKey, JSON.stringify(groups));
        } catch (e) {}
    };

    let openGroups = getStoredGroups();

    // 1. Initial State: Restore any previously open groups from session
    // (Active groups are already opened server-side in PHP)
    sidebar.querySelectorAll('.bc-nav-dropdown').forEach(dropdown => {
        const groupId = dropdown.getAttribute('data-group');
        if (!groupId) return;

        if (dropdown.classList.contains('is-open')) {
            // Already opened by PHP because it contains the active page
            if (!openGroups.includes(groupId)) {
                openGroups.push(groupId);
            }
        } else if (openGroups.includes(groupId)) {
            // Restore from session
            dropdown.classList.add('is-open');
        }
    });
    setStoredGroups(openGroups);

    // 2. Click Handler for Dropdown Toggles (Jump-Free & Accessible)
    sidebar.addEventListener('click', (e) => {
        const toggle = e.target.closest('.bc-dropdown-toggle');
        if (!toggle) return;

        e.preventDefault();
        e.stopPropagation();

        const dropdown = toggle.closest('.bc-nav-dropdown');
        if (!dropdown) return;

        const groupId = dropdown.getAttribute('data-group');
        const isOpen = dropdown.classList.toggle('is-open');

        // Update stored groups in session
        openGroups = getStoredGroups();
        if (groupId) {
            if (isOpen) {
                if (!openGroups.includes(groupId)) openGroups.push(groupId);
            } else {
                openGroups = openGroups.filter(id => id !== groupId);
            }
            setStoredGroups(openGroups);
        }
    });
});
