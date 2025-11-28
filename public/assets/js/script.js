document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const sidebarToggle = document.getElementById('sidebarToggle');

    // Sidebar Toggle Logic
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            console.log('Sidebar toggle clicked');
            document.body.classList.toggle('sidebar-is-collapsed');
            const isCollapsed = document.body.classList.contains('sidebar-is-collapsed');
            console.log('Sidebar collapsed state:', isCollapsed);
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
    }

    // Tooltip Logic
    const menuItems = document.querySelectorAll('.menu-item');
    let activeTooltip = null;

    menuItems.forEach(item => {
        item.addEventListener('mouseenter', function (e) {
            if (document.body.classList.contains('sidebar-is-collapsed')) {
                const title = this.getAttribute('title');
                if (!title) return;

                // Create tooltip
                activeTooltip = document.createElement('div');
                activeTooltip.className = 'custom-tooltip';
                activeTooltip.textContent = title;
                document.body.appendChild(activeTooltip);

                // Position tooltip
                const rect = this.getBoundingClientRect();
                activeTooltip.style.left = (rect.right + 10) + 'px';
                activeTooltip.style.top = (rect.top + (rect.height / 2) - (activeTooltip.offsetHeight / 2)) + 'px';

                // Temporarily remove title to prevent native tooltip
                this.setAttribute('data-original-title', title);
                this.removeAttribute('title');
            }
        });

        item.addEventListener('mouseleave', function () {
            if (activeTooltip) {
                activeTooltip.remove();
                activeTooltip = null;
            }
            // Restore title
            const originalTitle = this.getAttribute('data-original-title');
            if (originalTitle) {
                this.setAttribute('title', originalTitle);
                this.removeAttribute('data-original-title');
            }
        });
    });
});
