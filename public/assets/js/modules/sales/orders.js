/**
 * Orders Module JavaScript
 */

// Initialize variables
if (typeof window.rowIndex === 'undefined') {
    window.rowIndex = 1;
}

// Initialize Select2
function initSelect2(element) {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        element.select2({
            width: '100%',
            placeholder: 'Select Product',
            allowClear: true
        });
    }
}

// Initialize function to set up event listeners
function initializeOrderForm() {
    // Initialize Select2 on existing elements
    if (typeof $ !== 'undefined' && $.fn.select2) {
        initSelect2($('.product-select'));
    }

    // Handle Quick Add Product Form Submission
    const quickAddProductForm = document.getElementById('quickAddProductForm');
    if (quickAddProductForm) {
        quickAddProductForm.addEventListener('submit', function (e) {
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

                        // Update all dropdowns
                        updateAllProductDropdowns();

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
        quickAddCategoryForm.addEventListener('submit', function (e) {
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

    // Initialize calculations
    calculateTotals();
}

// Execute initialization immediately if DOM is ready, otherwise wait for DOMContentLoaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeOrderForm);
} else {
    // DOM is already ready, execute immediately
    initializeOrderForm();
}

// Function to update all product dropdowns
window.updateAllProductDropdowns = function () {
    // Destroy Select2 instances
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.product-select').each(function () {
            if ($(this).data('select2')) {
                $(this).select2('destroy');
            }
        });
    }

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
                // Add tracking data attributes
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

    // Re-initialize Select2
    if (typeof $ !== 'undefined' && $.fn.select2) {
        initSelect2($('.product-select'));
    }
}

window.openQuickAddModal = function () {
    document.getElementById('quickAddProductModal').style.display = 'block';
}

window.closeQuickAddModal = function () {
    document.getElementById('quickAddProductModal').style.display = 'none';
    document.getElementById('quickAddProductForm').reset();
}

window.openQuickAddCategoryModal = function () {
    document.getElementById('quickAddCategoryModal').style.display = 'block';
}

window.closeQuickAddCategoryModal = function () {
    document.getElementById('quickAddCategoryModal').style.display = 'none';
    document.getElementById('quickAddCategoryForm').reset();
}

// Close modal when clicking outside
window.onclick = function (event) {
    const modal = document.getElementById('quickAddProductModal');
    const catModal = document.getElementById('quickAddCategoryModal');
    if (event.target == modal) {
        closeQuickAddModal();
    }
    if (event.target == catModal) {
        closeQuickAddCategoryModal();
    }
}

window.addRow = function () {
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

        // Remove select2-hidden-accessible class and select2 container from the clone
        const select = newRow.querySelector('select.product-select');
        if (select) {
            if (typeof $ !== 'undefined') {
                $(select).next('.select2-container').remove();
                $(select).removeClass('select2-hidden-accessible');
                $(select).removeAttr('data-select2-id');
                $(select).removeAttr('aria-hidden');
                $(select).removeAttr('tabindex');
            }
        }

        // Update onchange handlers
        const productSelect = newRow.querySelector('.product-select');
        if (productSelect) {
            productSelect.setAttribute('onchange', `updateProductDetails(this, ${rowIndex})`);
        }

        // Update handlers for calculation inputs
        newRow.querySelectorAll('.item-quantity, .item-price, .item-discount, .item-tax').forEach(input => {
            input.setAttribute('onchange', `calculateRow(this)`);
        });

        tbody.appendChild(newRow);

        // Initialize Select2 on the new row's select
        if (select && typeof $ !== 'undefined' && $.fn.select2) {
            initSelect2($(select));
        }

        console.log('Row added successfully, new rowIndex:', rowIndex);
        rowIndex++;

    } catch (error) {
        console.error('Error adding row:', error);
        alert('Failed to add new row. Please refresh the page and try again.');
    }
}

window.removeRow = function (btn) {
    const tbody = document.getElementById('itemsBody');
    if (tbody.children.length > 1) {
        btn.closest('tr').remove();
        calculateTotals();
    } else {
        alert('At least one item is required');
    }
}

window.updateProductDetails = function (select, index) {
    const option = select.options[select.selectedIndex];
    const row = select.closest('tr');

    if (option.value) {
        // Use getAttribute for robustness
        const price = option.getAttribute('data-price') || 0;
        const tax = option.getAttribute('data-tax') || 0;
        const name = option.getAttribute('data-name') || '';

        row.querySelector('.item-price').value = price;
        row.querySelector('.item-tax').value = tax;
        row.querySelector('.item-description').value = name;
        calculateRow(select);
    }
}

window.calculateRow = function (element) {
    const row = element.closest('tr');
    if (!row) return; // Safety check

    const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const discountPercent = parseFloat(row.querySelector('.item-discount').value) || 0;
    const taxRate = parseFloat(row.querySelector('.item-tax').value) || 0;

    const subtotal = quantity * price;
    const discountAmount = subtotal * (discountPercent / 100);
    const afterDiscount = subtotal - discountAmount;
    const taxAmount = afterDiscount * (taxRate / 100);
    const total = afterDiscount + taxAmount;

    row.querySelector('.item-total').value = total.toFixed(2);

    calculateTotals();
}

window.calculateTotals = function () {
    let subtotal = 0;
    let totalTax = 0;
    let totalItemDiscount = 0;

    document.querySelectorAll('.item-row').forEach((row, index) => {
        const quantityInput = row.querySelector('.item-quantity');
        const priceInput = row.querySelector('.item-price');
        const discountInput = row.querySelector('.item-discount');
        const taxInput = row.querySelector('.item-tax');

        if (!quantityInput || !priceInput) return;

        const quantity = parseFloat(quantityInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const discountPercent = discountInput ? (parseFloat(discountInput.value) || 0) : 0;
        const taxRate = taxInput ? (parseFloat(taxInput.value) || 0) : 0;

        const lineSubtotal = quantity * price;
        const discountAmount = lineSubtotal * (discountPercent / 100);
        const afterDiscount = lineSubtotal - discountAmount;
        const taxAmount = afterDiscount * (taxRate / 100);

        subtotal += lineSubtotal;
        totalTax += taxAmount;
        totalItemDiscount += discountAmount;
    });

    const additionalDiscountInput = document.getElementById('discountAmount');
    let additionalDiscount = parseFloat(additionalDiscountInput.value) || 0;

    const maxDiscount = subtotal - totalItemDiscount + totalTax;

    if (additionalDiscount > maxDiscount) {
        alert('Discount cannot exceed the total amount.');
        additionalDiscount = maxDiscount;
        additionalDiscountInput.value = additionalDiscount.toFixed(2);
    }

    const shippingChargesInput = document.getElementById('shippingCharges');
    const shippingCharges = shippingChargesInput ? (parseFloat(shippingChargesInput.value) || 0) : 0;

    const totalDiscount = totalItemDiscount + additionalDiscount;
    const grandTotal = subtotal - totalDiscount + totalTax + shippingCharges;

    document.getElementById('subtotalDisplay').textContent = '₹' + subtotal.toFixed(2);
    document.getElementById('taxDisplay').textContent = '₹' + totalTax.toFixed(2);
    document.getElementById('discountDisplay').textContent = '₹' + totalDiscount.toFixed(2);
    document.getElementById('grandTotalDisplay').textContent = '₹' + Math.max(0, grandTotal).toFixed(2);
}
