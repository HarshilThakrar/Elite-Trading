@extends('layouts.app')

@section('title', 'Goods Receipt Note ' . $grn->grn_number)
@section('header_title', 'Goods Receipt Note Details')

@section('content')
<div class="row">
    <div class="col-md-9">
        <div class="card card-custom mb-4 border-0 shadow-sm" id="printArea">
            <div class="card-body p-5">
                <!-- Header -->
                <div class="d-flex justify-content-between mb-4 border-bottom pb-4">
                    <div>
                        <div class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 mb-2 fw-semibold fs-6">
                            <i class="bi bi-check-circle me-1"></i> {{ $grn->status }}
                        </div>
                        <h2 class="fw-bold text-primary-custom mb-0">GOODS RECEIPT NOTE</h2>
                        <h5 class="text-muted">{{ $grn->grn_number }}</h5>
                    </div>
                    <div class="text-end">
                        <h4 class="fw-bold mb-1">Demo ERP</h4>
                        <p class="text-muted mb-0 small">123 Business Park, Metro City</p>
                        <p class="text-muted mb-0 small">Email: admin@demoerp.com | Phone: +91 70690 52046</p>
                        <p class="text-muted small">Warehouse / Receiving Dept</p>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <h6 class="text-muted text-uppercase fw-bold small mb-2">Vendor Details:</h6>
                        <h5 class="fw-bold mb-1 text-primary">{{ $grn->purchase->vendor->company_name }}</h5>
                        <p class="mb-0 small">{{ $grn->purchase->vendor->contact_person }}</p>
                        <p class="mb-0 small">Email: {{ $grn->purchase->vendor->email ?? 'N/A' }}</p>
                        <p class="mb-0 small">Phone: {{ $grn->purchase->vendor->mobile ?? 'N/A' }}</p>
                        <p class="mb-0 small">GSTIN: <span class="fw-semibold">{{ $grn->purchase->vendor->gst_no ?? 'N/A' }}</span></p>
                        @if($grn->purchase->vendor->address)
                            <p class="mb-0 small text-muted">{{ $grn->purchase->vendor->address }}, {{ $grn->purchase->vendor->city }} {{ $grn->purchase->vendor->pincode }}</p>
                        @endif
                    </div>
                    <div class="col-sm-6 text-end">
                        <h6 class="text-muted text-uppercase fw-bold small mb-2">Receipt Details:</h6>
                        <table class="table table-sm table-borderless ms-auto" style="width: auto;">
                            <tr>
                                <td class="text-muted text-end pe-3 py-1 small">GRN Number:</td>
                                <td class="fw-bold text-start py-1 text-primary">{{ $grn->grn_number }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted text-end pe-3 py-1 small">Receipt Date:</td>
                                <td class="fw-bold text-start py-1">{{ \Carbon\Carbon::parse($grn->received_date)->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted text-end pe-3 py-1 small">PO Reference:</td>
                                <td class="fw-bold text-start py-1">
                                    <a href="{{ route('purchases.show', $grn->purchase->id) }}" class="text-decoration-none">
                                        #{{ $grn->purchase->po_number }}
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted text-end pe-3 py-1 small">PO Date:</td>
                                <td class="text-start py-1">{{ \Carbon\Carbon::parse($grn->purchase->po_date)->format('d M Y') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Received Items Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 5%;" class="text-center">#</th>
                                <th style="width: 20%;">Part Code</th>
                                <th style="width: 35%;">Item Description</th>
                                <th style="width: 15%;" class="text-center">Batch / Lot #</th>
                                <th style="width: 15%;" class="text-end">Received Qty</th>
                                <th style="width: 15%;" class="text-end">Unit Rate (₹)</th>
                                <th style="width: 15%;" class="text-end">Total (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalQty = 0;
                                $totalValue = 0;
                            @endphp
                            @foreach($grn->items as $index => $item)
                                @php
                                    $lineTotal = $item->received_qty * $item->purchase_rate;
                                    $totalQty += $item->received_qty;
                                    $totalValue += $lineTotal;
                                @endphp
                                <tr>
                                    <td class="text-center text-muted fw-semibold">{{ $index + 1 }}</td>
                                    <td class="font-monospace fw-semibold">{{ $item->product->part_code ?? 'N/A' }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $item->product->item_name ?? 'N/A' }}</div>
                                    </td>
                                    <td class="text-center">
                                        {{ $item->batch_no ?: '-' }}
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        {{ number_format($item->received_qty, 2) }} <span class="small fw-normal text-muted">{{ $item->product->unit ?? '' }}</span>
                                    </td>
                                    <td class="text-end text-muted">
                                        {{ number_format($item->purchase_rate, 2) }}
                                    </td>
                                    <td class="text-end fw-bold">
                                        {{ number_format($lineTotal, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Total Received:</td>
                                <td class="text-end fw-bold text-success">{{ number_format($totalQty, 2) }}</td>
                                <td class="text-end fw-bold">Grand Total:</td>
                                <td class="text-end fw-bold fs-6 text-primary-custom">₹ {{ number_format($totalValue, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Notes / Remarks -->
                @if($grn->notes)
                <div class="mt-4 p-3 bg-light rounded border">
                    <h6 class="text-muted fw-bold small text-uppercase mb-1"><i class="bi bi-sticky me-1"></i>Receiving Remarks / Notes:</h6>
                    <p class="mb-0 text-dark">{{ $grn->notes }}</p>
                </div>
                @endif

                <!-- Signatures Section for Physical Print -->
                <div class="row mt-5 pt-4 text-center text-muted small border-top">
                    <div class="col-4">
                        <div style="height: 50px;"></div>
                        <div class="border-top pt-2">
                            <strong>Received By (Store Keeper)</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div style="height: 50px;"></div>
                        <div class="border-top pt-2">
                            <strong>Quality / Inspection Verified</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div style="height: 50px;"></div>
                        <div class="border-top pt-2">
                            <strong>Authorized Signatory</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Sidebar Actions -->
    <div class="col-md-3 print-hide">
        <div class="card card-custom sticky-top border-0 shadow-sm" style="top: 20px;">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-gear me-2"></i>Actions</h6>
            </div>
            <div class="card-body p-3">
                <button class="btn btn-light border w-100 mb-2 text-start" onclick="window.print()">
                    <i class="bi bi-printer me-2 text-primary"></i> Print GRN
                </button>
                <a href="{{ route('purchases.show', $grn->purchase_id) }}" class="btn btn-outline-primary w-100 mb-2 text-start">
                    <i class="bi bi-file-earmark-text me-2"></i> View Purchase Order
                </a>
                <a href="{{ route('grn.index') }}" class="btn btn-outline-secondary w-100 mb-3 text-start">
                    <i class="bi bi-list-ul me-2"></i> All Goods Receipts
                </a>

                <div class="alert alert-info py-2 px-3 small mb-0">
                    <i class="bi bi-info-circle me-1"></i> 
                    Inventory stock has been automatically updated in the warehouse ledger.
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printArea, #printArea * {
        visibility: visible;
    }
    #printArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
    }
    .print-hide {
        display: none !important;
    }
}
</style>
@endsection
