@extends('layouts.app')

@section('title', 'Dead Stock Analysis - Demo ERP')
@section('header_title', 'Dead Stock Analysis')

@section('content')
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card card-custom bg-danger text-white">
            <div class="card-body text-center">
                <h6 class="text-uppercase fw-bold opacity-75">Dead Stock Items</h6>
                <h2 class="display-5 fw-bold mb-0">{{ count($deadStockData) }}</h2>
                <div class="mt-2 small opacity-75">Products inactive for 90+ days</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom bg-dark text-white">
            <div class="card-body text-center">
                <h6 class="text-uppercase fw-bold opacity-75">Capital Tied Up</h6>
                <h2 class="display-5 fw-bold mb-0">₹ {{ number_format($totalTiedUpCapital, 2) }}</h2>
                <div class="mt-2 small opacity-75">Total frozen value</div>
            </div>
        </div>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="m-0 fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Dead Stock Inventory</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Product Details</th>
                        <th class="text-center">Physical Stock</th>
                        <th class="text-center">Last Movement Date</th>
                        <th class="text-center">Days Inactive</th>
                        <th class="text-center">Capital Tied Up (₹)</th>
                        <th>Previous Customers</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deadStockData as $data)
                    <tr>
                        <td>
                            <span class="fw-bold">{{ $data['product']->part_code }}</span><br>
                            <small class="text-muted">{{ $data['product']->item_name }}</small>
                        </td>
                        <td class="text-center fw-bold">{{ $data['physical_stock'] }}</td>
                        <td class="text-center text-muted">
                            {{ \Carbon\Carbon::parse($data['last_movement_date'])->format('d M Y') }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-danger fs-6">{{ $data['days_inactive'] }} Days</span>
                        </td>
                        <td class="text-center fw-bold text-danger">
                            {{ number_format($data['tied_up_capital'], 2) }}
                        </td>
                        <td>
                            @if($data['previous_customers'] && $data['previous_customers']->count() > 0)
                                <ul class="list-unstyled mb-0 small">
                                    @foreach($data['previous_customers']->take(3) as $customer)
                                        <li>• {{ $customer->company_name }}</li>
                                    @endforeach
                                    @if($data['previous_customers']->count() > 3)
                                        <li class="text-muted fst-italic">+{{ $data['previous_customers']->count() - 3 }} more</li>
                                    @endif
                                </ul>
                            @else
                                <span class="text-muted small">No previous sales</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('products.show', $data['product']->id) }}" class="btn btn-sm btn-outline-secondary">View Product</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-3"></i>
                            <h5>No Dead Stock Found!</h5>
                            <p>All your inventory is moving nicely.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
