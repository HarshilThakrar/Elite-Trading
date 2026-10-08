@extends('layouts.app')

@section('title', 'Product Details - Demo ERP')
@section('header_title', 'Product Details')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-custom mb-4">
            <div class="card-body text-center">
                <div class="display-1 text-primary-custom mb-3">
                    <i class="bi bi-box-seam"></i>
                </div>
                <h4 class="fw-bold">{{ $product->item_name }}</h4>
                <p class="text-muted mb-1">{{ $product->part_code }}</p>
                <div class="mb-3 d-flex justify-content-center gap-2">
                    @if($product->status)
                        <span class="badge" style="background-color: #dcfce7; color: #166534; border: 1px solid #86efac;">Active</span>
                    @else
                        <span class="badge" style="background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;">Inactive</span>
                    @endif
                    
                    @if($product->abc_category)
                        @if($product->abc_category == 'A')
                            <span class="badge" style="background-color: #d1fae5; color: #065f46; border: 1px solid #6ee7b7;" title="Fast Moving">Class A</span>
                        @elseif($product->abc_category == 'B')
                            <span class="badge" style="background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d;" title="Medium Moving">Class B</span>
                        @else
                            <span class="badge" style="background-color: #f3f4f6; color: #374151; border: 1px solid #d1d5db;" title="Slow Moving">Class C</span>
                        @endif
                    @endif
                </div>
                <hr>
                <div class="text-start">
                    @php
                        $physical = $product->available_stock + $product->reserved_stock;
                    @endphp
                    <p class="mb-1"><strong>Physical Stock:</strong> <span class="fw-bold">{{ $physical }}</span></p>
                    <p class="mb-1"><strong>Available to Sell:</strong> <span class="fw-bold text-success">{{ $product->available_stock }}</span></p>
                    <p class="mb-1"><strong>Reserved Stock:</strong> <span class="fw-bold text-muted">{{ $product->reserved_stock }}</span></p>
                    <p class="mb-1"><strong>Unit:</strong> {{ $product->unit }}</p>
                    <p class="mb-1 text-danger fw-bold"><strong>LP Price:</strong> ₹ {{ number_format($product->lp_price, 2) }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card card-custom">
            <div class="card-header bg-white border-bottom">
                <ul class="nav nav-tabs card-header-tabs" id="productTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="details-tab" data-bs-toggle="tab" href="#details" role="tab">Inventory Details</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="stock-tab" data-bs-toggle="tab" href="#stock" role="tab">Stock Movement</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="lp-history-tab" data-bs-toggle="tab" href="#lp-history" role="tab">LP History</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="productTabsContent">
                    <div class="tab-pane fade show active" id="details" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Description</div>
                            <div class="col-sm-8">{{ $product->description ?? 'No description available.' }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Minimum Stock</div>
                            <div class="col-sm-8 fw-semibold">{{ $product->minimum_stock }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Reorder Level</div>
                            <div class="col-sm-8 fw-semibold">{{ $product->reorder_level }}</div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-warning text-white"><i class="bi bi-pencil me-1"></i> Edit Product</a>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="stock" role="tabpanel">
                        @if(isset($stockMovements) && $stockMovements->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Quantity</th>
                                            <th>Balance</th>
                                            <th>Reference</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($stockMovements as $movement)
                                        <tr>
                                            <td>{{ $movement->created_at->format('d M Y, h:i A') }}</td>
                                            <td>
                                                @if($movement->type == 'IN')
                                                    <span class="badge bg-success text-white">IN</span>
                                                @elseif($movement->type == 'OUT' || str_contains(strtolower($movement->type), 'out') || str_contains(strtolower($movement->type), 'pending'))
                                                    <span class="badge bg-danger text-white">{{ strtoupper($movement->type) }}</span>
                                                @else
                                                    <span class="badge bg-secondary text-white">{{ strtoupper($movement->type) }}</span>
                                                @endif
                                            </td>
                                            <td class="fw-bold {{ $movement->type == 'IN' ? 'text-success' : 'text-danger' }}">
                                                {{ $movement->type == 'IN' ? '+' : '-' }}{{ $movement->quantity }}
                                            </td>
                                            <td class="fw-bold">{{ $movement->balance_after }}</td>
                                            <td>
                                                {{ $movement->reference_type ? class_basename($movement->reference_type) . ' #' . $movement->reference_id : 'Manual' }}
                                            </td>
                                            <td>{{ $movement->notes }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center p-5 text-muted">
                                <i class="bi bi-arrow-left-right display-4 mb-3"></i>
                                <h5>No Stock Movement</h5>
                                <p>There is no inventory history for this product yet.</p>
                            </div>
                        @endif
                    </div>
                    <div class="tab-pane fade" id="lp-history" role="tabpanel">
                        @if($product->lpHistories && $product->lpHistories->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Old Price</th>
                                            <th>New Price</th>
                                            <th>Changed By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($product->lpHistories as $history)
                                        <tr>
                                            <td>
                                                <span class="fw-bold">{{ $history->created_at->format('d M Y') }}</span><br>
                                                <small class="text-muted">{{ $history->created_at->format('h:i A') }}</small>
                                            </td>
                                            <td class="text-danger fw-bold">₹ {{ number_format($history->old_price, 2) }}</td>
                                            <td class="text-success fw-bold">₹ {{ number_format($history->new_price, 2) }}</td>
                                            <td>
                                                @if($history->user)
                                                    {{ $history->user->name }}
                                                @else
                                                    <span class="text-muted font-italic">System</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center p-5 text-muted">
                                <i class="bi bi-tag display-4 mb-3"></i>
                                <h5>No LP History</h5>
                                <p>The list price for this product has not been changed yet.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
