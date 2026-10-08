@extends('layouts.app')

@section('title', 'Create Credit Note')
@section('header_title', 'Create Credit Note')

@section('content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Credit Note against Sales Voucher: {{ $salesVoucher->voucher_number }}</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('credit-notes.store', $salesVoucher->id) }}" id="cnForm">
                @csrf
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Customer</label>
                        <input type="text" class="form-control bg-light" value="{{ $customer->company_name ?? 'Unknown Customer' }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Credit Note Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reason for Return</label>
                        <input type="text" name="reason" class="form-control" value="{{ old('reason') }}" placeholder="e.g. Damaged goods, Quality rejected">
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-hover" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Invoiced Qty</th>
                                <th>Previously Returned</th>
                                <th>Returnable Qty</th>
                                <th>Return Qty <span class="text-danger">*</span></th>
                                <th>Original Rate</th>
                                <th>GST %</th>
                                <th>Taxable Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($itemsWithReturnable as $index => $item)
                            <tr>
                                <td>
                                    {{ $item->product->item_name }}
                                    <input type="hidden" name="items[{{ $index }}][sale_item_id]" value="{{ $item->id }}">
                                    <input type="hidden" class="item-gst-rate" value="{{ $item->product->gst_rate ?? 0 }}">
                                </td>
                                <td>{{ (float)($item->invoiced_qty ?? 0) }}</td>
                                <td>{{ (float)($item->returned_qty ?? 0) }}</td>
                                <td class="returnable-qty font-weight-bold">{{ $item->returnable_qty }}</td>
                                <td>
                                    <input type="number" name="items[{{ $index }}][return_qty]" class="form-control return-qty" step="0.01" min="0" max="{{ $item->returnable_qty }}" value="{{ $item->returnable_qty }}">
                                </td>
                                <td>
                                    <input type="number" class="form-control item-rate" value="{{ $item->invoice_rate }}" readonly>
                                </td>
                                <td>{{ $item->product->gst_rate ?? 0 }}%</td>
                                <td class="item-amount">0.00</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="7" class="text-end">Taxable Amount:</th>
                                <th id="taxableAmount">0.00</th>
                            </tr>
                            <tr>
                                <th colspan="7" class="text-end">Output CGST Reversal:</th>
                                <th id="totalCgst">0.00</th>
                            </tr>
                            <tr>
                                <th colspan="7" class="text-end">Output SGST Reversal:</th>
                                <th id="totalSgst">0.00</th>
                            </tr>
                            <tr>
                                <th colspan="7" class="text-end">Output IGST Reversal:</th>
                                <th id="totalIgst">0.00</th>
                            </tr>
                            <tr class="table-light">
                                <th colspan="7" class="text-end fs-5">Grand Total:</th>
                                <th id="grandTotal" class="fs-5 text-primary">0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row mb-4">
                    <div class="col-md-12">
                        <label class="form-label">Narration {!! $settings->mandatory_narration ? '<span class="text-danger">*</span>' : '' !!}</label>
                        <textarea name="narration" class="form-control" rows="2" {{ $settings->mandatory_narration ? 'required' : '' }}>{{ old('narration', 'Credit Note raised against Sales Voucher: ' . $salesVoucher->voucher_number) }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('credit-notes.select') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" name="action" value="draft" class="btn btn-warning">Save Draft</button>
                    <button type="submit" name="action" value="post" class="btn btn-success" id="postBtn">Save & Post</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function calculateTotals() {
        let taxable = 0;
        let cgst = 0;
        let sgst = 0;
        let igst = 0; 

        $('#itemsTable tbody tr').each(function() {
            let qty = parseFloat($(this).find('.return-qty').val()) || 0;
            let rate = parseFloat($(this).find('.item-rate').val()) || 0;
            let gstRate = parseFloat($(this).find('.item-gst-rate').val()) || 0;
            let returnable = parseFloat($(this).find('.returnable-qty').text()) || 0;

            if(qty > returnable) {
                $(this).find('.return-qty').val(returnable);
                qty = returnable;
                toastr.error('Return quantity cannot exceed the available returnable quantity.');
            }

            let amount = qty * rate;
            $(this).find('.item-amount').text(amount.toFixed(2));
            taxable += amount;

            if (amount > 0 && gstRate > 0) {
                let gstAmount = amount * (gstRate / 100);
                // Assume Intra-state (CGST + SGST) for frontend calculation
                cgst += (gstAmount / 2);
                sgst += (gstAmount / 2);
            }
        });

        $('#taxableAmount').text(taxable.toFixed(2));
        $('#totalCgst').text(cgst.toFixed(2));
        $('#totalSgst').text(sgst.toFixed(2));
        $('#totalIgst').text(igst.toFixed(2));
        
        let grandTotal = taxable + cgst + sgst + igst;
        $('#grandTotal').text(grandTotal.toFixed(2));
    }

    $('.return-qty').on('input', calculateTotals);
    
    // Calculate on load
    calculateTotals();
    
    $('#cnForm').submit(function() {
        let hasQty = false;
        $('.return-qty').each(function() {
            if (parseFloat($(this).val()) > 0) hasQty = true;
        });
        
        if (!hasQty) {
            toastr.error('Please enter return quantity for at least one item.');
            return false;
        }
        
        if ($(document.activeElement).val() === 'post') {
            return confirm('Are you sure you want to Post this Credit Note? This will immediately increase physical stock and reduce the Customer Receivable balance.');
        }
        return true;
    });
});
</script>
@endpush
