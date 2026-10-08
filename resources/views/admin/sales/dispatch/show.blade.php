@extends('layouts.app')

@section('title', 'View Delivery Note - Demo ERP')
@section('header_title', 'Delivery Note Details')

@section('content')
<div class="row">
    <div class="col-md-9">
        <div class="card card-custom">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold text-primary-custom">Delivery Note: {{ $dispatch->dispatch_number }}</h5>
                <span class="badge bg-success">{{ $dispatch->status }}</span>
            </div>
            
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted fw-bold mb-2">Customer Info</h6>
                        <p class="mb-1 fw-bold fs-5">{{ optional($dispatch->sale->customer)->company_name ?? 'N/A' }}</p>
                        <p class="mb-1 text-muted">{{ optional($dispatch->sale->customer)->contact_person }}</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h6 class="text-muted fw-bold mb-2">Dispatch Details</h6>
                        <p class="mb-1"><strong>Dispatch Date:</strong> {{ \Carbon\Carbon::parse($dispatch->dispatch_date)->format('d M Y') }}</p>
                        <p class="mb-1"><strong>Related SO:</strong> <a href="{{ route('sales.show', $dispatch->sale_id) }}">{{ optional($dispatch->sale)->invoice_number }}</a></p>
                    </div>
                </div>

                <h6 class="fw-bold mb-3 border-bottom pb-2">Dispatched Items</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product Details</th>
                                <th class="text-center">Batch No</th>
                                <th class="text-center">Dispatched Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dispatch->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <span class="fw-bold">{{ optional($item->product)->part_code }}</span><br>
                                    <small class="text-muted">{{ optional($item->product)->item_name }}</small>
                                </td>
                                <td class="text-center">{{ $item->batch_no ?? '-' }}</td>
                                <td class="text-center fw-bold text-success">{{ $item->dispatched_qty }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($dispatch->notes)
                <div class="mt-4 p-3 bg-light rounded">
                    <h6 class="fw-bold mb-2">Dispatch Notes</h6>
                    <p class="mb-0 text-muted">{{ $dispatch->notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-custom sticky-top" style="top: 20px;">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="mb-0 fw-bold">Actions</h6>
            </div>
            <div class="card-body">
                <a href="{{ route('dispatch.pdf', $dispatch->id) }}" class="btn btn-light w-100 mb-3 text-start"><i class="bi bi-printer me-2"></i> Print Delivery Note</a>
                <a href="{{ route('sales.show', $dispatch->sale_id) }}" class="btn btn-primary w-100 mb-3 text-start"><i class="bi bi-arrow-left me-2"></i> Back to Sales Order</a>
            </div>
        </div>
    </div>
</div>
@endsection
