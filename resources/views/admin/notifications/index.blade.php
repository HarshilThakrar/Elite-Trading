@extends('layouts.app')

@section('title', 'All Notifications - Demo ERP')
@section('header_title', 'Action Center & Notifications')

@section('content')
<div class="row">
    <!-- Low Stock Alerts -->
    <div class="col-md-12 mb-4">
        <div class="card card-custom border-danger border-start border-0 border-end-0 border-top-0 border-bottom-0" style="border-left-width: 4px !important;">
            <div class="card-header bg-white p-3 border-bottom d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-4 me-2"></i>
                <h5 class="m-0 fw-bold text-danger">Low Stock Alerts ({{ $lowStockProducts->count() }})</h5>
            </div>
            <div class="card-body p-0">
                @if($lowStockProducts->count() > 0)
                <div class="data-table">
                    <table class="data-table">
                        <thead class="data-table">
                            <tr>
                                <th>Product Code</th>
                                <th>Product Name</th>
                                <th class="text-center">Current Quantity</th>
                                <th class="text-center">Reorder Level</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockProducts as $product)
                            <tr>
                                <td class="fw-bold">{{ $product->part_code }}</td>
                                <td>{{ $product->item_name }}</td>
                                <td class="text-center fw-bold text-danger">{{ $product->available_stock }} {{ $product->unit }}</td>
                                <td class="text-center">{{ $product->reorder_level }} {{ $product->unit }}</td>
                                <td><a href="{{ route('purchases.create') }}" class="btn btn-sm btn-outline-danger">Create PO</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="p-4 text-center text-muted">
                    No low stock alerts at this time.
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Pending Sales -->
    <div class="col-md-6 mb-4">
        <div class="card card-custom border-primary border-start border-0 border-end-0 border-top-0 border-bottom-0" style="border-left-width: 4px !important;">
            <div class="card-header bg-white p-3 border-bottom d-flex align-items-center">
                <i class="bi bi-cart text-primary fs-4 me-2"></i>
                <h5 class="m-0 fw-bold text-primary">Pending Sales to Dispatch ({{ $pendingSales->count() }})</h5>
            </div>
            <div class="card-body p-0">
                @if($pendingSales->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($pendingSales as $sale)
                    <a href="{{ route('sales.show', $sale->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
                        <div>
                            <h6 class="mb-1 fw-bold">{{ $sale->invoice_number }}</h6>
                            <small class="text-muted">{{ $sale->customer->company_name ?? 'N/A' }} | Date: {{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</small>
                        </div>
                        <span class="badge bg-primary text-white rounded-pill">View</span>
                    </a>
                    @endforeach
                </div>
                @else
                <div class="p-4 text-center text-muted">
                    No pending sales to dispatch.
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Pending Purchases -->
    <div class="col-md-6 mb-4">
        <div class="card card-custom border-warning border-start border-0 border-end-0 border-top-0 border-bottom-0" style="border-left-width: 4px !important;">
            <div class="card-header bg-white p-3 border-bottom d-flex align-items-center">
                <i class="bi bi-truck text-warning fs-4 me-2"></i>
                <h5 class="m-0 fw-bold text-warning">Pending POs to Receive ({{ $pendingPurchases->count() }})</h5>
            </div>
            <div class="card-body p-0">
                @if($pendingPurchases->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($pendingPurchases as $purchase)
                    <a href="{{ route('purchases.show', $purchase->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
                        <div>
                            <h6 class="mb-1 fw-bold">{{ $purchase->po_number }}</h6>
                            <small class="text-muted">{{ $purchase->vendor->company_name ?? 'N/A' }} | Date: {{ \Carbon\Carbon::parse($purchase->po_date)->format('d M Y') }}</small>
                        </div>
                        <span class="badge bg-warning text-dark rounded-pill">View</span>
                    </a>
                    @endforeach
                </div>
                @else
                <div class="p-4 text-center text-muted">
                    No pending purchase orders.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
