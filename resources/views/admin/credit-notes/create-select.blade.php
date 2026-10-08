@extends('layouts.app')

@section('title', 'Select Sales Voucher for Credit Note')
@section('header_title', 'Create Credit Note')

@section('content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Step 1: Select Customer and Sales Voucher</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('credit-notes.select') }}" class="mb-4">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Select Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-select select2" required onchange="this.form.submit()">
                            <option value="">-- Choose Customer --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ (request('customer_id') == $c->id) ? 'selected' : '' }}>
                                    {{ $c->company_name }} ({{ $c->customer_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>

            @if($selectedCustomer)
                @if($salesVouchers->count() > 0)
                    <h5 class="mb-3">Posted Sales Invoices with Returnable Quantities for {{ $selectedCustomer->company_name }}</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Voucher Number</th>
                                    <th>Date</th>
                                    <th>Invoice No</th>
                                    <th>Invoice Total</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salesVouchers as $sv)
                                <tr>
                                    <td><strong>{{ $sv->voucher_number }}</strong></td>
                                    <td>{{ \Carbon\Carbon::parse($sv->date)->format('d M, Y') }}</td>
                                    <td>{{ $sv->reference->invoice_number ?? 'N/A' }}</td>
                                    <td>₹{{ number_format($sv->reference->total_amount ?? 0, 2) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('credit-notes.create', $sv->id) }}" class="btn btn-primary btn-sm">
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
                        <i class="ph ph-warning-circle me-2"></i> No eligible Sales Invoices found for this customer.
                        <br>
                        <small>Only Posted Sales Vouchers with remaining returnable quantities are displayed.</small>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
