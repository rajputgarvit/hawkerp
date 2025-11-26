<?php
// Fetch branding settings if not already available
if (!isset($brandingSettings)) {
    $db = Database::getInstance();
    $brandingSettings = $db->fetchOne("SELECT app_name, logo_path, theme_color FROM company_settings WHERE id = ? LIMIT 1", [$_SESSION['company_id'] ?? 0]);
}
$appName = !empty($brandingSettings['app_name']) ? $brandingSettings['app_name'] : APP_NAME;
$logoPath = !empty($brandingSettings['logo_path']) ? BASE_URL . $brandingSettings['logo_path'] : '';
?>
<?php if (!isset($_SERVER['HTTP_X_SPA_REQUEST']) || $_SERVER['HTTP_X_SPA_REQUEST'] !== 'true'): ?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2 style="display: flex; flex-direction: column; align-items: center; gap: 10px;">
            <?php if ($logoPath): ?>
                <img id="sidebar-logo" src="<?php echo $logoPath; ?>" alt="Logo" style="max-height: 50px; vertical-align: middle;">
            <?php else: ?>
                <i id="sidebar-logo-icon" class="fas fa-tiger" style="font-size: 24px; margin-bottom: 5px;"></i>
            <?php endif; ?>
            <span id="sidebar-app-name" style="font-size: 18px;"><?php echo htmlspecialchars($appName); ?></span>
        </h2>
    </div>
    
    <nav class="sidebar-menu">
        <div class="menu-section">
            <div class="menu-section-title">Main</div>
            <a href="<?php echo MODULES_URL; ?>/dashboard/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false ? 'active' : ''; ?>" title="Dashboard">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">
                <span>HR Management</span>
            </div>
            <?php if ($auth->hasModuleAccess('hrm')): ?>
                <a href="<?php echo MODULES_URL; ?>/hr/employees/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/hr/employees/') !== false ? 'active' : ''; ?>" title="Employees">
                    <i class="fas fa-users"></i>
                    <span>Employees</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/hr/attendance/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/hr/attendance/') !== false ? 'active' : ''; ?>" title="Attendance">
                    <i class="fas fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/hr/leaves/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/hr/leaves/') !== false ? 'active' : ''; ?>" title="Leave Management">
                    <i class="fas fa-calendar-times"></i>
                    <span>Leave Management</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/hr/payroll/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/hr/payroll/') !== false && strpos($_SERVER['PHP_SELF'], 'settings') === false ? 'active' : ''; ?>" title="Payroll">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Payroll</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/hr/settings" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/hr/settings.php') !== false ? 'active' : ''; ?>" title="HR Settings">
                    <i class="fas fa-cogs"></i>
                    <span>HR Settings</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/hr/payroll/components" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/hr/payroll/components.php') !== false ? 'active' : ''; ?>" title="Payroll Settings">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Payroll Settings</span>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">
                <span>Inventory</span>
            </div>
            <?php if ($auth->hasModuleAccess('inventory')): ?>
                <a href="<?php echo MODULES_URL; ?>/inventory/products/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/inventory/products/') !== false ? 'active' : ''; ?>" title="Products">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/inventory/warehouses/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/inventory/warehouses/') !== false ? 'active' : ''; ?>" title="Warehouses">
                    <i class="fas fa-warehouse"></i>
                    <span>Warehouses</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/inventory/stock/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/inventory/stock/') !== false ? 'active' : ''; ?>" title="Stock Management">
                    <i class="fas fa-boxes"></i>
                    <span>Stock Management</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/inventory/settings" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/inventory/settings.php') !== false ? 'active' : ''; ?>" title="Inventory Settings">
                    <i class="fas fa-sliders-h"></i>
                    <span>Inventory Settings</span>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">
                <span>Sales</span>
            </div>
            <?php if ($auth->hasModuleAccess('sales')): ?>
                <a href="<?php echo MODULES_URL; ?>/dashboard/sales" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/dashboard/sales.php') !== false ? 'active' : ''; ?>" title="Sales Dashboard">
                    <i class="fas fa-chart-line"></i>
                    <span>Sales Dashboard</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/crm/customers/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/crm/customers/') !== false ? 'active' : ''; ?>" title="Customers">
                    <i class="fas fa-user-tie"></i>
                    <span>Customers</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/sales/quotations/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/sales/quotations/') !== false ? 'active' : ''; ?>" title="Quotations">
                    <i class="fas fa-file-invoice"></i>
                    <span>Quotations</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/sales/orders/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/sales/orders/') !== false ? 'active' : ''; ?>" title="Sales Orders">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Sales Orders</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/sales/invoices/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/sales/invoices/') !== false ? 'active' : ''; ?>" title="Invoices">
                    <i class="fas fa-receipt"></i>
                    <span>Invoices</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/reports/payment-tracking" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/reports/payment-tracking.php') !== false ? 'active' : ''; ?>" title="Payment Tracking">
                    <i class="fas fa-money-check-alt"></i>
                    <span>Payment Tracking</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/crm/leads/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/crm/leads/') !== false ? 'active' : ''; ?>" title="Leads">
                    <i class="fas fa-bullseye"></i>
                    <span>Leads</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/sales/reports/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/sales/reports/') !== false ? 'active' : ''; ?>" title="Sales Reports">
                    <i class="fas fa-file-alt"></i>
                    <span>Sales Reports</span>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">
                <span>Purchase</span>
            </div>
            <?php if ($auth->hasModuleAccess('purchases')): ?>
                <a href="<?php echo MODULES_URL; ?>/crm/suppliers/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/crm/suppliers/') !== false ? 'active' : ''; ?>" title="Suppliers">
                    <i class="fas fa-truck"></i>
                    <span>Suppliers</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/purchases/orders/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/purchases/orders/') !== false ? 'active' : ''; ?>" title="Purchase Orders">
                    <i class="fas fa-file-alt"></i>
                    <span>Purchase Orders</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/purchases/invoices/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/purchases/invoices/') !== false ? 'active' : ''; ?>" title="Purchase Invoices">
                    <i class="fas fa-file-invoice"></i>
                    <span>Purchase Invoices</span>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">
                <span>Accounting</span>
            </div>
            <?php if ($auth->hasModuleAccess('accounting')): ?>
                <a href="<?php echo MODULES_URL; ?>/accounting/accounts/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/accounting/accounts/') !== false ? 'active' : ''; ?>" title="Chart of Accounts">
                    <i class="fas fa-book"></i>
                    <span>Chart of Accounts</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/accounting/journal/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/accounting/journal/') !== false ? 'active' : ''; ?>" title="Journal Entries">
                    <i class="fas fa-edit"></i>
                    <span>Journal Entries</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/accounting/reports/balance-sheet" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/accounting/reports/') !== false && strpos($_SERVER['PHP_SELF'], 'gst') === false ? 'active' : ''; ?>" title="Financial Reports">
                    <i class="fas fa-chart-bar"></i>
                    <span>Financial Reports</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/accounting/reports/gst-reports" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/accounting/reports/gst-reports.php') !== false ? 'active' : ''; ?>" title="GST Reports">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>GST Reports</span>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="menu-section">
            <div class="menu-section-title">
                <span>CRM</span>
            </div>
            <?php if ($auth->hasModuleAccess('crm')): ?>
                <a href="<?php echo MODULES_URL; ?>/crm/leads/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/crm/leads/') !== false ? 'active' : ''; ?>" title="Leads">
                    <i class="fas fa-bullseye"></i>
                    <span>Leads</span>
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Missing Production Module -->
        <!-- <div class="menu-section">
            <div class="menu-section-title">
                <span>Production</span>
            </div>

                <a href="#" class="menu-item" title="Bill of Materials">
                    <i class="fas fa-list-alt"></i>
                    <span>Bill of Materials</span>
                </a>
                <a href="#" class="menu-item" title="Work Orders">
                    <i class="fas fa-industry"></i>
                    <span>Work Orders</span>
                </a>
        </div> -->
        
        <div class="menu-section">
            <div class="menu-section-title">
                <span>System</span>
            </div>

                <a href="<?php echo MODULES_URL; ?>/settings/company" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/settings/company.php') !== false ? 'active' : ''; ?>" title="Company Settings">
                    <i class="fas fa-building"></i>
                    <span>Company Settings</span>
                </a>
                <a href="<?php echo MODULES_URL; ?>/settings/index" class="menu-item <?php echo strpos($_SERVER['PHP_SELF'], '/settings/index.php') !== false ? 'active' : ''; ?>" title="System Settings">
                    <i class="fas fa-cog"></i>
                    <span>System Settings</span>
                </a>

        </div>
    </nav>
</aside>
<?php if (!empty($brandingSettings['theme_color'])): ?>
<style>
    :root {
        --primary-color: <?php echo htmlspecialchars($brandingSettings['theme_color']); ?>;
        --sidebar-active-border: <?php echo htmlspecialchars($brandingSettings['theme_color']); ?>;
    }
    .menu-item.active {
        border-left-color: var(--primary-color);
        color: var(--primary-color);
    }
    .menu-item:hover {
        border-left-color: var(--primary-color);
        color: var(--primary-color);
    }
    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    .sidebar-header h2 i {
        color: var(--primary-color);
    }
</style>
<?php endif; ?>
    <script src="<?php echo BASE_URL; ?>public/assets/js/script.js"></script>
    <script src="<?php echo BASE_URL; ?>public/assets/js/spa.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>