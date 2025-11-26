#!/bin/bash
# Create backward compatibility redirect files

echo "Creating backward compatibility redirects..."

# Auth redirects
echo "<?php header('Location: modules/auth/login.php'); exit; ?>" > login.php.redirect
echo "<?php header('Location: modules/auth/register.php'); exit; ?>" > register.php.redirect
echo "<?php header('Location: modules/auth/logout.php'); exit; ?>" > logout.php.redirect

# Dashboard redirects
echo "<?php header('Location: modules/dashboard/index.php'); exit; ?>" > dashboard.php.redirect

# Sales redirects
echo "<?php header('Location: modules/sales/invoices/index.php'); exit; ?>" > invoices.php.redirect
echo "<?php header('Location: modules/sales/quotations/index.php'); exit; ?>" > quotations.php.redirect
echo "<?php header('Location: modules/sales/orders/index.php'); exit; ?>" > sales-orders.php.redirect

# Purchases redirects
echo "<?php header('Location: modules/purchases/invoices/index.php'); exit; ?>" > purchase-invoices.php.redirect

# Inventory redirects
echo "<?php header('Location: modules/inventory/products/index.php'); exit; ?>" > products.php.redirect
echo "<?php header('Location: modules/inventory/warehouses/index.php'); exit; ?>" > warehouses.php.redirect
echo "<?php header('Location: modules/inventory/stock/index.php'); exit; ?>" > stock.php.redirect

# Accounting redirects
echo "<?php header('Location: modules/accounting/accounts/index.php'); exit; ?>" > accounts.php.redirect
echo "<?php header('Location: modules/accounting/journal/index.php'); exit; ?>" > journal-entries.php.redirect

# HR redirects
echo "<?php header('Location: modules/hr/employees/index.php'); exit; ?>" > employees.php.redirect
echo "<?php header('Location: modules/hr/attendance/index.php'); exit; ?>" > attendance.php.redirect
echo "<?php header('Location: modules/hr/payroll/index.php'); exit; ?>" > payroll.php.redirect

# CRM redirects
echo "<?php header('Location: modules/crm/customers/index.php'); exit; ?>" > customers.php.redirect
echo "<?php header('Location: modules/crm/suppliers/index.php'); exit; ?>" > suppliers.php.redirect

# Settings redirects
echo "<?php header('Location: modules/settings/index.php'); exit; ?>" > settings.php.redirect

echo "Redirect files created with .redirect extension"
echo "To activate, rename .redirect files to .php (after testing)"
