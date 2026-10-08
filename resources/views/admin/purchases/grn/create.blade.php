@extends('layouts.app')

@section('title', 'Generate Goods Receipt Note (GRN) - #' . $purchase->po_number)
@section('header_title', 'Generate Goods Receipt Note (GRN)')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Back Navigation & Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="{{ route('purchases.show', $purchase->id) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Purchase Order
                </a>
            </div>
            <div class="text-end">
                <span class="badge bg-primary px-3 py-2 fs-6">PO #{{ $purchase->po_number }}</span>
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Purchase Order Summary Info -->
        <div class="card card-custom mb-4 border-0 shadow-sm">
            <div class="card-header bg-light py-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Purchase Order Summary</h6>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="text-muted small">PO Date: <strong>{{ \Carbon\Carbon::parse($purchase->po_date)->format('d M Y') }}</strong></span>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4 border-end">
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Vendor</div>
                        <h6 class="fw-bold mb-1 text-primary">{{ $purchase->vendor->company_name }}</h6>
                        <div class="text-muted small">{{ $purchase->vendor->contact_person }}</div>
                        <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{{ $purchase->vendor->mobile ?? 'N/A' }}</div>
                        <div class="text-muted small"><i class="bi bi-envelope me-1"></i>{{ $purchase->vendor->email ?? 'N/A' }}</div>
                        <div class="text-muted small">GSTIN: <span class="fw-semibold">{{ $purchase->vendor->gst_no ?? 'N/A' }}</span></div>
                    </div>
                    <div class="col-md-4 border-end">
                        <div class="text-muted small text-uppercase fw-semibold mb-1">Delivery / Billing Address</div>
                        <p class="text-muted small mb-0">
                            {{ $purchase->vendor->address ?? 'N/A' }}<br>
                            {{ $purchase->vendor->city ?? '' }}{{ $purchase->vendor->city && $purchase->vendor->state ? ', ' : '' }}{{ $purchase->vendor->state ?? '' }} {{ $purchase->vendor->pincode ?? '' }}
                        </p>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small text-uppercase fw-semibold mb-1">PO Status & Value</div>
                        <div class="mb-1">
                            Status: <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">{{ $purchase->status }}</span>
                        </div>
                        <div class="mb-1">
                            Total Order Value: <span class="fw-bold text-dark">₹ {{ number_format($purchase->total_amount, 2) }}</span>
                        </div>
                        @if($purchase->notes)
                            <div class="text-muted small mt-2">
                                <strong>PO Notes:</strong> {{ $purchase->notes }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- GRN Entry Form -->
        <form action="{{ route('purchases.grn.store', $purchase->id) }}" method="POST" id="grnForm">
            @csrf

            <div class="card card-custom border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary-custom">
                        <i class="bi bi-box-arrow-in-down me-2"></i>Goods Receipt Details
                    </h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnFillAll">
                            <i class="bi bi-check2-all me-1"></i> Receive Full Pending
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearAll">
                            <i class="bi bi-x-circle me-1"></i> Clear Quantities
                        </button>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Receipt Date <span class="text-danger">*</span></label>
                            <input type="date" name="received_date" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                            <div class="form-text">Date when physical materials were unloaded/verified.</div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Receiving Notes / Remarks</label>
                            <input type="text" name="notes" class="form-control" placeholder="e.g. Received via BlueDart courier / Transporter LR #1234, vehicle MH-04-1234, verified OK">
                            <div class="form-text">Optional details such as transporter name, LR number, vehicle number, condition of packaging.</div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-secondary text-uppercase mb-3">Pending Items to Receive</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle" id="grnItemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;" class="text-center">#</th>
                                    <th style="width: 35%;">Product / Item Description</th>
                                    <th style="width: 12%;" class="text-center">Ordered Qty</th>
                                    <th style="width: 12%;" class="text-center">Received So Far</th>
                                    <th style="width: 12%;" class="text-center">Pending Qty</th>
                                    <th style="width: 12%;" class="text-center bg-primary-subtle text-primary fw-bold">Receiving Now <span class="text-danger">*</span></th>
                                    <th style="width: 12%;">Batch / Lot #</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($itemsWithPending as $index => $item)
                                <tr>
                                    <td class="text-center fw-semibold text-muted">
                                        {{ $index + 1 }}
                                        <input type="hidden" name="items[{{ $index }}][purchase_item_id]" value="{{ $item->id }}">
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $item->product->item_name }}</div>
                                        <div class="small text-muted font-monospace">Part Code: {{ $item->product->part_code }}</div>
                                        <div class="small text-muted">Unit Rate: ₹ {{ number_format($item->unit_price, 2) }}</div>
                                    </td>
                                    <td class="text-center fw-semibold">
                                        {{ number_format($item->quantity, 2) }} <span class="small text-muted">{{ $item->product->unit ?? 'Units' }}</span>
                                    </td>
                                    <td class="text-center text-muted">
                                        {{ number_format($item->received_so_far, 2) }} <span class="small">{{ $item->product->unit ?? 'Units' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 fs-6 pending-badge" data-pending="{{ $item->pending_qty }}">
                                            {{ number_format($item->pending_qty, 2) }} {{ $item->product->unit ?? 'Units' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" 
                                                   step="0.01" 
                                                   min="0" 
                                                   max="{{ $item->pending_qty }}" 
                                                   name="items[{{ $index }}][received_qty]" 
                                                   class="form-control text-center fw-bold text-primary receiving-qty-input" 
                                                   value="{{ $item->pending_qty }}" 
                                                   data-max="{{ $item->pending_qty }}" 
                                                   required>
                                            <span class="input-group-text bg-light text-muted small">{{ $item->product->unit ?? 'Units' }}</span>
                                        </div>
                                        <div class="invalid-feedback d-none qty-error-msg small text-danger">Cannot exceed pending qty</div>
                                    </td>
                                    <td>
                                        <input type="text" 
                                               name="items[{{ $index }}][batch_no]" 
                                               class="form-control form-control-sm" 
                                               placeholder="Batch # (optional)">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Total Quantity Receiving Now:</td>
                                    <td class="text-center fw-bold fs-6 text-primary" id="totalReceivingQty">0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light p-3 d-flex justify-content-between align-items-center">
                    <a href="{{ route('purchases.show', $purchase->id) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-success px-4" id="btnSubmitGrn">
                        <i class="bi bi-check-circle me-1"></i> Save & Generate GRN
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        function updateTotalQty() {
            let total = 0;
            let hasValidItem = false;
            let hasError = false;

            $('.receiving-qty-input').each(function() {
                let val = parseFloat($(this).val()) || 0;
                let max = parseFloat($(this).data('max')) || 0;
                let errorMsg = $(this).siblings('.qty-error-msg');

                if (val < 0) {
                    val = 0;
                    $(this).val(0);
                }

                if (val > max) {
                    $(this).addClass('is-invalid');
                    errorMsg.removeClass('d-none');
                    hasError = true;
                } else {
                    $(this).removeClass('is-invalid');
                    errorMsg.addClass('d-none');
                }

                if (val > 0) {
                    hasValidItem = true;
                }

                total += val;
            });

            $('#totalReceivingQty').text(total.toFixed(2));

            if (!hasValidItem || hasError) {
                $('#btnSubmitGrn').prop('disabled', true);
            } else {
                $('#btnSubmitGrn').prop('disabled', false);
            }
        }

        // Live input handling
        $(document).on('input change', '.receiving-qty-input', function() {
            updateTotalQty();
        });

        // Fill all pending
        $('#btnFillAll').click(function() {
            $('.receiving-qty-input').each(function() {
                let max = $(this).data('max');
                $(this).val(max);
            });
            updateTotalQty();
        });

        // Clear all
        $('#btnClearAll').click(function() {
            $('.receiving-qty-input').val(0);
            updateTotalQty();
        });

        // Form submission safety
        $('#grnForm').on('submit', function(e) {
            let total = 0;
            $('.receiving-qty-input').each(function() {
                total += parseFloat($(this).val()) || 0;
            });

            if (total <= 0) {
                e.preventDefault();
                alert('Please enter a receiving quantity greater than 0 for at least one item.');
                return false;
            }

            return confirm('Are you sure you want to generate this Goods Receipt Note? This will update warehouse inventory.');
        });

        // Initial calc
        updateTotalQty();
    });
</script>
@endpush
