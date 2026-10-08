@extends('layouts.app')

@section('title', 'Create Delivery Note - Demo ERP')
@section('header_title', 'Create Delivery Note')

@section('content')
<div class="card card-custom">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="m-0 fw-bold text-success"><i class="bi bi-truck me-2"></i> Dispatch Items for SO: {{ $sale->invoice_number }}</h5>
        <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-outline-secondary btn-sm">Back to Sale</a>
    </div>
    
    <form action="{{ route('sales.dispatch.store', $sale->id) }}" method="POST" id="dispatchForm">
        @csrf
        <div class="card-body">

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Customer</label>
                    <input type="text" class="form-control bg-light" value="{{ optional($sale->customer)->company_name }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Dispatch Date <span class="text-danger">*</span></label>
                    <input type="date" name="dispatch_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>

            <h6 class="fw-bold mb-3">Items to Dispatch</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Ordered</th>
                            <th class="text-center">Previously Dispatched</th>
                            <th class="text-center">Pending</th>
                            <th class="text-center" style="width: 150px;">Dispatch Qty Today <span class="text-danger">*</span></th>
                            <th class="text-center" style="width: 200px;">Batch No (Optional)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->items as $index => $item)
                        @php
                            $pending = $item->quantity - $item->dispatched_qty;
                            $available = optional($item->product)->available_stock ?? 0;
                            $defaultDispatchQty = max(0, min($pending, $available));
                        @endphp
                        <tr>
                            <td>
                                <span class="fw-bold">{{ optional($item->product)->part_code }}</span><br>
                                <small class="text-muted">{{ optional($item->product)->item_name }}</small>
                            </td>
                            <td class="text-center fw-bold">{{ $item->quantity }}</td>
                            <td class="text-center text-success">{{ $item->dispatched_qty }}</td>
                            <td class="text-center text-danger fw-bold">{{ $pending }}</td>
                            <td>
                                @if($pending > 0)
                                    <input type="number" name="items[{{ $index }}][dispatched_qty]" class="form-control text-center border-success" max="{{ $pending }}" min="0" step="0.01" value="{{ old('items.'.$index.'.dispatched_qty', $defaultDispatchQty) }}">
                                @else
                                    <span class="badge bg-success">Fully Dispatched</span>
                                @endif
                            </td>
                            <td>
                                @if($pending > 0)
                                    <input type="text" name="items[{{ $index }}][batch_no]" class="form-control text-center" placeholder="e.g. BATCH-001">
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mb-3 mt-4">
                <label class="form-label fw-bold">Dispatch Notes / Vehicle Details</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Enter driver name, vehicle number, or other dispatch details..."></textarea>
            </div>
        </div>
        <div class="card-footer bg-light d-flex justify-content-end p-3">
            <button type="submit" class="btn btn-success fw-bold px-4"><i class="bi bi-check-circle me-2"></i> Confirm Dispatch & Deduct Inventory</button>
        </div>
    </form>
</div>
@endsection

