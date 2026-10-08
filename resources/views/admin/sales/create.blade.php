@extends('layouts.app')

@section('title', 'Create Sales Order - Demo ERP')
@section('header_title', 'Create Sales Order')

@section('content')
<div class="card card-custom">
    <div class="card-body">

        <form action="{{ route('sales.store') }}" method="POST" id="saleForm">
            @csrf
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                    <div class="d-flex gap-2">
                        <div class="flex-grow-1">
                            <select name="customer_id" class="form-select select2" required>
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->company_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <a href="{{ route('customers.create') }}" target="_blank" class="btn btn-outline-primary" type="button" title="Add New Customer">
                            <i class="bi bi-plus-lg"></i>
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Sales Order Number <span class="text-danger">*</span></label>
                    <input type="text" name="invoice_number" class="form-control" value="{{ $invoice_number }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                    <input type="date" name="sale_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">E-way Bill No.</label>
                    <input type="text" name="eway_bill_no" class="form-control" value="{{ old('eway_bill_no') }}" placeholder="Enter E-way Bill Number">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">E-way Bill Date</label>
                    <input type="date" name="eway_bill_date" class="form-control" value="{{ old('eway_bill_date') }}">
                </div>
            </div>

            <h5 class="mb-3 border-bottom pb-2">Additional Details (Invoice PDF)</h5>
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Buyer's Order No.</label>
                    <input type="text" name="buyer_order_no" class="form-control" value="{{ old('buyer_order_no') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Buyer's Order Date</label>
                    <input type="date" name="buyer_order_date" class="form-control" value="{{ old('buyer_order_date') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Dispatched through</label>
                    <input type="text" name="dispatched_through" class="form-control" value="{{ old('dispatched_through') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Destination</label>
                    <input type="text" name="destination" class="form-control" value="{{ old('destination') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Delivery Note</label>
                    <input type="text" name="delivery_note" class="form-control" value="{{ old('delivery_note') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Mode/Terms of Payment</label>
                    <input type="text" name="mode_of_payment" class="form-control" value="{{ old('mode_of_payment', '30 Days') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Reference No. & Date</label>
                    <input type="text" name="reference_no_date" class="form-control" value="{{ old('reference_no_date') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Other References</label>
                    <input type="text" name="other_references" class="form-control" value="{{ old('other_references') }}">
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">Terms of Delivery</label>
                    <textarea name="terms_of_delivery" class="form-control" rows="2">{{ old('terms_of_delivery') }}</textarea>
                </div>
            </div>

            <h5 class="mb-3 border-bottom pb-2">Line Items</h5>
            <div class="table-responsive mb-3">
                <table class="table table-bordered" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th width="40%">Product</th>
                            <th width="20%">Quantity</th>
                            <th width="20%">Unit Price (₹)</th>
                            <th width="15%">Total (₹)</th>
                            <th width="5%">Action</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr>
                            <td>
                                <select name="items[0][product_id]" class="form-select form-select-sm product-select select2" required>
                                    <option value="">Select Product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->lp_price }}">{{ $product->part_code }} - {{ $product->item_name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" step="0.01" name="items[0][quantity]" class="form-control form-control-sm qty-input" value="1" required min="0.01"></td>
                            <td><input type="number" step="0.01" name="items[0][unit_price]" class="form-control form-control-sm price-input" required min="0"></td>
                            <td><input type="text" class="form-control form-control-sm row-total" readonly></td>
                            <td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Grand Total:</th>
                            <th class="fs-5 fw-bold text-primary-custom" id="grandTotal">₹ 0.00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addRow"><i class="bi bi-plus"></i> Add Another Item</button>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Any special instructions..."></textarea>
            </div>

            <div class="d-flex justify-content-end">
                <a href="{{ route('sales.index') }}" class="btn btn-light me-2">Cancel</a>
                <button type="submit" class="btn btn-primary-custom"><i class="bi bi-check-lg"></i> Create Sales Order</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        let rowIdx = 1;
        const productsHtml = `@foreach($products as $product)<option value="{{ $product->id }}" data-price="{{ $product->lp_price }}">{{ $product->part_code }} - {{ $product->item_name }}</option>@endforeach`;

        function calculateTotals() {
            let grandTotal = 0;
            $('#itemsBody tr').each(function() {
                let qty = parseFloat($(this).find('.qty-input').val()) || 0;
                let price = parseFloat($(this).find('.price-input').val()) || 0;
                let rowTotal = qty * price;
                $(this).find('.row-total').val(rowTotal.toFixed(2));
                grandTotal += rowTotal;
            });
            $('#grandTotal').text('₹ ' + grandTotal.toFixed(2));
        }

        $('#addRow').click(function() {
            let newRow = `
                <tr>
                    <td>
                        <select name="items[${rowIdx}][product_id]" class="form-select form-select-sm product-select select2" required>
                            <option value="">Select Product</option>
                            ${productsHtml}
                        </select>
                    </td>
                    <td><input type="number" step="0.01" name="items[${rowIdx}][quantity]" class="form-control form-control-sm qty-input" value="1" required min="0.01"></td>
                    <td><input type="number" step="0.01" name="items[${rowIdx}][unit_price]" class="form-control form-control-sm price-input" required min="0"></td>
                    <td><input type="text" class="form-control form-control-sm row-total" readonly></td>
                    <td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="bi bi-trash"></i></button></td>
                </tr>`;
            $('#itemsBody').append(newRow);
            
            if ($('.select2').length) {
                let newSelect = $('#itemsBody tr:last-child .select2');
                newSelect.select2({ theme: 'bootstrap-5' });
                newSelect.select2('open');
            }
            
            rowIdx++;
        });

        $(document).on('click', '.remove-row', function() {
            if ($('#itemsBody tr').length > 1) {
                $(this).closest('tr').remove();
                calculateTotals();
            } else {
                alert("You must have at least one item.");
            }
        });

        $(document).on('input', '.qty-input, .price-input', function() {
            calculateTotals();
        });

        $(document).on('change', '.product-select', function() {
            let currentSelect = $(this);
            let selectedProductId = currentSelect.val();
            
            if (selectedProductId) {
                let isDuplicate = false;
                $('.product-select').not(currentSelect).each(function() {
                    if ($(this).val() === selectedProductId) {
                        isDuplicate = true;
                        return false; // break the loop
                    }
                });
                
                if (isDuplicate) {
                    alert('This item is already added to the list!');
                    currentSelect.val('').trigger('change.select2'); // Reset the selection
                    
                    // Reopen the dropdown after a short delay (overriding the auto-advance)
                    setTimeout(function() {
                        currentSelect.select2('open');
                    }, 100);
                    
                    return;
                }
            }
            
            let selectedPrice = currentSelect.find(':selected').data('price');
            if(selectedPrice !== undefined) {
                currentSelect.closest('tr').find('.price-input').val(selectedPrice);
                calculateTotals();
            }
        });
        
        // Initial Calculation
        calculateTotals();
        
        // Open Customer select2 dropdown automatically on page load
        setTimeout(function() {
            $('select[name="customer_id"]').select2('open');
        }, 100);

        // Escape key to redirect back to sales list
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                window.location.href = "{{ route('sales.index') }}";
            }
        });
        // Enter key navigation for standard inputs
        $(document).on('keydown', 'input, select, textarea, .select2-selection', function(e) {
            if (e.key === 'Enter' && !$(this).is(':submit')) {
                // Allow Shift+Enter for new line in textarea
                if ($(this).is('textarea') && e.shiftKey) {
                    return;
                }
                
                e.preventDefault();
                
                // If this is a select2 selection, we need to find the actual select element
                var currentElement = this;
                if ($(this).hasClass('select2-selection')) {
                    currentElement = $(this).closest('.select2-container').prev('select')[0];
                }
                
                var focusables = $('#saleForm').find('input, select, textarea, button[type="submit"], button#addRow').filter(':visible:not([readonly]):not([disabled])');
                var index = focusables.index(currentElement);
                if (index > -1 && index < focusables.length - 1) {
                    var nextElement = focusables.eq(index + 1);
                    
                    // Scroll next element into view
                    if(nextElement.length) {
                        nextElement[0].scrollIntoView({ behavior: 'auto', block: 'center' });
                    }
                    
                    if (nextElement.hasClass('select2-hidden-accessible')) {
                        setTimeout(function() { nextElement.select2('open'); }, 10);
                    } else {
                        setTimeout(function() {
                            nextElement.focus();
                            if (nextElement.is('input')) {
                                nextElement.select();
                            }
                        }, 10);
                    }
                }
            }
        });

        // When a select2 value is chosen, automatically jump to next field
        $(document).on('select2:select', function (e) {
            var currentElement = e.target;
            var focusables = $('#saleForm').find('input, select, textarea, button[type="submit"], button#addRow').filter(':visible:not([readonly]):not([disabled])');
            var index = focusables.index(currentElement);
            if (index > -1 && index < focusables.length - 1) {
                var nextElement = focusables.eq(index + 1);
                
                // Scroll next element into view
                if(nextElement.length) {
                    nextElement[0].scrollIntoView({ behavior: 'auto', block: 'center' });
                }
                
                setTimeout(function() {
                    if (nextElement.hasClass('select2-hidden-accessible')) {
                        nextElement.select2('open');
                    } else {
                        nextElement.focus();
                        if (nextElement.is('input')) {
                            nextElement.select();
                        }
                    }
                }, 10);
            }
        });
    });
</script>
@endpush

