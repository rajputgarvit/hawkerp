#!/bin/bash
# Script to delete old files from root directory after reorganization

echo "Deleting old files from root directory..."

# Auth files
rm -f login.php register.php logout.php onboarding.php

# Subscription files
rm -f select-plan.php checkout.php create-trial-subscription.php

# Dashboard files
rm -f dashboard.php sales-dashboard.php

# Sales files
rm -f invoices.php create-invoice.php edit-invoice.php invoice-template.php record-payment.php
rm -f quotations.php create-quotation.php edit-quotation.php quotation-template.php
rm -f sales-orders.php create-sales-order.php edit-sales-order.php sales-order-template.php
rm -f sales-reports.php

# Purchase files
rm -f purchase-invoices.php create-purchase-invoice.php edit-purchase-invoice.php purchase-invoice-template.php
rm -f purchase-orders.php

# Inventory files
rm -f products.php create-product.php edit-product.php create-category.php
rm -f warehouses.php create-warehouse.php stock.php create-stock-adjustment.php
rm -f inventory-settings.php

# Accounting files
rm -f accounts.php create-account.php edit-account.php
rm -f journal-entries.php create-journal-entry.php view-journal-entry.php
rm -f balance-sheet.php profit-loss.php trial-balance.php cash-flow.php ledger.php gst-reports.php
rm -f account-statement.php fiscal-years.php create-fiscal-year.php close-fiscal-year.php

# HR files
rm -f employees.php create-employee.php attendance.php mark-attendance.php
rm -f leaves.php apply-leave.php payroll.php process-payroll.php create-payroll-component.php
rm -f create-department.php create-designation.php hr-settings.php

# CRM files
rm -f customers.php create-customer.php edit-customer.php customer-details.php
rm -f suppliers.php create-supplier.php leads.php create-lead.php

# Settings & Reports
rm -f settings.php company-settings.php payroll-settings.php
rm -f reports.php payment-tracking.php

# Old landing page and assets (now in public/)
rm -f landing.html
rm -rf assets

echo "Old files deleted successfully!"
echo "Module-based structure is now active."
