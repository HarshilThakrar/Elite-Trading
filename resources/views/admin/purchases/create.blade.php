@extends('layouts.app')

@section('title', 'Create Purchase Order - Demo ERP')
@section('header_title', 'Create Purchase Order')

@section('content')
<div class="card card-custom">
    <div class="card-body">

        <form action="{{ route('purchases.store') }}" method="POST" id="poForm">
            @csrf
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">PO Number <span class="text-danger">*</span></label>
                    <input type="text" name="po_number" class="form-control" value="{{ $po_number }}" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">PO Date <span class="text-danger">*</span></label>
                    <input type="date" name="po_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Vendor <span class="text-danger">*</span></label>
                    <select name="vendor_id" class="form-select" required>
                        <option value="">Select Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->company_name }} ({{ $vendor->vendor_code }})</option>
                        @endforeach
                    </select>
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
                                <select name="items[0][product_id]" class="form-select product-select" required>
                                    <option value="">Select Product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->lp_price }}">{{ $product->part_code }} - {{ $product->item_name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" step="0.01" name="items[0][quantity]" class="form-control qty-input" value="1" required min="0.01"></td>
                            <td><input type="number" step="0.01" name="items[0][unit_price]" class="form-control price-input" required min="0"></td>
                            <td><input type="text" class="form-control row-total" readonly></td>
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
                <button type="button" class="btn btn-sm btn-secondary" id="addRow"><i class="bi bi-plus"></i> Add Another Item</button>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Any special instructions..."></textarea>
            </div>

            <div class="d-flex justify-content-end">
                <a href="{{ route('purchases.index') }}" class="btn btn-light me-2">Cancel</a>
                <button type="submit" class="btn btn-primary-custom"><i class="bi bi-check-lg"></i> Create Purchase Order</button>
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
                        <select name="items[${rowIdx}][product_id]" class="form-select product-select" required>
                            <option value="">Select Product</option>
                            ${productsHtml}
                        </select>
                    </td>
                    <td><input type="number" step="0.01" name="items[${rowIdx}][quantity]" class="form-control qty-input" value="1" required min="0.01"></td>
                    <td><input type="number" step="0.01" name="items[${rowIdx}][unit_price]" class="form-control price-input" required min="0"></td>
                    <td><input type="text" class="form-control row-total" readonly></td>
                    <td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="bi bi-trash"></i></button></td>
                </tr>`;
            $('#itemsBody').append(newRow);
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
            let selectedPrice = $(this).find(':selected').data('price');
            if(selectedPrice !== undefined) {
                $(this).closest('tr').find('.price-input').val(selectedPrice);
                calculateTotals();
            }
        });
        
        // Initial Calculation
        calculateTotals();

        // Escape key to redirect back to purchases list
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                window.location.href = "{{ route('purchases.index') }}";
            }
        });
    });
</script>
@endpush

