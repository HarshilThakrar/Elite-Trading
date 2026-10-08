@extends('layouts.app')

@section('title', 'Customer Profile - Demo ERP')
@section('header_title', 'Customer Profile')

@section('content')
<style>
    .profile-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }
    .profile-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    }
    .icon-wrapper {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        color: #fff;
        font-size: 2.5rem;
        box-shadow: 0 4px 15px rgba(161, 140, 209, 0.4);
    }
    .info-row {
        padding: 16px 10px;
        border-bottom: 1px solid #f1f1f1;
        transition: all 0.2s ease;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-row:hover {
        background-color: #f8f9fa;
        border-radius: 8px;
        transform: scale(1.01);
    }
    .custom-tabs {
        border-bottom: 2px solid #f1f1f1;
    }
    .custom-tabs .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 500;
        padding: 12px 20px;
        position: relative;
        transition: all 0.3s ease;
        background: transparent;
    }
    .custom-tabs .nav-link:hover {
        color: #333;
    }
    .custom-tabs .nav-link.active {
        color: #667eea;
        background: transparent;
        font-weight: 600;
    }
    .custom-tabs .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        border-radius: 3px 3px 0 0;
    }
    .action-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: #fff;
        font-weight: 500;
        padding: 10px 24px;
        border-radius: 30px;
        transition: all 0.3s ease;
    }
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: #fff;
    }
    .contact-item-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        font-size: 1.2rem;
    }
</style>

<div class="row g-4">
    <!-- Left Column: Profile Summary -->
    <div class="col-md-4">
        <div class="card profile-card h-100">
            <div class="card-body text-center p-4">
                <div class="icon-wrapper mb-4">
                    <i class="ph ph-buildings"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">{{ $customer->company_name }}</h4>
                <p class="text-muted small fw-medium mb-3" style="letter-spacing: 1px;">{{ $customer->customer_code }}</p>
                
                <div class="mb-4">
                    @if($customer->status)
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill shadow-sm">
                            <i class="ph-fill ph-check-circle me-1"></i> Active
                        </span>
                    @else
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2 rounded-pill shadow-sm">
                            <i class="ph-fill ph-x-circle me-1"></i> Inactive
                        </span>
                    @endif
                </div>
                
                <hr class="opacity-10 my-4">
                
                <div class="text-start px-2">
                    <div class="d-flex align-items-center mb-3">
                        <div class="contact-item-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="ph ph-user"></i>
                        </div>
                        <div>
                            <p class="text-muted small mb-0">Contact Person</p>
                            <p class="fw-semibold text-dark mb-0">{{ $customer->contact_person ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="contact-item-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="ph ph-phone"></i>
                        </div>
                        <div>
                            <p class="text-muted small mb-0">Mobile Number</p>
                            <p class="fw-semibold text-dark mb-0">{{ $customer->mobile }}</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="contact-item-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="ph ph-envelope-simple"></i>
                        </div>
                        <div>
                            <p class="text-muted small mb-0">Email Address</p>
                            <p class="fw-semibold text-dark mb-0">{{ $customer->email ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Tabs & Details -->
    <div class="col-md-8">
        <div class="card profile-card h-100">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4 rounded-top-4">
                <ul class="nav nav-tabs custom-tabs" id="customerTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                            <i class="ph ph-identification-card me-1"></i> Profile Details
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                            <i class="ph ph-chart-bar me-1"></i> Purchases & Stats
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button" role="tab">
                            <i class="ph ph-receipt me-1"></i> Invoices
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="quotations-tab" data-bs-toggle="tab" data-bs-target="#quotations" type="button" role="tab">
                            <i class="ph ph-file-text me-1"></i> Quotations
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body p-4">
                <div class="tab-content" id="customerTabsContent">
                    <div class="tab-pane fade show active" id="profile" role="tabpanel">
                        <div class="info-row row m-0">
                            <div class="col-sm-4 text-muted d-flex align-items-center"><i class="ph ph-receipt me-2 fs-5"></i> GST Number</div>
                            <div class="col-sm-8 fw-bold text-dark d-flex align-items-center">{{ $customer->gst_no ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row row m-0">
                            <div class="col-sm-4 text-muted d-flex align-items-center"><i class="ph ph-map-pin me-2 fs-5"></i> Address</div>
                            <div class="col-sm-8 fw-semibold text-dark d-flex align-items-center">{{ $customer->address ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row row m-0">
                            <div class="col-sm-4 text-muted d-flex align-items-center"><i class="ph ph-city me-2 fs-5"></i> City / State</div>
                            <div class="col-sm-8 fw-semibold text-dark d-flex align-items-center">{{ $customer->city ?? 'N/A' }}, {{ $customer->state ?? 'N/A' }} - <span class="text-primary ms-1">{{ $customer->pincode }}</span></div>
                        </div>
                        <div class="info-row row m-0">
                            <div class="col-sm-4 text-muted d-flex align-items-center"><i class="ph ph-credit-card me-2 fs-5"></i> Credit Limit</div>
                            <div class="col-sm-8 fw-bold text-danger fs-5 d-flex align-items-center">₹ {{ number_format($customer->credit_limit, 2) }}</div>
                        </div>
                        <div class="info-row row m-0">
                            <div class="col-sm-4 text-muted d-flex align-items-center"><i class="ph ph-handshake me-2 fs-5"></i> Payment Terms</div>
                            <div class="col-sm-8 fw-semibold text-dark d-flex align-items-center">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-2">
                                    {{ $customer->payment_terms ?? 'N/A' }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-3 text-end">
                            <a href="{{ route('customers.edit', $customer->id) }}" class="btn action-btn shadow-sm text-decoration-none">
                                <i class="ph-bold ph-pencil-simple me-2"></i> Edit Profile Details
                            </a>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="overview" role="tabpanel">
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="p-3 bg-primary bg-opacity-10 rounded-3 border border-primary border-opacity-25 text-center h-100">
                                    <p class="text-muted mb-1 small fw-semibold text-uppercase">Total Revenue</p>
                                    <h4 class="fw-bold text-primary mb-0">₹ {{ number_format($totalRevenue, 2) }}</h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-warning bg-opacity-10 rounded-3 border border-warning border-opacity-25 text-center h-100">
                                    <p class="text-muted mb-1 small fw-semibold text-uppercase">Top Report Rank</p>
                                    <h4 class="fw-bold text-warning mb-0">#{{ $customerRank }}</h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-info bg-opacity-10 rounded-3 border border-info border-opacity-25 text-center h-100 d-flex flex-column justify-content-center">
                                    <p class="text-muted mb-1 small fw-semibold text-uppercase">Most Ordered Item</p>
                                    <h6 class="fw-bold text-info mb-0 text-truncate" title="{{ $topItemName }}">{{ $topItemName }}</h6>
                                    <small class="text-muted">{{ $topItemPartCode }}</small>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3 text-dark">Purchased Items History</h6>
                        @if(count($itemsBought) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover border">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product Name</th>
                                            <th class="text-center">Quantity Bought</th>
                                            <th class="text-end">Total Spent (₹)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($itemsBought as $item)
                                            <tr>
                                                <td>{{ $item['product']->item_name ?? 'Unknown Product' }}</td>
                                                <td class="text-center fw-bold">{{ $item['quantity'] }}</td>
                                                <td class="text-end">{{ number_format($item['total_spent'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted border rounded-3 bg-light">
                                <i class="ph ph-shopping-cart fs-1 text-secondary mb-2"></i>
                                <p class="mb-0">No purchase history available yet.</p>
                            </div>
                        @endif
                    </div>
                    
                    <div class="tab-pane fade" id="invoices" role="tabpanel">
                        <h6 class="fw-bold mb-3 text-dark">Sales Invoices</h6>
                        @if($customer->sales->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover border">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Invoice No</th>
                                            <th class="text-end">Amount (₹)</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($customer->sales->sortByDesc('sale_date') as $sale)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</td>
                                                <td class="fw-bold">{{ $sale->invoice_number }}</td>
                                                <td class="text-end fw-bold">{{ number_format($sale->total_amount, 2) }}</td>
                                                <td>
                                                    @if($sale->status == 'Draft')
                                                        <span class="badge bg-secondary text-white">Draft</span>
                                                    @elseif($sale->status == 'Approved')
                                                        <span class="badge bg-primary text-white">Approved</span>
                                                    @elseif($sale->status == 'Dispatched')
                                                        <span class="badge bg-success text-white">Dispatched</span>
                                                    @else
                                                        <span class="badge bg-danger text-white">Cancelled</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-sm btn-light py-0"><i class="bi bi-eye"></i> View</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted border rounded-3 bg-light">
                                <i class="ph ph-receipt fs-1 text-secondary mb-2"></i>
                                <p class="mb-0">No invoices generated yet.</p>
                            </div>
                        @endif
                    </div>

                    <div class="tab-pane fade" id="quotations" role="tabpanel">
                        <h6 class="fw-bold mb-3 text-dark">Quotations</h6>
                        @if($customer->quotations->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover border">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Quotation No</th>
                                            <th class="text-end">Amount (₹)</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($customer->quotations->sortByDesc('quotation_date') as $quotation)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($quotation->quotation_date)->format('d M Y') }}</td>
                                                <td class="fw-bold"><a href="{{ route('quotations.edit', $quotation->id) }}" class="text-decoration-none">{{ $quotation->quotation_number }}</a></td>
                                                <td class="text-end fw-bold">{{ number_format($quotation->total_amount, 2) }}</td>
                                                <td>
                                                    @if($quotation->status == 'Draft')
                                                        <span class="badge bg-secondary text-white">Draft</span>
                                                    @elseif($quotation->status == 'Sent')
                                                        <span class="badge bg-info text-white">Sent</span>
                                                    @elseif($quotation->status == 'Approved')
                                                        <span class="badge bg-success text-white">Approved</span>
                                                    @else
                                                        <span class="badge bg-danger text-white">Rejected</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('quotations.show', $quotation->id) }}" target="_blank" class="btn btn-sm btn-light py-0"><i class="bi bi-eye"></i> View</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted border rounded-3 bg-light">
                                <i class="ph ph-file-text fs-1 text-secondary mb-2"></i>
                                <p class="mb-0">No quotations generated yet.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
