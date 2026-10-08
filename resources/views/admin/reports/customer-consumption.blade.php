@extends('layouts.app')

@section('title', 'Customer Consumption Analysis - Demo ERP')
@section('header_title', 'Customer Consumption Analysis')

@section('content')
<div class="card card-custom mb-4">
    <div class="card-body bg-light">
        <form method="GET" action="{{ route('reports.customerConsumption') }}" class="row align-items-end">
            <div class="col-md-5">
                <label for="customer_id" class="form-label fw-bold">Select Customer</label>
                <select name="customer_id" id="customer_id" class="form-select select2" onchange="this.form.submit()">
                    <option value="">-- Choose a Customer --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ $selectedCustomerId == $customer->id ? 'selected' : '' }}>
                            {{ $customer->company_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-7 text-end text-muted small pb-2">
                <i class="bi bi-info-circle me-1"></i> Data based on Approved and Dispatched Sales Orders over the last 12 months.
            </div>
        </form>
    </div>
</div>

@if($selectedCustomerId)
    <div class="card card-custom">
        <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold text-primary-custom">
                <i class="bi bi-graph-up me-2"></i> 12-Month Consumption Trend for {{ $selectedCustomer->company_name }}
            </h5>
        </div>
        <div class="card-body">
            @if(empty($matrix))
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-basket text-secondary fs-1 d-block mb-3"></i>
                    <h5>No Purchase History</h5>
                    <p>This customer hasn't purchased anything in the last 12 months.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light text-center">
                            <tr>
                                <th class="text-start">Product</th>
                                @foreach($months as $month)
                                    <th>{{ $month['label'] }}</th>
                                @endforeach
                                <th>MoM Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($matrix as $productId => $data)
                            <tr>
                                <td>
                                    <span class="fw-bold">{{ $data['product']->part_code }}</span><br>
                                    <small class="text-muted">{{ $data['product']->item_name }}</small>
                                </td>
                                @foreach($months as $month)
                                    <td class="text-center fw-bold fs-6 {{ $data['months'][$month['key']] > 0 ? 'text-dark' : 'text-muted opacity-25' }}">
                                        {{ $data['months'][$month['key']] }}
                                    </td>
                                @endforeach
                                <td class="text-center">
                                    @if($data['trend'] > 0)
                                        <span class="badge bg-success bg-opacity-25 text-success border border-success w-100 py-2">
                                            <i class="bi bi-arrow-up-right-circle-fill me-1"></i> +{{ $data['trend'] }}%
                                        </span>
                                    @elseif($data['trend'] < 0)
                                        <span class="badge bg-danger bg-opacity-25 text-danger border border-danger w-100 py-2">
                                            <i class="bi bi-arrow-down-right-circle-fill me-1"></i> {{ $data['trend'] }}%
                                        </span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary w-100 py-2">
                                            <i class="bi bi-dash-circle-fill me-1"></i> Flat
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@else
    <div class="text-center py-5 text-muted">
        <i class="bi bi-arrow-up-circle fs-1 d-block mb-3 opacity-25"></i>
        <h5>Select a customer above to view their consumption trend</h5>
    </div>
@endif
@endsection
