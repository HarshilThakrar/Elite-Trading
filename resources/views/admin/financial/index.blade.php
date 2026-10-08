@extends('layouts.app')

@section('title', 'Financial Overview - Demo ERP')
@section('header_title', 'Financial Overview')

@section('content')
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card card-custom bg-primary text-white h-100">
            <div class="card-body p-4 d-flex flex-column justify-content-center">
                <h6 class="text-white-50 text-uppercase fw-bold mb-2">Total Revenue</h6>
                <h2 class="fw-bold mb-0">₹ {{ number_format($totalRevenue, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom bg-danger text-white h-100">
            <div class="card-body p-4 d-flex flex-column justify-content-center">
                <h6 class="text-white-50 text-uppercase fw-bold mb-2">Total Expenses</h6>
                <h2 class="fw-bold mb-0">₹ {{ number_format($totalExpenses, 2) }}</h2>
            </div>
        </div>
    </div>
    @php
        $canViewProfit = auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('View Profit'));
    @endphp
    @if($canViewProfit)
    <div class="col-md-4">
        <div class="card card-custom bg-success text-white h-100">
            <div class="card-body p-4 d-flex flex-column justify-content-center">
                <h6 class="text-white-50 text-uppercase fw-bold mb-2">Gross Profit</h6>
                <h2 class="fw-bold mb-0">₹ {{ number_format($grossProfit, 2) }}</h2>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card card-custom h-100">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-primary-custom"><i class="ph ph-receipt me-2"></i> Recent Sales</h6>
                <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M, Y') }}</td>
                                <td><a href="{{ route('sales.show', $sale->id) }}">{{ $sale->invoice_number }}</a></td>
                                <td>{{ $sale->customer->company_name ?? 'N/A' }}</td>
                                <td class="text-end text-success fw-semibold">₹{{ number_format($sale->total_amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No recent sales found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card card-custom h-100">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-danger"><i class="ph ph-shopping-cart me-2"></i> Recent Purchases</h6>
                <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-outline-danger">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>PO Number</th>
                                <th>Vendor</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPurchases as $purchase)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($purchase->po_date)->format('d M, Y') }}</td>
                                <td><a href="{{ route('purchases.show', $purchase->id) }}">{{ $purchase->po_number }}</a></td>
                                <td>{{ $purchase->vendor->company_name ?? 'N/A' }}</td>
                                <td class="text-end text-danger fw-semibold">₹{{ number_format($purchase->total_amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No recent purchases found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
