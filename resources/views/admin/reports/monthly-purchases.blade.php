@extends('layouts.app')

@section('title', 'Monthly Purchases Report')
@section('header_title', 'Monthly Purchases')

@section('content')
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="ph-bold ph-currency-inr fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-medium">Total Historical Purchases</div>
                    <div class="fs-5 fw-bold text-dark">₹{{ number_format(collect($monthlyData)->sum('total_purchases'), 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="ph-bold ph-receipt fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-medium">Total Purchase Orders</div>
                    <div class="fs-5 fw-bold text-dark">{{ number_format(collect($monthlyData)->sum('total_orders')) }} POs</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="rounded-circle bg-info bg-opacity-10 text-info p-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="ph-bold ph-package fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-medium">Total Units Purchased</div>
                    <div class="fs-5 fw-bold text-dark">{{ number_format(collect($monthlyData)->sum('total_quantity')) }} Units</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print-Only Header (Visible only when Printing/PDF) -->
<div class="print-only mb-3">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
        <div>
            <h3 class="fw-bold mb-0 text-dark">Demo ERP System</h3>
            <div class="text-muted small">Historical Monthly Purchases Breakdown Report</div>
        </div>
        <div class="text-end small">
            <div><strong>Generated on:</strong> {{ now()->format('d M Y, h:i A') }}</div>
        </div>
    </div>
</div>

<div class="card card-custom border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="card-title m-0 fw-bold text-dark">Historical Monthly Purchases Breakdown</h5>
            <small class="text-muted">Procurement volume, items purchased, and expenditure per month</small>
        </div>
        <div class="d-flex gap-2 align-items-center print-hide">
            <button type="button" class="btn btn-sm btn-outline-secondary px-3" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
            <button type="button" class="btn btn-sm btn-danger text-white px-3" onclick="window.print()">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="py-3 px-4 border-bottom-0" style="width: 180px;">Month & Year</th>
                        <th class="py-3 px-4 border-bottom-0">Items Purchased</th>
                        <th class="py-3 text-center border-bottom-0" style="width: 140px;">Total Orders</th>
                        <th class="py-3 text-end px-4 border-bottom-0" style="width: 200px;">Total Purchases (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monthlyData as $index => $data)
                    <tr>
                        <td class="py-3 px-4 fw-semibold text-dark align-top">
                            <div class="d-flex align-items-center">
                                <i class="ph-fill ph-calendar-blank text-primary me-2 fs-5"></i>
                                <span>{{ $data['month_name'] }}</span>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            @if(!empty($data['items']) && count($data['items']) > 0)
                                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2.5 py-1.5 fw-bold">
                                        <i class="ph-fill ph-package me-1"></i> {{ number_format($data['total_quantity'], 0) }} Units
                                    </span>
                                    <span class="badge bg-light text-secondary border px-2 py-1">
                                        {{ $data['unique_items_count'] }} Unique Products
                                    </span>
                                </div>

                                {{-- Top 3 items --}}
                                <div class="list-group list-group-flush border-0">
                                    @foreach(array_slice($data['items'], 0, 3, true) as $itemName => $qty)
                                        <div class="py-1 px-0 d-flex justify-content-between align-items-center" style="font-size: 0.85rem; max-width: 550px;">
                                            <span class="text-truncate me-2 text-dark" title="{{ $itemName }}">
                                                <i class="ph ph-dot-outline text-primary me-1"></i> {{ $itemName }}
                                            </span>
                                            <span class="badge bg-light text-dark fw-bold border text-nowrap">
                                                {{ number_format($qty, 0) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- If more than 3 items, provide collapse toggle --}}
                                @if(count($data['items']) > 3)
                                    <div class="collapse mt-1" id="items-purchase-collapse-{{ $index }}">
                                        <div class="list-group list-group-flush border-top pt-1">
                                            @foreach(array_slice($data['items'], 3, null, true) as $itemName => $qty)
                                                <div class="py-1 px-0 d-flex justify-content-between align-items-center" style="font-size: 0.85rem; max-width: 550px;">
                                                    <span class="text-truncate me-2 text-muted" title="{{ $itemName }}">
                                                        <i class="ph ph-dot-outline text-secondary me-1"></i> {{ $itemName }}
                                                    </span>
                                                    <span class="badge bg-light text-muted fw-bold border text-nowrap">
                                                        {{ number_format($qty, 0) }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <button class="btn btn-link btn-sm p-0 text-decoration-none text-primary mt-1 fw-medium" 
                                            type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#items-purchase-collapse-{{ $index }}" 
                                            aria-expanded="false" 
                                            onclick="this.innerText = this.getAttribute('aria-expanded') === 'true' ? 'Show Less' : '+ View {{ count($data['items']) - 3 }} More Items'">
                                        + View {{ count($data['items']) - 3 }} More Items
                                    </button>
                                @endif
                            @else
                                <div class="text-muted small py-1 d-flex align-items-center">
                                    <span class="badge bg-light text-secondary border px-2 py-1">
                                        <i class="ph ph-info me-1"></i> No line-item details available
                                    </span>
                                </div>
                            @endif
                        </td>
                        <td class="py-3 text-center align-top">
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold fs-7">
                                {{ $data['total_orders'] }} Orders
                            </span>
                        </td>
                        <td class="py-3 text-end px-4 fw-bold text-danger fs-6 align-top">
                            ₹{{ number_format($data['total_purchases'], 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="ph ph-receipt mb-2 d-block text-black-50" style="font-size: 3rem;"></i>
                            No purchase data found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
