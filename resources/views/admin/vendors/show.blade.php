@extends('layouts.app')

@section('title', 'Vendor Profile - Demo ERP')
@section('header_title', 'Vendor Profile')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-custom mb-4">
            <div class="card-body text-center">
                <div class="display-1 text-primary-custom mb-3">
                    <i class="bi bi-truck"></i>
                </div>
                <h4 class="fw-bold">{{ $vendor->company_name }}</h4>
                <p class="text-muted mb-1">{{ $vendor->vendor_code }}</p>
                <div class="mb-3">
                    @if($vendor->status)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-danger">Inactive</span>
                    @endif
                </div>
                <hr>
                <div class="text-start">
                    <p class="mb-1"><i class="bi bi-person me-2"></i> {{ $vendor->contact_person ?? 'N/A' }}</p>
                    <p class="mb-1"><i class="bi bi-telephone me-2"></i> {{ $vendor->mobile }}</p>
                    <p class="mb-1"><i class="bi bi-envelope me-2"></i> {{ $vendor->email ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card card-custom">
            <div class="card-header bg-white border-bottom">
                <ul class="nav nav-tabs card-header-tabs" id="vendorTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="profile-tab" data-bs-toggle="tab" href="#profile" role="tab">Profile Details</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="purchase-tab" data-bs-toggle="tab" href="#purchase" role="tab">Purchase History</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="rates-tab" data-bs-toggle="tab" href="#rates" role="tab">Rate History</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="vendorTabsContent">
                    <div class="tab-pane fade show active" id="profile" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">GST Number</div>
                            <div class="col-sm-8 fw-semibold">{{ $vendor->gst_no ?? 'N/A' }}</div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Lead Time</div>
                            <div class="col-sm-8 fw-semibold">{{ $vendor->lead_time_days }} Days</div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('vendors.edit', $vendor->id) }}" class="btn btn-warning text-white"><i class="bi bi-pencil me-1"></i> Edit Profile</a>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="purchase" role="tabpanel">
                        @if($purchases->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>PO Number</th>
                                        <th>Date</th>
                                        <th>Total Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchases as $purchase)
                                    <tr>
                                        <td>{{ $purchase->po_number }}</td>
                                        <td>{{ \Carbon\Carbon::parse($purchase->po_date)->format('d M Y') }}</td>
                                        <td>₹{{ number_format($purchase->total_amount, 2) }}</td>
                                        <td>
                                            @if($purchase->status === 'Draft')
                                                <span class="badge bg-secondary">Draft</span>
                                            @elseif($purchase->status === 'Approved')
                                                <span class="badge bg-primary">Approved</span>
                                            @elseif($purchase->status === 'Completed')
                                                <span class="badge bg-success">Completed</span>
                                            @else
                                                <span class="badge bg-dark">{{ $purchase->status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('purchases.show', $purchase->id) }}" class="btn btn-sm btn-info text-white"><i class="bi bi-eye"></i> View</a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center p-5 text-muted">
                            <i class="bi bi-cart-x display-4 mb-3"></i>
                            <h5>No Purchases Yet</h5>
                            <p>There is no purchase history for this vendor.</p>
                        </div>
                        @endif
                    </div>
                    <div class="tab-pane fade" id="rates" role="tabpanel">
                        @if(count($rateHistory) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product Name</th>
                                        <th>PO Number</th>
                                        <th>Date</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rateHistory as $rate)
                                    <tr>
                                        <td>{{ $rate['product_name'] }}</td>
                                        <td>{{ $rate['po_number'] }}</td>
                                        <td>{{ \Carbon\Carbon::parse($rate['po_date'])->format('d M Y') }}</td>
                                        <td>{{ $rate['quantity'] }}</td>
                                        <td>₹{{ number_format($rate['unit_price'], 2) }}</td>
                                        <td>₹{{ number_format($rate['total_price'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center p-5 text-muted">
                            <i class="bi bi-graph-down display-4 mb-3"></i>
                            <h5>No Rate History</h5>
                            <p>No items have been purchased from this vendor yet.</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
