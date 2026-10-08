@extends('layouts.app')

@section('title', 'Smart Reorder Management - Demo ERP')
@section('header_title', 'Smart Reorder Management')

@section('content')
<!-- Print-Only Header -->
<div class="print-only mb-3">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
        <div>
            <h3 class="fw-bold mb-0 text-dark">Demo ERP System</h3>
            <div class="text-muted small">Smart Reorder & Inventory Replenishment Report</div>
        </div>
        <div class="text-end small">
            <div><strong>Generated on:</strong> {{ now()->format('d M Y, h:i A') }}</div>
        </div>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="m-0 fw-bold text-primary-custom"><i class="bi bi-cpu me-2"></i> Smart Reorder Engine</h5>
            <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Based on 90-day consumption data</small>
        </div>
        <div class="d-flex gap-2 align-items-center print-hide">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
            <button type="button" class="btn btn-danger btn-sm text-white" onclick="window.print()">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </button>
        </div>
    </div>
    <div class="card-body">
        
        <div class="alert alert-info mb-4">
            <h6 class="fw-bold"><i class="bi bi-lightbulb me-2"></i> How it works:</h6>
            <p class="mb-0">The system calculates your exact average consumption based on actual sales dispatches over the last 90 days. It then multiplies this daily average by your <strong>Lead Time</strong> to determine exactly when you should reorder to prevent a stockout.</p>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Lead Time</th>
                        <th class="text-center" title="Avg Monthly Consumption">Monthly Cons.</th>
                        <th class="text-center" title="Avg Yearly Consumption">Yearly Cons.</th>
                        <th class="text-center" title="Dynamic Reorder Level calculated from data">Smart Reorder Level</th>
                        <th class="text-center">Available Stock</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reorderData as $data)
                    <tr class="{{ $data['needs_reorder'] ? 'table-danger' : '' }}">
                        <td>
                            <span class="fw-bold">{{ $data['product']->part_code }}</span><br>
                            <small class="text-muted">{{ $data['product']->item_name }}</small>
                        </td>
                        <td class="text-center">{{ $data['lead_time_days'] }} Days</td>
                        <td class="text-center">{{ $data['amc'] }}</td>
                        <td class="text-center">{{ $data['ayc'] }}</td>
                        <td class="text-center fw-bold text-primary-custom">{{ $data['dynamic_reorder_level'] }}</td>
                        <td class="text-center fw-bold {{ $data['needs_reorder'] ? 'text-danger' : 'text-success' }}">
                            {{ $data['available_stock'] }}
                        </td>
                        <td class="text-center">
                            @if($data['status'] === 'Ordered')
                                <span class="badge bg-info text-white"><i class="bi bi-box-seam me-1"></i> Ordered</span>
                            @elseif($data['status'] === 'Upcoming')
                                <span class="badge bg-warning text-dark pulse"><i class="bi bi-clock-history me-1"></i> Upcoming</span>
                            @else
                                <span class="badge bg-success text-white">Sufficient</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($data['status'] === 'Upcoming')
                                <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-danger fw-bold shadow-sm">
                                    <i class="bi bi-cart-plus"></i> Create PO
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.pulse {
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); box-shadow: 0 0 10px rgba(220, 53, 69, 0.5); }
    100% { transform: scale(1); }
}
</style>
@endsection
