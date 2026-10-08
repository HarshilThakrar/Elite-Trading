@extends('layouts.app')

@section('title', 'Create Purchase Voucher')
@section('header_title', 'Create Purchase Voucher')

@section('content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Purchase Voucher for PO: {{ $purchase->po_number }}</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('purchase-vouchers.store', $purchase->id) }}" id="pvForm">
                @csrf
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Vendor</label>
                        <input type="text" class="form-control bg-light" value="{{ $vendor->company_name }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Voucher Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Vendor Invoice No <span class="text-danger">*</span></label>
                        <input type="text" name="vendor_invoice_number" class="form-control" value="{{ old('vendor_invoice_number') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Vendor Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" name="vendor_invoice_date" class="form-control" value="{{ old('vendor_invoice_date', date('Y-m-d')) }}" required>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-hover" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Ordered</th>
                                <th>Received</th>
                                <th>Invoiced</th>
                                <th>Available</th>
                                <th>Invoice Qty</th>
                                <th>Rate</th>
                                <th>GST %</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($itemsWithPending as $index => $item)
                            <tr>
                                <td>
                                    {{ $item->product->item_name }}
                                    <input type="hidden" name="items[{{ $index }}][purchase_item_id]" value="{{ $item->id }}">
                                    <input type="hidden" class="item-gst-rate" value="{{ $item->product->gst_rate ?? 0 }}">
                                </td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ $item->received_so_far }}</td>
                                <td>{{ $item->invoiced_so_far }}</td>
                                <td class="available-qty">{{ $item->available_to_invoice }}</td>
                                <td>
                                    <input type="number" name="items[{{ $index }}][invoiced_qty]" class="form-control invoice-qty" step="0.01" min="0" max="{{ $item->available_to_invoice }}" value="{{ $item->available_to_invoice }}">
                                </td>
                                <td>
                                    <input type="number" class="form-control item-rate" value="{{ $item->unit_price }}" readonly>
                                </td>
                                <td>{{ $item->product->gst_rate ?? 0 }}%</td>
                                <td class="item-amount">0.00</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="8" class="text-end">Taxable Amount:</th>
                                <th id="taxableAmount">0.00</th>
                            </tr>
                            <tr>
                                <th colspan="8" class="text-end">CGST:</th>
                                <th id="totalCgst">0.00</th>
                            </tr>
                            <tr>
                                <th colspan="8" class="text-end">SGST:</th>
                                <th id="totalSgst">0.00</th>
                            </tr>
                            <tr>
                                <th colspan="8" class="text-end">IGST:</th>
                                <th id="totalIgst">0.00</th>
                            </tr>
                            <tr class="table-light">
                                <th colspan="8" class="text-end fs-5">Grand Total:</th>
                                <th id="grandTotal" class="fs-5 text-primary">0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row mb-4">
                    <div class="col-md-12">
                        <label class="form-label">Narration {!! $settings->mandatory_narration ? '<span class="text-danger">*</span>' : '' !!}</label>
                        <textarea name="narration" class="form-control" rows="2" {{ $settings->mandatory_narration ? 'required' : '' }}>{{ old('narration', 'Being purchase invoice booked against PO: ' . $purchase->po_number) }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('purchase-vouchers.select-po') }}" class="btn btn-secondary">Cancel</a>
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
        let igst = 0; // Set to inter-state logic if needed

        $('#itemsTable tbody tr').each(function() {
            let qty = parseFloat($(this).find('.invoice-qty').val()) || 0;
            let rate = parseFloat($(this).find('.item-rate').val()) || 0;
            let gstRate = parseFloat($(this).find('.item-gst-rate').val()) || 0;
            let available = parseFloat($(this).find('.available-qty').text()) || 0;

            if(qty > available) {
                $(this).find('.invoice-qty').val(available);
                qty = available;
                toastr.error('Quantity cannot exceed available received quantity.');
            }

            let amount = qty * rate;
            $(this).find('.item-amount').text(amount.toFixed(2));
            taxable += amount;

            if (amount > 0 && gstRate > 0) {
                let gstAmount = amount * (gstRate / 100);
                // Currently assuming Intra-state (CGST + SGST)
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

    $('.invoice-qty').on('input', calculateTotals);
    
    // Calculate totals on page load
    calculateTotals();
    
    $('#pvForm').submit(function() {
        let hasQty = false;
        $('.invoice-qty').each(function() {
            if (parseFloat($(this).val()) > 0) hasQty = true;
        });
        
        if (!hasQty) {
            toastr.error('Please enter invoice quantity for at least one item.');
            return false;
        }
        
        if ($(document.activeElement).val() === 'post') {
            return confirm('Are you sure you want to Post this Purchase Voucher? This will impact accounting and Vendor Payable immediately.');
        }
        return true;
    });
});
</script>
@endpush
