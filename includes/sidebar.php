<aside class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-tiger"></i> <?php echo APP_NAME; ?></h2>
    </div>
    
    <nav class="sidebar-menu">
        <div class="menu-section">
            <div class="menu-section-title">Main</div>
            <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">
                <span>HR Management</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="menu-items-container">
                <a href="employees.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'employees.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Employees</span>
                </a>
                <a href="attendance.php" class="menu-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
                <a href="leaves.php" class="menu-item">
                    <i class="fas fa-calendar-times"></i>
                    <span>Leave Management</span>
                </a>
                <a href="payroll.php" class="menu-item">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Payroll</span>
                </a>
                <a href="hr-settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'hr-settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cogs"></i>
                    <span>HR Settings</span>
                </a>
                <a href="payroll-settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'payroll-settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Payroll Settings</span>
                </a>
            </div>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">
                <span>Inventory</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="menu-items-container">
                <a href="products.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
                <a href="warehouses.php" class="menu-item">
                    <i class="fas fa-warehouse"></i>
                    <span>Warehouses</span>
                </a>
                <a href="stock.php" class="menu-item">
                    <i class="fas fa-boxes"></i>
                    <span>Stock Management</span>
                </a>
                <a href="inventory-settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'inventory-settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-sliders-h"></i>
                    <span>Inventory Settings</span>
                </a>
            </div>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">
                <span>Sales</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="menu-items-container">
                <a href="sales-dashboard.php" class="menu-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Sales Dashboard</span>
                </a>
                <a href="customers.php" class="menu-item">
                    <i class="fas fa-user-tie"></i>
                    <span>Customers</span>
                </a>
                <a href="quotations.php" class="menu-item">
                    <i class="fas fa-file-invoice"></i>
                    <span>Quotations</span>
                </a>
                <a href="sales-orders.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Sales Orders</span>
                </a>
                <a href="invoices.php" class="menu-item">
                    <i class="fas fa-receipt"></i>
                    <span>Invoices</span>
                </a>
                <a href="payment-tracking.php" class="menu-item">
                    <i class="fas fa-money-check-alt"></i>
                    <span>Payment Tracking</span>
                </a>
                <a href="leads.php" class="menu-item">
                    <i class="fas fa-bullseye"></i>
                    <span>Leads</span>
                </a>
                <a href="sales-reports.php" class="menu-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Sales Reports</span>
                </a>
            </div>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">
                <span>Purchase</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="menu-items-container">
                <a href="suppliers.php" class="menu-item">
                    <i class="fas fa-truck"></i>
                    <span>Suppliers</span>
                </a>
                <a href="purchase-orders.php" class="menu-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Purchase Orders</span>
                </a>
                <a href="grn.php" class="menu-item">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Goods Receipt</span>
                </a>
            </div>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">
                <span>Accounting</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="menu-items-container">
                <a href="accounts.php" class="menu-item">
                    <i class="fas fa-book"></i>
                    <span>Chart of Accounts</span>
                </a>
                <a href="journal-entries.php" class="menu-item">
                    <i class="fas fa-edit"></i>
                    <span>Journal Entries</span>
                </a>
                <a href="reports.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Financial Reports</span>
                </a>
                <a href="gst-reports.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'gst-reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>GST Reports</span>
                </a>
            </div>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">
                <span>CRM</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="menu-items-container">
                <a href="leads.php" class="menu-item">
                    <i class="fas fa-bullseye"></i>
                    <span>Leads</span>
                </a>
                <a href="opportunities.php" class="menu-item">
                    <i class="fas fa-handshake"></i>
                    <span>Opportunities</span>
                </a>
                <a href="activities.php" class="menu-item">
                    <i class="fas fa-tasks"></i>
                    <span>Activities</span>
                </a>
            </div>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">
                <span>Production</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="menu-items-container">
                <a href="bom.php" class="menu-item">
                    <i class="fas fa-list-alt"></i>
                    <span>Bill of Materials</span>
                </a>
                <a href="work-orders.php" class="menu-item">
                    <i class="fas fa-industry"></i>
                    <span>Work Orders</span>
                </a>
            </div>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">
                <span>System</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="menu-items-container">
                <a href="company-settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'company-settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i>
                    <span>Company Settings</span>
                </a>
                <a href="settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>System Settings</span>
                </a>
            </div>
        </div>
    </nav>
</aside>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Select all menu titles that have a dropdown
    const menuTitles = document.querySelectorAll('.menu-section-title');

    menuTitles.forEach(title => {
        title.addEventListener('click', function() {
            // Find the parent section
            const section = this.closest('.menu-section');
            
            // Toggle the 'open' class
            section.classList.toggle('open');
            
            // Optional: Close other sections when one opens (Accordion style)
            // If you want this, uncomment the lines below:
            /*
            document.querySelectorAll('.menu-section').forEach(otherSection => {
                if (otherSection !== section) {
                    otherSection.classList.remove('open');
                }
            });
            */
        });
    });

    // 2. Open the section that contains the active link
    const activeLink = document.querySelector('.menu-item.active');
    if (activeLink) {
        // Find the parent menu-section
        const parentSection = activeLink.closest('.menu-section');
        if (parentSection) {
            parentSection.classList.add('open');
        }
    }
});
</script>