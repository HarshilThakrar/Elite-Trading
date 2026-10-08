@extends('layouts.app')

@section('title', 'Sales Vouchers')
@section('header_title', 'Sales Vouchers')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Sales Vouchers</h2>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('sales-vouchers.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="voucher_number" class="form-control" value="{{ request('voucher_number') }}" placeholder="Voucher Number">
                </div>
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="ph ph-funnel me-1"></i> Filter</button>
                    <a href="{{ route('sales-vouchers.index') }}" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Voucher No</th>
                            <th>Reference Invoice</th>
                            <th>Customer</th>
                            <th class="text-end">Amount (Dr)</th>
                            <th class="text-end">Amount (Cr)</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $voucher)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($voucher->date)->format('d M, Y') }}</td>
                            <td><strong>{{ $voucher->voucher_number }}</strong></td>
                            <td>
                                @if($voucher->reference_type == 'App\Models\Invoice' && $voucher->reference)
                                    <a href="{{ route('invoices.show', $voucher->reference_id) }}">{{ $voucher->reference->invoice_number }}</a>
                                @elseif($voucher->reference_type == 'App\Models\Sale' && $voucher->reference)
                                    <a href="{{ route('sales.show', $voucher->reference_id) }}">{{ $voucher->reference->invoice_number }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($voucher->reference_type == 'App\Models\Invoice' && $voucher->reference->sale && $voucher->reference->sale->customer)
                                    {{ $voucher->reference->sale->customer->company_name ?? $voucher->reference->sale->customer->customer_name }}
                                @elseif($voucher->reference_type == 'App\Models\Sale' && $voucher->reference->customer)
                                    {{ $voucher->reference->customer->company_name ?? $voucher->reference->customer->customer_name }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="text-end text-success">
                                ₹{{ number_format($voucher->entries->where('type', 'Dr')->sum('amount'), 2) }}
                            </td>
                            <td class="text-end text-danger">
                                ₹{{ number_format($voucher->entries->where('type', 'Cr')->sum('amount'), 2) }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('sales-vouchers.show', $voucher->id) }}" class="btn btn-sm btn-info text-white" title="View">
                                    <i class="ph ph-eye"></i>
                                </a>
                                @if($voucher->status === 'Posted')
                                    <a href="{{ route('sales-vouchers.pdf', $voucher->id) }}" class="btn btn-sm btn-secondary" title="Download PDF">
                                        <i class="ph ph-download-simple"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">No Sales Vouchers found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($vouchers->hasPages())
            <div class="mt-4">
                {{ $vouchers->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
