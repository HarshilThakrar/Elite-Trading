// Quotation AJAX Calculation Logic

document.addEventListener('DOMContentLoaded', function() {
    
    // Attach event listeners to table body (assuming table has id 'quotation-items-table')
    const tableBody = document.querySelector('#quotation-items-table tbody');
    
    // Global stack for undone rows
    window.deletedRowsStack = window.deletedRowsStack || [];
    
    // Handle row removal
    const quotationTable = document.querySelector('#quotation-items-table');
    if (quotationTable) {
        quotationTable.addEventListener('click', function(e) {
            if (e.target.closest('.remove-row')) {
                const row = e.target.closest('tr');
                const tbody = row.parentElement;
                const prevSibling = row.previousElementSibling;
                const allRows = tbody.querySelectorAll('tr');
                
                if (allRows.length > 1) {
                    // Destroy select2 to prevent detached node issues
                    const selects = $(row).find('.select2');
                    if (selects.length) {
                        try {
                            selects.select2('destroy');
                        } catch(err) {
                            console.warn("Select2 destroy failed", err);
                        }
                    }
                    
                    // Save and detach
                    row.remove();
                    window.deletedRowsStack.push({
                        row: row,
                        prevSibling: prevSibling,
                        parent: tbody
                    });
                    
                    const freightInput = document.querySelector('#freight_charges');
                    if (freightInput) freightInput.dispatchEvent(new Event('input'));
                } else {
                    alert('At least one item is required.');
                }
            }
        });
    }
    
    // Ctrl+Z Undo for deleted rows
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'z') {
            if (window.deletedRowsStack && window.deletedRowsStack.length > 0) {
                // Prevent default undo behavior in inputs unless they're focused and typing, 
                // but actually we want to undo row deletion if possible.
                // It's better to just let native undo happen AND do our undo if focus isn't in an input,
                // or just always do it. Let's do it always and preventDefault to avoid conflicts,
                // UNLESS user is typing in an input.
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                    // If user is inside an input, native undo is probably preferred.
                    return;
                }
                
                e.preventDefault();
                const lastDeleted = window.deletedRowsStack.pop();
                
                if (lastDeleted.prevSibling && lastDeleted.prevSibling.parentElement === lastDeleted.parent) {
                    lastDeleted.prevSibling.after(lastDeleted.row);
                } else {
                    lastDeleted.parent.prepend(lastDeleted.row);
                }
                
                // Re-initialize select2
                $(lastDeleted.row).find('select[name^="items"][name$="[product_id]"]').select2({ theme: 'bootstrap-5', width: '100%' });
                
                // Trigger recalculation
                const freightInput = document.querySelector('#freight_charges');
                if (freightInput) freightInput.dispatchEvent(new Event('input'));
                
                // Blink row green
                lastDeleted.row.style.transition = "background-color 0.5s";
                lastDeleted.row.style.backgroundColor = "#d1e7dd";
                setTimeout(() => {
                    lastDeleted.row.style.backgroundColor = "";
                }, 500);
            }
        }
    });
    
    if (tableBody) {
        // Trigger change for existing rows (like in edit view)
        const selects = tableBody.querySelectorAll('select[name^="items"][name$="[product_id]"]');
        selects.forEach(select => {
            if (select.value) {
                $(select).trigger('change');
            }
        });

        tableBody.addEventListener('input', function(e) {
            if (e.target.matches('.calc-trigger')) {
                const row = e.target.closest('tr');
                calculateRow(row);
                calculateGrandTotal();
            }
        });



        const skipToFreightBtn = document.getElementById('skip-to-freight');
        if (skipToFreightBtn) {
            skipToFreightBtn.addEventListener('click', function() {
                const freightInput = document.getElementById('freight_charges');
                if (freightInput) {
                    freightInput.focus();
                    freightInput.select();
                    freightInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        }

        $(tableBody).on('change', 'select[name^="items"][name$="[product_id]"]', function() {
            const row = this.closest('tr');
            const selectedOption = this.options[this.selectedIndex];
            const productId = this.value;
            
            // Check for duplicate product
            if (productId) {
                let duplicateFound = false;
                const allSelects = tableBody.querySelectorAll('select[name^="items"][name$="[product_id]"]');
                allSelects.forEach(select => {
                    if (select !== this && select.value === productId) {
                        duplicateFound = true;
                    }
                });
                
                if (duplicateFound) {
                    alert('This item is already added.');
                    
                    // Blink effect
                    row.style.transition = "background-color 0.2s";
                    let blinkCount = 0;
                    let blinkInterval = setInterval(() => {
                        row.style.backgroundColor = (blinkCount % 2 === 0) ? "#f8d7da" : "";
                        blinkCount++;
                        if (blinkCount > 5) {
                            clearInterval(blinkInterval);
                            row.style.backgroundColor = "";
                        }
                    }, 200);

                    $(this).val('').trigger('change.select2');
                    setTimeout(() => $(this).select2('open'), 100);
                    return;
                }
            }

            if(selectedOption && selectedOption.dataset.lp !== undefined) {
                const lpInput = row.querySelector('.list_price');
                const purchaseDiscountInput = row.querySelector('.purchase_discount');
                
                let lp = parseFloat(selectedOption.dataset.lp) || 0;
                
                if(lpInput) {
                    lpInput.value = lp;
                    lpInput.dispatchEvent(new Event('input', { bubbles: true }));
                }

                if (productId && purchaseDiscountInput) {
                    fetch(`/products/${productId}/average-purchase-rate`)
                        .then(response => response.json())
                        .then(data => {
                            let avgRate = parseFloat(data.average_purchase_rate) || 0;
                            if (avgRate > 0 && lp > 0) {
                                let discount = ((lp - avgRate) / lp) * 100;
                                purchaseDiscountInput.value = Math.round(discount);
                            } else {
                                purchaseDiscountInput.value = 0;
                            }
                            purchaseDiscountInput.dispatchEvent(new Event('input', { bubbles: true }));
                        })
                        .catch(error => console.error('Error fetching average purchase rate:', error));
                }
            }
            
            // Update stock indicator
            const stockIndicator = row.querySelector('.stock-badge');
            if (stockIndicator && selectedOption) {
                const stockVal = selectedOption.dataset.stock;
                if (stockVal !== undefined && stockVal !== '') {
                    const stock = parseFloat(stockVal);
                    let colorClass = 'bg-danger text-white'; // < 10
                    if (stock >= 60) {
                        colorClass = 'bg-success text-white';
                    } else if (stock >= 10) {
                        colorClass = 'bg-warning text-white';
                    }
                    stockIndicator.className = `badge rounded-pill stock-badge ${colorClass}`;
                    stockIndicator.textContent = stock;
                } else {
                    stockIndicator.className = 'badge bg-secondary rounded-pill stock-badge text-white';
                    stockIndicator.textContent = '--';
                }
            }

            // Auto-fill HSN code
            const hsnInput = row.querySelector('.hsn_code');
            if (hsnInput && selectedOption) {
                hsnInput.value = selectedOption.dataset.hsn || '';
            }

            // Auto-fill GST rate
            const gstRateInput = row.querySelector('.gst_rate_input');
            const cgstRateDisplay = row.querySelector('.cgst_rate_display');
            const sgstRateDisplay = row.querySelector('.sgst_rate_display');
            if (gstRateInput && selectedOption) {
                const gstRate = parseFloat(selectedOption.dataset.gst) || 0;
                gstRateInput.value = gstRate;
                if (cgstRateDisplay) cgstRateDisplay.textContent = (gstRate / 2) + '%';
                if (sgstRateDisplay) sgstRateDisplay.textContent = (gstRate / 2) + '%';
                // Trigger GST recalculation
                calculateRow(row);
                calculateGrandTotal();
            }
        });

        tableBody.addEventListener('click', function(e) {
            const historyBtn = e.target.closest('.view-history');
            if (historyBtn) {
                const row = historyBtn.closest('tr');
                const selectElement = row.querySelector('select[name^="items"][name$="[product_id]"]');
                const productId = selectElement ? selectElement.value : null;

                if (!productId) {
                    alert('Please select a product first to view its history.');
                    return;
                }

                historyBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                historyBtn.disabled = true;

                fetch(`/products/${productId}/purchase-history`)
                    .then(response => response.json())
                    .then(data => {
                        historyBtn.innerHTML = '<i class="bi bi-clock-history"></i>';
                        historyBtn.disabled = false;

                        const tbody = document.querySelector('#history-table tbody');
                        if (tbody) {
                            tbody.innerHTML = '';
                            
                            if (data.length === 0) {
                                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No approved purchase history found.</td></tr>';
                            } else {
                                data.forEach(item => {
                                    const tr = document.createElement('tr');
                                    tr.innerHTML = `
                                        <td>${item.po_number}</td>
                                        <td>${item.po_date}</td>
                                        <td>${item.vendor_name}</td>
                                        <td>${item.quantity}</td>
                                        <td class="fw-bold">₹${parseFloat(item.net_rate).toFixed(2)}</td>
                                    `;
                                    tbody.appendChild(tr);
                                });
                            }
                            
                            const historyModal = new bootstrap.Modal(document.getElementById('purchaseHistoryModal'));
                            historyModal.show();
                        }
                    })
                    .catch(err => {
                        console.error('Error fetching history:', err);
                        historyBtn.innerHTML = '<i class="bi bi-clock-history"></i>';
                        historyBtn.disabled = false;
                        alert('Failed to load purchase history.');
                    });
            }
        });
    }

    const freightInput = document.querySelector('#freight_charges');
    if (freightInput) {
        freightInput.addEventListener('input', calculateGrandTotal);
    }

    function calculateRow(row) {
        const lpInput = row.querySelector('.list_price');
        const purchaseDiscountInput = row.querySelector('.purchase_discount');
        const customerDiscountInput = row.querySelector('.customer_discount');
        const qtyInput = row.querySelector('.quantity');
        const gstRateInput = row.querySelector('.gst_rate_input');
        
        const purchaseRateDisplay = row.querySelector('.purchase_rate_display');
        const customerRateDisplay = row.querySelector('.customer_rate_display');
        const profitRsDisplay = row.querySelector('.profit_rs_display');
        const profitPctDisplay = row.querySelector('.profit_pct_display');
        const lineTotalDisplay = row.querySelector('.line_total_display');
        const lineTotalInput = row.querySelector('.line_total_input');
        const cgstAmountDisplay = row.querySelector('.cgst_amount_display');
        const sgstAmountDisplay = row.querySelector('.sgst_amount_display');
        const gstAmountInput = row.querySelector('.gst_amount_input');
        const lineTotalWithGstInput = row.querySelector('.line_total_with_gst_input');

        let lp = parseFloat(lpInput ? lpInput.value : 0) || 0;
        let purchaseDiscount = parseFloat(purchaseDiscountInput ? purchaseDiscountInput.value : 0) || 0;
        let customerDiscount = parseFloat(customerDiscountInput ? customerDiscountInput.value : 0) || 0;
        let qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
        let gstRate = parseFloat(gstRateInput ? gstRateInput.value : 0) || 0;

        let purchaseRate = lp * (100 - purchaseDiscount) / 100;
        let customerRate = lp * (100 - customerDiscount) / 100;
        
        let profitRs = customerRate - purchaseRate;
        let profitPct = purchaseRate > 0 ? ((customerRate - purchaseRate) / purchaseRate) * 100 : 0;
        let lineTotal = customerRate * qty;
        let gstAmount = lineTotal * gstRate / 100;
        let lineTotalWithGst = lineTotal + gstAmount;

        if (purchaseRateDisplay) purchaseRateDisplay.textContent = purchaseRate.toFixed(2);
        if (customerRateDisplay) customerRateDisplay.textContent = customerRate.toFixed(2);
        if (profitRsDisplay) profitRsDisplay.textContent = profitRs.toFixed(2);
        if (profitPctDisplay) profitPctDisplay.textContent = profitPct.toFixed(2) + '%';
        if (lineTotalDisplay) lineTotalDisplay.textContent = lineTotal.toFixed(2);
        if (lineTotalInput) lineTotalInput.value = lineTotal.toFixed(2);
        if (cgstAmountDisplay) cgstAmountDisplay.textContent = (gstAmount / 2).toFixed(2);
        if (sgstAmountDisplay) sgstAmountDisplay.textContent = (gstAmount / 2).toFixed(2);
        if (gstAmountInput) gstAmountInput.value = gstAmount.toFixed(2);
        if (lineTotalWithGstInput) lineTotalWithGstInput.value = lineTotalWithGst.toFixed(2);
    }

    function calculateGrandTotal() {
        let subtotal = 0;
        let totalNetRate = 0;
        let totalProfitPctSum = 0;
        let itemCount = 0;
        let totalGstAmount = 0;
        
        const rows = document.querySelectorAll('#quotation-items-table tbody tr');
        rows.forEach(row => {
            const lpInput = row.querySelector('.list_price');
            const purchaseDiscountInput = row.querySelector('.purchase_discount');
            const customerDiscountInput = row.querySelector('.customer_discount');
            const qtyInput = row.querySelector('.quantity');
            const lineTotalInput = row.querySelector('.line_total_input');
            const gstAmountInput = row.querySelector('.gst_amount_input');
            
            let lp = parseFloat(lpInput ? lpInput.value : 0) || 0;
            let purchaseDiscount = parseFloat(purchaseDiscountInput ? purchaseDiscountInput.value : 0) || 0;
            let customerDiscount = parseFloat(customerDiscountInput ? customerDiscountInput.value : 0) || 0;
            let lineTotal = parseFloat(lineTotalInput ? lineTotalInput.value : 0) || 0;
            let gstAmount = parseFloat(gstAmountInput ? gstAmountInput.value : 0) || 0;
            
            let purchaseRate = lp * (100 - purchaseDiscount) / 100;
            let customerRate = lp * (100 - customerDiscount) / 100;
            
            subtotal += lineTotal;
            totalGstAmount += gstAmount;
            if (lp > 0) {
                totalNetRate += customerRate;
                let profitPct = purchaseRate > 0 ? ((customerRate - purchaseRate) / purchaseRate) * 100 : 0;
                totalProfitPctSum += profitPct;
                itemCount++;
            }
        });

        const freightInput = document.querySelector('#freight_charges');
        let freight = parseFloat(freightInput ? freightInput.value : 0) || 0;

        let grandTotal = subtotal + freight;
        
        let avgProfitPct = itemCount > 0 ? (totalProfitPctSum / itemCount) : 0;

        // Update Subtotal and Grand Total DOM
        const subtotalDisplay = document.querySelector('#subtotal_display');
        const subtotalInput = document.querySelector('#subtotal_input');
        if (subtotalDisplay) subtotalDisplay.textContent = subtotal.toFixed(2);
        if (subtotalInput) subtotalInput.value = subtotal.toFixed(2);

        const grandTotalDisplay = document.querySelector('#grand_total_display');
        const grandTotalInput = document.querySelector('#grand_total_input');
        if (grandTotalDisplay) grandTotalDisplay.textContent = grandTotal.toFixed(2);
        if (grandTotalInput) grandTotalInput.value = grandTotal.toFixed(2);

        // Update GST total
        const gstTotalDisplay = document.querySelector('#gst_amount_display');
        const gstTotalInput = document.querySelector('#gst_amount_input');
        if (gstTotalDisplay) gstTotalDisplay.textContent = totalGstAmount.toFixed(2);
        if (gstTotalInput) gstTotalInput.value = totalGstAmount.toFixed(2);

        // Update Grand Total with GST
        let grandTotalWithGst = grandTotal + totalGstAmount;
        const grandTotalWithGstDisplay = document.querySelector('#grand_total_with_gst_display');
        const grandTotalWithGstInput = document.querySelector('#grand_total_with_gst_input');
        if (grandTotalWithGstDisplay) grandTotalWithGstDisplay.textContent = grandTotalWithGst.toFixed(2);
        if (grandTotalWithGstInput) grandTotalWithGstInput.value = grandTotalWithGst.toFixed(2);
        
        // Update Net Rate & Profit DOM
        const totalNetRateDisplay = document.querySelector('#total_purchase_display'); // Using same ID for simplicity
        if (totalNetRateDisplay) totalNetRateDisplay.textContent = totalNetRate.toFixed(2);
        
        const avgProfitPctDisplay = document.querySelector('#avg_profit_pct_display');
        if (avgProfitPctDisplay) avgProfitPctDisplay.textContent = avgProfitPct.toFixed(2) + '%';
    }

    // Allow Enter key to act like Tab for faster data entry
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') {
                if (e.target.type === 'submit' || e.target.type === 'button') return;
                
                e.preventDefault();
                
                const form = e.target.closest('form');
                if (!form) return;
                
                // Find visible, focusable elements + select2 hidden selects
                const focusableElements = Array.from(form.querySelectorAll('input:not([type="hidden"]):not(:disabled):not(.d-none input), select:not(:disabled):not(.d-none select), button:not(:disabled):not(.remove-row):not(.view-history)'))
                    .filter(el => el.offsetParent !== null || el.classList.contains('select2-hidden-accessible')); 
                
                const index = focusableElements.indexOf(e.target);
                if (index > -1 && index < focusableElements.length - 1) {
                    let nextEl = focusableElements[index + 1];
                    
                    if (nextEl.id === 'add-item-row') {
                        const currentRow = e.target.closest('tr');
                        if (currentRow) {
                            const productSelect = currentRow.querySelector('select[name^="items"][name$="[product_id]"]');
                            if (productSelect && !productSelect.value) {
                                $(productSelect).select2('open');
                                return;
                            }
                        }
                    }

                    if ($(nextEl).hasClass('select2-hidden-accessible')) {
                        $(nextEl).next('.select2-container').find('.select2-selection').focus();
                    } else {
                        nextEl.focus();
                        if (['text', 'number'].includes(nextEl.type)) {
                            nextEl.select();
                        }
                    }
                }
            }
        }
    });

    // Advance focus when an option is selected in Select2
    $(document).on('select2:select', function(e) {
        const select = e.target;
        const form = select.closest('form');
        if (!form) return;
        
        const focusableElements = Array.from(form.querySelectorAll('input:not([type="hidden"]):not(:disabled):not(.d-none input), select:not(:disabled):not(.d-none select), button:not(:disabled):not(.remove-row):not(.view-history)'))
            .filter(el => el.offsetParent !== null || el.classList.contains('select2-hidden-accessible')); 
            
        const index = focusableElements.indexOf(select);
        if (index > -1 && index < focusableElements.length - 1) {
            let nextEl = focusableElements[index + 1];
            if ($(nextEl).hasClass('select2-hidden-accessible')) {
                // Focus next select2
                setTimeout(() => $(nextEl).select2('open'), 50);
            } else {
                // Focus next input
                setTimeout(() => {
                    nextEl.focus();
                    if (['text', 'number'].includes(nextEl.type)) {
                        nextEl.select();
                    }
                }, 50);
            }
        }
    });
});
