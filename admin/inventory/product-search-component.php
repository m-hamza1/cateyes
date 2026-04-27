<?php
/**
 * Unified Product Search Component for Inventory Modules
 * Provides consistent product search functionality across all inventory operations
 */

// Get default currency symbol
$defaultCurrency = $db->fetchOne("SELECT symbol, code FROM currencies WHERE is_default = 1 LIMIT 1");
$currency_symbol = $defaultCurrency['symbol'] ?? '$';
$currency_code = $defaultCurrency['code'] ?? 'USD';
?>

<!-- Unified Product Search Modal Template -->
<script>
// Global product search modal functions
function showProductSearchModal(inputElement, options = {}) {
    const defaultOptions = {
        searchUrl: 'search-products.php',
        placeholder: 'Type to search products...',
        minSearchLength: 2,
        searchDelay: 500,
        showPrice: true,
        showStock: false,
        warehouseId: null,
        onSelect: null
    };
    
    const config = Object.assign(defaultOptions, options);
    
    // Optional validation before opening (e.g. require warehouse selection)
    if (typeof config.beforeOpen === 'function' && !config.beforeOpen()) {
        return;
    }
    
    // Ensure input has an ID
    if (!inputElement.id) {
        const row = inputElement.closest('tr');
        if (row) {
            const index = Array.from(row.parentNode.children).indexOf(row);
            inputElement.id = 'product_search_' + index;
        } else {
            inputElement.id = 'product_search_' + Date.now();
        }
    }
    
    // Remove any existing modal
    const existingModal = document.getElementById('productSearchModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Create modal
    const modal = document.createElement('div');
    modal.id = 'productSearchModal';
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Search Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control" id="productSearchInput" placeholder="${config.placeholder}">
                    <div id="productSearchResults" class="mt-3"></div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Store reference to input element for use in selectProductForInput
    const inputId = inputElement.id;
    
    // Focus on search input
    setTimeout(() => {
        document.getElementById('productSearchInput').focus();
    }, 300);
    
    // Search functionality
    let searchTimeout;
    document.getElementById('productSearchInput').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const searchTerm = e.target.value;
        
        if (searchTerm.length < config.minSearchLength) {
            document.getElementById('productSearchResults').innerHTML = '<p class="text-muted">Type at least ' + config.minSearchLength + ' characters to search</p>';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            let url = config.searchUrl + '?search=' + encodeURIComponent(searchTerm);
            const warehouseId = typeof config.getWarehouseId === 'function' ? config.getWarehouseId() : config.warehouseId;
            if (warehouseId) {
                url += '&warehouse_id=' + warehouseId;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    console.log('Search results:', data);
                    let html = '';
                    if (data && data.success === false) {
                        const msg = (data.message || data.error || 'Error loading products');
                        html = '<p class="text-danger">' + msg + '</p>';
                    } else if (data.success && data.products.length > 0) {
                        data.products.forEach(product => {
                            const productName = (product.name || 'N/A').replace(/'/g, "\\'");
                            const productSku = (product.sku || 'N/A').replace(/'/g, "\\'");
                            let priceDisplay = '';
                            let stockDisplay = '';
                            
                            if (config.showPrice) {
                                priceDisplay = ` | Price: ${config.currencySymbol || '<?php echo $currency_symbol; ?>'}${product.price || 0}`;
                            }
                            
                            if (config.showStock && product.stock_quantity !== undefined) {
                                stockDisplay = ` | Stock: ${product.stock_quantity}`;
                            }
                            
                            html += `
                                <div class="border p-2 mb-2" style="cursor: pointer;" onclick="selectProductForInput('${inputId}', ${product.id}, '${productName}', ${product.price || 0}, '${productSku}', ${JSON.stringify(product).replace(/"/g, '&quot;')})">
                                    <strong>${product.name || 'N/A'}</strong><br>
                                    <small>SKU: ${product.sku || 'N/A'}${priceDisplay}${stockDisplay}</small>
                                </div>
                            `;
                        });
                    } else {
                        html = '<p class="text-muted">No products found</p>';
                    }
                    document.getElementById('productSearchResults').innerHTML = html;
                })
                .catch(error => {
                    console.error('Search error:', error);
                    document.getElementById('productSearchResults').innerHTML = '<p class="text-danger">Error loading products</p>';
                });
        }, config.searchDelay);
    });
    
    // Clean up modal when hidden
    modal.addEventListener('hidden.bs.modal', function() {
        modal.remove();
    });
}

// Global function to select product
window.selectProductForInput = function(inputId, productId, productName, price, productSku, fullProductData) {
    const input = document.getElementById(inputId);
    if (!input) {
        console.error('Input element not found:', inputId);
        return;
    }
    
    const row = input.closest('tr');
    if (!row) {
        console.error('Row not found for input:', inputId);
        return;
    }
    
    // Update product ID hidden field
    const productIdInput = row.querySelector('input[name*="[product_id]"]');
    if (productIdInput) {
        productIdInput.value = productId;
    }
    
    // Update product name hidden field
    const productNameInput = row.querySelector('input[name*="[product_name]"]');
    if (productNameInput) {
        productNameInput.value = productName;
    }
    
    // Update product SKU hidden field if exists
    const productSkuInput = row.querySelector('input[name*="[product_sku]"]');
    if (productSkuInput) {
        productSkuInput.value = productSku || '';
    }
    
    // Update variant ID if exists in product data
    const variantIdInput = row.querySelector('input[name*="[variant_id]"]');
    if (variantIdInput && fullProductData && fullProductData.variant_id) {
        variantIdInput.value = fullProductData.variant_id;
    }
    
    // Update visible input with product name (and SKU if available)
    let displayValue = productName;
    if (productSku) {
        displayValue += ' (' + productSku + ')';
    }
    input.value = displayValue;
    input.setAttribute('readonly', 'readonly');
    
    // Update unit price if empty and price is provided
    const unitPriceInput = row.querySelector('input[name*="[unit_price]"]');
    if (unitPriceInput && price && (unitPriceInput.value === '' || parseFloat(unitPriceInput.value) === 0)) {
        unitPriceInput.value = price;
    }
    
    // Update current stock if field exists and stock data is available
    const currentStockInput = row.querySelector('input[name*="[current_stock]"]');
    if (currentStockInput && fullProductData && fullProductData.stock_quantity !== undefined) {
        currentStockInput.value = fullProductData.stock_quantity;
    }
    // Also update .available-stock (used by stock transfers)
    const availableStockInput = row.querySelector('.available-stock');
    if (availableStockInput && fullProductData && fullProductData.stock_quantity !== undefined) {
        availableStockInput.value = fullProductData.stock_quantity;
    }
    
    // Trigger custom callback if provided
    if (typeof window.onProductSelect === 'function') {
        window.onProductSelect(inputId, productId, fullProductData);
    }
    
    // Recalculate totals if function exists
    if (typeof calculateTotals === 'function') {
        calculateTotals();
    }
    
    // Close modal
    const modal = document.getElementById('productSearchModal');
    if (modal) {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
    }
};

// Function to add product search listeners to all elements with .product-search class
function initializeProductSearch(options = {}) {
    document.querySelectorAll('.product-search').forEach(function(input) {
        if (!input.hasAttribute('data-listener-attached')) {
            input.addEventListener('click', function() {
                showProductSearchModal(this, options);
            });
            input.setAttribute('data-listener-attached', 'true');
        }
    });
}

// Function to add a new item row with product search
function addProductItemRow(tableBodyId, options = {}) {
    const tbody = document.getElementById(tableBodyId);
    if (!tbody) {
        console.error('Table body not found:', tableBodyId);
        return;
    }
    
    const rowCount = tbody.children.length;
    const newRow = document.createElement('tr');
    
    // Default row template - can be customized via options
    const defaultRowTemplate = `
        <td>
            <input type="hidden" name="items[${rowCount}][product_id]" value="">
            <input type="hidden" name="items[${rowCount}][product_name]" value="">
            <input type="text" class="form-control product-search" id="product_search_${rowCount}" placeholder="Search Product" readonly>
        </td>
        <td>
            <input type="number" class="form-control" name="items[${rowCount}][quantity]" value="1" min="1" required onchange="if(typeof calculateTotals === 'function') calculateTotals()">
        </td>
        <td>
            <input type="number" class="form-control" name="items[${rowCount}][unit_price]" step="0.01" min="0" required onchange="if(typeof calculateTotals === 'function') calculateTotals()">
        </td>
        <td>
            <input type="text" class="form-control item-total" value="${options.currencySymbol || '<?php echo $currency_symbol; ?>'}0.00" readonly>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="if(typeof removeItem === 'function') removeItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    let template = options.rowTemplate || defaultRowTemplate;
    // Replace ${rowCount} placeholder with actual count
    template = template.replace(/\$\{rowCount\}/g, rowCount);
    
    newRow.innerHTML = template;
    tbody.appendChild(newRow);
    
    // Add click event listener to the new product search field
    const newProductSearch = newRow.querySelector('.product-search');
    if (newProductSearch) {
        newProductSearch.addEventListener('click', function() {
            showProductSearchModal(this, options);
        });
    }
    
    return newRow;
}

// Function to remove an item row
function removeProductItemRow(button) {
    const row = button.closest('tr');
    const tbody = row.parentNode;
    if (tbody.children.length > 1) {
        row.remove();
        if (typeof calculateTotals === 'function') {
            calculateTotals();
        }
    }
}

// NOTE: Auto-init intentionally removed.
// Each page MUST call initializeProductSearch() in its own DOMContentLoaded.
// See stock-transfers/add.php, supplier-returns/add.php, sales-returns/add.php

</script>

<?php
/**
 * Helper function to generate product search input HTML
 */
function renderProductSearchInput($index, $value = '', $options = []) {
    $defaults = [
        'show_sku' => false,
        'placeholder' => 'Search Product',
        'readonly' => true
    ];
    
    $opts = array_merge($defaults, $options);
    
    $html = '<input type="hidden" name="items[' . $index . '][product_id]" value="">';
    $html .= '<input type="hidden" name="items[' . $index . '][product_name]" value="">';
    
    if ($opts['show_sku']) {
        $html .= '<input type="hidden" name="items[' . $index . '][product_sku]" value="">';
    }
    
    $html .= '<input type="text" class="form-control product-search" id="product_search_' . $index . '" ';
    $html .= 'placeholder="' . htmlspecialchars($opts['placeholder']) . '" ';
    $html .= 'value="' . htmlspecialchars($value) . '" ';
    if ($opts['readonly']) {
        $html .= 'readonly ';
    }
    $html .= '>';
    
    return $html;
}
?>
