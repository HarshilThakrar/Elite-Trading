@extends('layouts.app')

@section('title', 'Customer Profitability - Demo ERP')
@section('header_title', 'Customer Profitability Analysis')

@section('content')
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card card-custom bg-white border-start border-4 border-primary">
            <div class="card-body">
                <div class="text-muted small fw-bold text-uppercase mb-1">Total Customers Analyzed</div>
                <h3 class="fw-bold mb-0">{{ count($profitabilityData) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom bg-white border-start border-4 border-success">
            <div class="card-body">
                <div class="text-muted small fw-bold text-uppercase mb-1">Highest Margin</div>
                <h3 class="fw-bold mb-0 text-success">
                    {{ count($profitabilityData) > 0 ? number_format(collect($profitabilityData)->max('margin_percent'), 1) : '0' }}%
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-0 h-100">
            <i class="bi bi-info-circle-fill fs-3 me-3 text-info"></i>
            <div>
                <strong>How this works:</strong> Gross Profit = Net Revenue - Cost of Goods Sold.
                Discount is calculated as the difference between the Product's List Price and the Final Sold Price.
            </div>
        </div>
    </div>
</div>

<!-- Print-Only Header -->
<div class="print-only mb-3">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
        <div>
            <h3 class="fw-bold mb-0 text-dark">Demo ERP System</h3>
            <div class="text-muted small">Customer Profitability Analysis Report</div>
        </div>
        <div class="text-end small">
            <div><strong>Generated on:</strong> {{ now()->format('d M Y, h:i A') }}</div>
        </div>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="m-0 fw-bold text-primary-custom"><i class="bi bi-graph-up-arrow me-2"></i> Profitability Ranking (Highest to Lowest)</h5>
        <div class="d-flex gap-2 align-items-center print-hide">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
            <button type="button" class="btn btn-danger btn-sm text-white" onclick="window.print()">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4">Rank</th>
                        <th>Customer</th>
                        <th class="text-end">Orders</th>
                        <th class="text-end">Net Revenue (₹)</th>
                        <th class="text-end">Discount Given (₹)</th>
                        <th class="text-end">Estimated COGS (₹)</th>
                        <th class="text-end">Gross Profit (₹)</th>
                        <th class="text-center px-4">Margin %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($profitabilityData as $index => $data)
                    <tr class="{{ $data['gross_profit'] < 0 ? 'bg-danger bg-opacity-10' : '' }}">
                        <td class="px-4 text-muted fw-bold">#{{ $index + 1 }}</td>
                        <td>
                            <h6 class="mb-0 fw-bold">{{ $data['customer']->company_name }}</h6>
                            <small class="text-muted">{{ $data['customer']->contact_person }}</small>
                        </td>
                        <td class="text-end">{{ $data['total_orders'] }}</td>
                        <td class="text-end fw-bold">{{ number_format($data['net_revenue'], 2) }}</td>
                        <td class="text-end text-warning fw-bold">-{{ number_format($data['total_discount'], 2) }}</td>
                        <td class="text-end text-muted">-{{ number_format($data['total_cogs'], 2) }}</td>
                        
                        <td class="text-end fw-bold fs-5 {{ $data['gross_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($data['gross_profit'], 2) }}
                        </td>
                        
                        <td class="text-center px-4">
                            @if($data['margin_percent'] >= 30)
                                <span class="badge bg-success text-white w-100 py-2 fs-6"><i class="bi bi-arrow-up-circle me-1"></i> {{ number_format($data['margin_percent'], 1) }}%</span>
                            @elseif($data['margin_percent'] > 0)
                                <span class="badge bg-warning text-dark w-100 py-2 fs-6"><i class="bi bi-dash-circle me-1"></i> {{ number_format($data['margin_percent'], 1) }}%</span>
                            @else
                                <span class="badge bg-danger text-white w-100 py-2 fs-6"><i class="bi bi-arrow-down-circle me-1"></i> {{ number_format($data['margin_percent'], 1) }}%</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-wallet2 text-secondary fs-1 d-block mb-3 opacity-25"></i>
                            <h5>No Sales Data Found</h5>
                            <p>Once you have approved sales, profitability metrics will appear here.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
