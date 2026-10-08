@extends('layouts.app')

@section('title', 'Top Customers - Demo ERP')
@section('header_title', 'Top Customers')

@section('content')
<!-- Filter Card -->
<div class="card card-custom mb-4 print-hide">
    <div class="card-body bg-white border-bottom p-4">
        <form action="{{ route('reports.topCustomers') }}" method="GET" class="row g-3 align-items-center">
            <div class="col-auto">
                <label for="period" class="col-form-label fw-bold">Period:</label>
            </div>
            <div class="col-auto">
                <select id="period" name="period" class="form-select">
                    <option value="today" {{ $period == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $period == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="this_week" {{ $period == 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="this_month" {{ $period == 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="last_month" {{ $period == 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="this_quarter" {{ $period == 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                    <option value="this_year" {{ $period == 'this_year' ? 'selected' : '' }}>This Year</option>
                    <option value="last_year" {{ $period == 'last_year' ? 'selected' : '' }}>Last Year</option>
                    <option value="365_days" {{ $period == '365_days' ? 'selected' : '' }}>Last 365 Days</option>
                    <option value="all" {{ $period == 'all' ? 'selected' : '' }}>All Time</option>
                </select>
            </div>
            <div class="col-auto">
                <label for="limit" class="col-form-label fw-bold">Top N:</label>
            </div>
            <div class="col-auto">
                <input type="number" id="limit" name="limit" class="form-control" value="{{ $limit }}" min="1">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-filter me-2"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Print-Only Header -->
<div class="print-only mb-3">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
        <div>
            <h3 class="fw-bold mb-0 text-dark">Demo ERP System</h3>
            <div class="text-muted small">Top {{ $limit }} Customers by Revenue Report ({{ $periodLabel }})</div>
        </div>
        <div class="text-end small">
            <div><strong>Generated on:</strong> {{ now()->format('d M Y, h:i A') }}</div>
        </div>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="m-0 fw-bold text-primary-custom"><i class="bi bi-trophy me-2"></i> Top {{ $limit }} Customers by Revenue ({{ $periodLabel }})</h5>
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
                        <th class="text-end px-4">Total Revenue (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topCustomers as $index => $data)
                    <tr>
                        <td class="px-4 text-muted fw-bold">
                            @if($index == 0)
                                <i class="bi bi-award-fill text-warning fs-4"></i>
                            @elseif($index == 1)
                                <i class="bi bi-award-fill text-secondary fs-4"></i>
                            @elseif($index == 2)
                                <i class="bi bi-award-fill text-danger fs-4"></i>
                            @else
                                #{{ $index + 1 }}
                            @endif
                        </td>
                        <td>
                            <h6 class="mb-0 fw-bold">{{ $data->customer->company_name ?? 'Unknown Customer' }}</h6>
                            <small class="text-muted">{{ $data->customer->contact_person ?? '' }}</small>
                        </td>
                        <td class="text-end fw-bold fs-5 text-success px-4">
                            {{ number_format($data->total_revenue, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center py-5 text-muted">
                            <i class="bi bi-person-x text-secondary fs-1 d-block mb-3 opacity-25"></i>
                            <h5>No Top Customers Found</h5>
                            <p>No completed sales data available for the selected period.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
