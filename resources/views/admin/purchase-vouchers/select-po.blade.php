@extends('layouts.app')

@section('title', 'Select Purchase Order')
@section('header_title', 'Create Purchase Voucher')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="ph ph-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="ph ph-warning-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
            <div>
                <h6 class="m-0 font-weight-bold text-primary"><i class="ph ph-file-plus me-1"></i> Step 1: Select Vendor and Purchase Order</h6>
                <small class="text-muted">Choose a vendor from the list or search by vendor code (e.g. V-6018) to view available purchase orders.</small>
            </div>
            <div>
                <form method="POST" action="{{ route('purchase-vouchers.sync-excel') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Re-sync purchases and vendors from Excel">
                        <i class="ph ph-arrows-clockwise me-1"></i> Sync Excel Purchases
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body p-4">
            <form method="GET" action="{{ route('purchase-vouchers.select-po') }}" class="mb-4" id="vendorSelectForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-7">
                        <label class="form-label fw-bold">Select Vendor <span class="text-danger">*</span></label>
                        <select name="vendor_id" id="vendorSelect" class="form-select select2" onchange="this.form.submit()">
                            <option value="">-- Choose Vendor from List --</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}" data-code="{{ $v->vendor_code }}" 
                                    {{ ((request('vendor_id') == $v->id || request('vendor_id') == $v->vendor_code || (isset($selectedVendor) && $selectedVendor->id == $v->id))) ? 'selected' : '' }}>
                                    {{ $v->company_name }} ({{ $v->vendor_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Or Search by Vendor Code</label>
                        <div class="input-group">
                            <input type="text" name="vendor_code" class="form-control" placeholder="e.g. V-6018" value="{{ request('vendor_code') ?: ($selectedVendor ? $selectedVendor->vendor_code : '') }}">
                            <button class="btn btn-primary" type="submit">
                                <i class="ph ph-magnifying-glass me-1"></i> Search
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            @if($selectedVendor)
                <div class="card border bg-light mb-4 shadow-none">
                    <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h5 class="mb-0 font-weight-bold text-dark">{{ $selectedVendor->company_name }}</h5>
                                <span class="badge bg-primary fs-6">{{ $selectedVendor->vendor_code }}</span>
                            </div>
                            <div class="text-muted small">
                                <span class="me-3"><i class="ph ph-identification-card me-1"></i> GSTIN: <strong>{{ $selectedVendor->gst_no ?? 'N/A' }}</strong></span>
                                <span class="me-3"><i class="ph ph-phone me-1"></i> Mobile: <strong>{{ $selectedVendor->mobile ?? 'N/A' }}</strong></span>
                                <span><i class="ph ph-envelope me-1"></i> Email: <strong>{{ $selectedVendor->email ?? 'N/A' }}</strong></span>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('purchase-vouchers.create-direct-po') }}">
                                @csrf
                                <input type="hidden" name="vendor_id" value="{{ $selectedVendor->id }}">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="ph ph-plus-circle me-1"></i> Create Direct Voucher
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                @if($purchases->count() > 0)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 font-weight-bold text-secondary">
                            Available Purchase Orders ({{ $purchases->count() }})
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle shadow-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>PO / Invoice No.</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Items Summary</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-center" style="width: 160px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchases as $po)
                                <tr>
                                    <td>
                                        <span class="fw-bold text-primary">{{ $po->po_number }}</span>
                                        @if($po->notes)
                                            <div class="text-muted small">{{ Str::limit($po->notes, 40) }}</div>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($po->po_date)->format('d M, Y') }}</td>
                                    <td>
                                        @if($po->status === 'Approved' || $po->status === 'Received')
                                            <span class="badge bg-success">{{ $po->status }}</span>
                                        @elseif($po->status === 'Completed')
                                            <span class="badge bg-secondary">{{ $po->status }}</span>
                                        @else
                                            <span class="badge bg-info">{{ $po->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($po->items->count() > 0)
                                            <div>{{ $po->items->count() }} item(s)</div>
                                            <small class="text-muted">{{ Str::limit($po->items->first()->product->item_name ?? 'Item', 35) }}</small>
                                        @else
                                            <span class="text-muted small">General Purchase</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold fs-6">
                                        ₹{{ number_format((float)$po->total_amount, 2) }}
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('purchase-vouchers.create', $po->id) }}" class="btn btn-primary btn-sm px-3 shadow-sm">
                                            Select & Invoice <i class="ph ph-arrow-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between p-3 border-0 shadow-sm">
                        <div class="d-flex align-items-center">
                            <i class="ph ph-info me-3 fs-3 text-info"></i>
                            <div>
                                <h6 class="mb-1 font-weight-bold">No purchase orders found for {{ $selectedVendor->company_name }}</h6>
                                <p class="mb-0 text-muted small">You can create a Direct Purchase Voucher directly for this vendor right away.</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('purchase-vouchers.create-direct-po') }}" class="mt-2 mt-md-0">
                            @csrf
                            <input type="hidden" name="vendor_id" value="{{ $selectedVendor->id }}">
                            <button type="submit" class="btn btn-success shadow-sm">
                                <i class="ph ph-plus-circle me-1"></i> Create Direct Purchase Voucher
                            </button>
                        </form>
                    </div>
                @endif
            @else
                <div class="text-center py-5 border rounded bg-light">
                    <i class="ph ph-magnifying-glass fs-1 text-muted mb-2"></i>
                    <h6 class="text-muted">Please select a vendor or enter a vendor code above to view purchase orders.</h6>
                    <p class="text-muted small">Try searching <strong>V-6018</strong> or selecting <strong>SHAKUNTAL PRINTERS (V-6018)</strong>.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
