/**
 * Invoices Module JavaScript
 */

// Initialize variables
if (typeof window.rowIndex === 'undefined') {
    window.rowIndex = 1;
}

// Function to update all product dropdowns
window.updateAllProductDropdowns = function() {
    const selects = document.querySelectorAll('.product-select');
    selects.forEach(select => {
        const currentValue = select.value;
        // Clear existing options except the first one
        while (select.options.length > 1) {
            select.remove(1);
        }
        // Add updated options
        if (window.productsData) {
            window.productsData.forEach(p => {
                const option = document.createElement('option');
                option.value = p.id;
                option.dataset.price = p.selling_price;
                option.dataset.tax = p.tax_rate;
                option.dataset.name = p.name;
                option.dataset.hasSerial = p.has_serial_number;
                option.dataset.hasWarranty = p.has_warranty;
                option.dataset.hasExpiry = p.has_expiry_date;
                option.textContent = `${p.product_code} - ${p.name}`;
                select.appendChild(option);
            });
        }
        // Restore selected value if it still exists
        select.value = currentValue;
    });
}

window.openQuickAddModal = function() {
    document.getElementById('quickAddProductModal').style.display = 'block';
}

window.closeQuickAddModal = function() {
    document.getElementById('quickAddProductModal').style.display = 'none';
    document.getElementById('quickAddProductForm').reset();
}

window.openQuickAddCategoryModal = function() {
    document.getElementById('quickAddCategoryModal').style.display = 'block';
}

window.closeQuickAddCategoryModal = function() {
    document.getElementById('quickAddCategoryModal').style.display = 'none';
    document.getElementById('quickAddCategoryForm').reset();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('quickAddProductModal');
    const catModal = document.getElementById('quickAddCategoryModal');
    if (event.target == modal) {
        closeQuickAddModal();
    }
    if (event.target == catModal) {
        closeQuickAddCategoryModal();
    }
}

// Handle Quick Add Product Form Submission
document.addEventListener('DOMContentLoaded', function() {
    const quickAddProductForm = document.getElementById('quickAddProductForm');
    if (quickAddProductForm) {
        quickAddProductForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            
            fetch('../../../ajax/add-product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Add new product to local data
                    const newProduct = result.product;
                    if (window.productsData) {
                        window.productsData.push({
                            id: newProduct.id,
                            product_code: newProduct.product_code,
                            name: newProduct.name,
                            selling_price: newProduct.selling_price,
                            tax_rate: newProduct.tax_rate,
                            has_serial_number: newProduct.has_serial_number || 0,
                            has_warranty: newProduct.has_warranty || 0,
                            has_expiry_date: newProduct.has_expiry_date || 0
                        });
                    }
                    
                    // Reload page to reflect new product (simplest way without custom dropdowns)
                    // Or we could dynamically add option to all selects
                    const selects = document.querySelectorAll('.product-select');
                    selects.forEach(select => {
                        const option = document.createElement('option');
                        option.value = result.product.id;
                        option.dataset.price = result.product.selling_price;
                        option.dataset.tax = result.product.tax_rate;
                        option.dataset.name = result.product.name;
                        option.dataset.hasSerial = result.product.has_serial_number || 0;
                        option.dataset.hasWarranty = result.product.has_warranty || 0;
                        option.dataset.hasExpiry = result.product.has_expiry_date || 0;
                        option.textContent = result.product.product_code + ' - ' + result.product.name;
                        select.appendChild(option);
                    });
                    
                    // Select the new product in the last row (or the row that triggered it if we tracked it)
                    // For now, let's just alert success
                    alert('Product added successfully!');
                    closeQuickAddModal();
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the product.');
            });
        });
    }

    // Handle Quick Add Category Form Submission
    const quickAddCategoryForm = document.getElementById('quickAddCategoryForm');
    if (quickAddCategoryForm) {
        quickAddCategoryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => data[key] = value);
            
            fetch('../../../ajax/add-category.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Add new category to dropdown
                    const select = document.getElementById('quickAddCategorySelect');
                    const option = document.createElement('option');
                    option.value = result.category.id;
                    option.textContent = result.category.name;
                    option.selected = true;
                    select.appendChild(option);
                    
                    alert('Category added successfully!');
                    closeQuickAddCategoryModal();
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the category.');
            });
        });
    }
    
    // Set default date to today if not set
    if (document.getElementById('invoiceDate') && !document.getElementById('invoiceDate').value) {
        document.getElementById('invoiceDate').valueAsDate = new Date();
    }
    
    // Initialize calculations
    calculateTotals();
});

window.addRow = function() {
    try {
        const tbody = document.getElementById('itemsBody');
        const firstRow = tbody.querySelector('.item-row');
        
        if (!firstRow) {
            console.error('No template row found');
            alert('Error: Unable to add new row. Please refresh the page.');
            return;
        }
        
        const newRow = firstRow.cloneNode(true);
        
        // Update name attributes and reset values
        newRow.querySelectorAll('input, select').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                input.setAttribute('name', name.replace(/\[\d+\]/, `[${rowIndex}]`));
            }
            
            // Reset values based on input type
            if (input.type === 'number') {
                input.value = input.classList.contains('item-quantity') ? '1' : '0';
            } else if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            } else {
                input.value = '';
            }
        });
        
        // Update onchange handlers
        const productSelect = newRow.querySelector('.product-select');
        if (productSelect) {
            productSelect.setAttribute('onchange', `updateProductDetails(this, ${rowIndex})`);
        }
        
        // Update handlers for calculation inputs
        newRow.querySelectorAll('.item-quantity, .item-price, .item-discount').forEach(input => {
            input.setAttribute('onchange', `calculateRow(this)`);
        });
        
        // Update handler for tax select specifically
        const taxSelect = newRow.querySelector('.item-tax');
        if (taxSelect) {
            taxSelect.setAttribute('onchange', `calculateRow(this)`);
        }
        
        // Add handler for serial number check
        const serialInput = newRow.querySelector('.item-serial');
        if (serialInput) {
            serialInput.setAttribute('onblur', `checkSerialAvailability(this)`);
        }
        
        // Clean up cloned row
        newRow.querySelectorAll('select').forEach(select => {
            select.selectedIndex = 0;
            select.value = '';
        });

        tbody.appendChild(newRow);
        
        console.log('Row added successfully, new rowIndex:', rowIndex);
        rowIndex++;
        
    } catch (error) {
        console.error('Error adding row:', error);
        alert('Failed to add new row. Please refresh the page and try again.');
    }
}

window.removeRow = function(btn) {
    const tbody = document.getElementById('itemsBody');
    if (tbody.children.length > 1) {
        btn.closest('tr').remove();
        calculateTotals();
    } else {
        alert('At least one item is required');
    }
}

window.updateProductDetails = function(select, index) {
    const option = select.options[select.selectedIndex];
    const row = select.closest('tr');
    
    if (option.value) {
        row.querySelector('.item-price').value = option.dataset.price || 0;
        row.querySelector('.item-tax').value = option.dataset.tax || 0;
        row.querySelector('.item-description').value = option.dataset.name || '';
        
        // Toggle tracking fields
        const serialInput = row.querySelector('.item-serial');
        const warrantyInput = row.querySelector('.item-warranty');
        const expiryInput = row.querySelector('.item-expiry');
        
        // Robust attribute retrieval
        const hasSerialVal = option.getAttribute('data-has-serial');
        const hasWarrantyVal = option.getAttribute('data-has-warranty');
        const hasExpiryVal = option.getAttribute('data-has-expiry');
        
        // Check for '1' or 'true' (just in case)
        const hasSerial = (hasSerialVal === '1' || hasSerialVal === 'true');
        const hasWarranty = (hasWarrantyVal === '1' || hasWarrantyVal === 'true');
        const hasExpiry = (hasExpiryVal === '1' || hasExpiryVal === 'true');
        
        if (serialInput) {
            serialInput.style.display = hasSerial ? 'block' : 'none';
            if (hasSerial) serialInput.setAttribute('required', 'required');
            else serialInput.removeAttribute('required');
        }
        
        if (warrantyInput) {
            warrantyInput.style.display = hasWarranty ? 'block' : 'none';
            if (hasWarranty) warrantyInput.setAttribute('required', 'required');
            else warrantyInput.removeAttribute('required');
        }
        
        if (expiryInput) {
            expiryInput.style.display = hasExpiry ? 'block' : 'none';
            if (hasExpiry) expiryInput.setAttribute('required', 'required');
            else expiryInput.removeAttribute('required');
        }
        
        calculateRow(select);
    }
}

window.calculateRow = function(element) {
    const row = element.closest('tr');
    if (!row) return; // Safety check
    
    const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const discountPercent = parseFloat(row.querySelector('.item-discount').value) || 0;
    const taxRate = parseFloat(row.querySelector('.item-tax').value) || 0;
    
    // Inclusive Pricing Logic
    const grossTotal = quantity * price;
    const discountAmount = grossTotal * (discountPercent / 100);
    const netTotal = grossTotal - discountAmount; // This is the Final Line Total (Inclusive)
    
    // Back-calculate Tax
    const taxableValue = netTotal / (1 + (taxRate / 100));
    const taxAmount = netTotal - taxableValue;
    
    // Display Total (Inclusive)
    row.querySelector('.item-total').value = netTotal.toFixed(2);
    
    calculateTotals();
}

window.calculateTotals = function() {
    let subtotal = 0; // Taxable Subtotal
    let totalTax = 0;
    let totalItemDiscount = 0;
    
    document.querySelectorAll('.item-row').forEach((row, index) => {
        const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const discountPercent = parseFloat(row.querySelector('.item-discount').value) || 0;
        const taxRate = parseFloat(row.querySelector('.item-tax').value) || 0;
        
        const grossTotal = quantity * price;
        const discountAmount = grossTotal * (discountPercent / 100);
        const netTotal = grossTotal - discountAmount;
        
        const taxableValue = netTotal / (1 + (taxRate / 100));
        const taxAmount = netTotal - taxableValue;
        
        subtotal += taxableValue;
        totalTax += taxAmount;
        totalItemDiscount += discountAmount;
    });
    
    const additionalDiscountInput = document.getElementById('discountAmount');
    let additionalDiscount = parseFloat(additionalDiscountInput.value) || 0;
    
    const maxDiscount = subtotal + totalTax;
    
    if (additionalDiscount > maxDiscount) {
        alert('Discount cannot exceed the total amount.');
        additionalDiscount = maxDiscount;
        additionalDiscountInput.value = additionalDiscount.toFixed(2);
    }
    
    
    const totalDiscount = totalItemDiscount + additionalDiscount;
    
    document.getElementById('subtotalDisplay').textContent = '₹' + subtotal.toFixed(2);
    document.getElementById('taxDisplay').textContent = '₹' + totalTax.toFixed(2);
    document.getElementById('discountDisplay').textContent = '₹' + totalItemDiscount.toFixed(2);
    document.getElementById('grandTotalDisplay').textContent = '₹' + (subtotal + totalTax - additionalDiscount).toFixed(2);
}

window.checkSerialAvailability = function(input, excludeInvoiceId = null) {
    const serialNumber = input.value.trim();
    if (!serialNumber) return;
    
    const payload = { serial_number: serialNumber };
    if (excludeInvoiceId || window.invoiceId) {
        payload.exclude_invoice_id = excludeInvoiceId || window.invoiceId;
    }

    fetch('../../../ajax/check-serial.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success && result.exists) {
            alert(result.message);
            input.value = ''; // Clear the invalid input
            input.focus();
        }
    })
    .catch(error => {
        console.error('Error checking serial number:', error);
    });
}
