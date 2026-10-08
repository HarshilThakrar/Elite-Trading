@extends('layouts.app')

@section('title', 'Select Purchase Order')
@section('header_title', 'Create Purchase Voucher')

@section('content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Step 1: Select Vendor and Purchase Order</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('purchase-vouchers.select-po') }}" class="mb-4">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Select Vendor <span class="text-danger">*</span></label>
                        <select name="vendor_id" class="form-select select2" required onchange="this.form.submit()">
                            <option value="">-- Choose Vendor --</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}" {{ (request('vendor_id') == $v->id) ? 'selected' : '' }}>
                                    {{ $v->company_name }} ({{ $v->vendor_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>

            @if($selectedVendor)
                @if($purchases->count() > 0)
                    <h5 class="mb-3">Available Purchase Orders for {{ $selectedVendor->company_name }}</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>PO Number</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>PO Total</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchases as $po)
                                <tr>
                                    <td><strong>{{ $po->po_number }}</strong></td>
                                    <td>{{ \Carbon\Carbon::parse($po->po_date)->format('d M, Y') }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $po->status }}</span>
                                    </td>
                                    <td>₹{{ number_format($po->total_amount, 2) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('purchase-vouchers.create', $po->id) }}" class="btn btn-primary btn-sm">
                                            Select <i class="ph ph-arrow-right"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="ph ph-warning-circle me-2"></i> No approved purchase orders with un-invoiced received quantities found for this vendor.
                        <br>
                        <small>Note: To generate a Purchase Voucher, the Goods must first be received via a Goods Receipt Note (GRN).</small>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
