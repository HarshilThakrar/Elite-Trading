@extends('layouts.app')

@section('title', 'Select Purchase Voucher for Debit Note')
@section('header_title', 'Create Debit Note')

@section('content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Step 1: Select Vendor and Purchase Voucher</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('debit-notes.select') }}" class="mb-4">
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
                @if($purchaseVouchers->count() > 0)
                    <h5 class="mb-3">Posted Purchase Vouchers with Returnable Quantities for {{ $selectedVendor->company_name }}</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Voucher Number</th>
                                    <th>Date</th>
                                    <th>Vendor Invoice No</th>
                                    <th>Invoice Total</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseVouchers as $pv)
                                <tr>
                                    <td><strong>{{ $pv->voucher_number }}</strong></td>
                                    <td>{{ \Carbon\Carbon::parse($pv->date)->format('d M, Y') }}</td>
                                    <td>{{ $pv->metadata['vendor_invoice_number'] ?? 'N/A' }}</td>
                                    <td>₹{{ number_format($pv->metadata['grand_total'] ?? 0, 2) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('debit-notes.create', $pv->id) }}" class="btn btn-primary btn-sm">
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
                        <i class="ph ph-warning-circle me-2"></i> No eligible Purchase Vouchers found for this vendor.
                        <br>
                        <small>Only Posted Purchase Vouchers with remaining returnable quantities are displayed.</small>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
