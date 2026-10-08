@extends('layouts.app')

@section('title', 'Order Frequency Analysis - Demo ERP')
@section('header_title', 'Order Frequency & Predictions')

@section('content')
<div class="card card-custom">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="m-0 fw-bold text-primary-custom"><i class="bi bi-calendar-check-fill me-2"></i> Predictive Order Timeline</h5>
        <div class="text-muted small">
            <i class="bi bi-info-circle me-1"></i> Requires at least 2 historical orders to predict.
        </div>
    </div>
    <div class="card-body">
        
        <div class="alert alert-primary mb-4" style="background-color: #e0e7ff; border: 1px solid #c7d2fe; color: #3730a3;">
            <h6 class="fw-bold"><i class="bi bi-robot me-2"></i> How Predictions Work:</h6>
            <p class="mb-0">The system analyzes the exact gap in days between every past order a customer has placed. It calculates their unique <strong>Average Gap</strong>, and adds it to their last order date to accurately predict exactly when they should place their next order.</p>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th class="text-center">Total Orders</th>
                        <th class="text-center">Average Gap</th>
                        <th class="text-center">Last Order Date</th>
                        <th class="text-center">Predicted Next Order</th>
                        <th class="text-center">Status Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($predictionData as $data)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 40px; height: 40px;">
                                    {{ substr($data['customer']->company_name, 0, 1) }}
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ $data['customer']->company_name }}</h6>
                                    <small class="text-muted">{{ $data['customer']->contact_person }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="text-center fw-bold">{{ $data['total_orders'] }}</td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border"><i class="bi bi-clock-history me-1"></i> Every {{ $data['average_gap'] }} Days</span>
                        </td>
                        <td class="text-center text-muted">
                            {{ $data['last_sale_date']->format('d M Y') }}
                        </td>
                        <td class="text-center fw-bold text-primary-custom fs-5">
                            {{ $data['next_expected_date']->format('d M Y') }}
                        </td>
                        <td class="text-center">
                            @if($data['status'] == 'Overdue')
                                <span class="badge rounded-pill bg-danger w-100 py-2 fs-6 shadow-sm pulse-red">
                                    <i class="bi bi-telephone-outbound-fill me-1"></i> Call Now (Overdue)
                                </span>
                            @elseif($data['status'] == 'Due Now')
                                <span class="badge rounded-pill text-dark w-100 py-2 fs-6 shadow-sm pulse-yellow" style="background-color: #ffc107;">
                                    <i class="bi bi-bell-fill me-1"></i> Due Soon (Call)
                                </span>
                            @else
                                <span class="badge rounded-pill bg-success w-100 py-2 fs-6 shadow-sm">
                                    <i class="bi bi-check-circle-fill me-1"></i> Upcoming
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-graph-down text-secondary fs-1 d-block mb-3 opacity-25"></i>
                            <h5>No Predictive Data Available Yet</h5>
                            <p>Once customers place at least 2 orders, their predictions will appear here.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.pulse-red { animation: pulseRed 2s infinite; }
@keyframes pulseRed {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); box-shadow: 0 0 10px rgba(220, 53, 69, 0.5); }
    100% { transform: scale(1); }
}

.pulse-yellow { animation: pulseYellow 2s infinite; }
@keyframes pulseYellow {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); box-shadow: 0 0 10px rgba(255, 193, 7, 0.5); }
    100% { transform: scale(1); }
}
</style>
@endsection
