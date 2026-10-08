@extends('layouts.app')

@section('title', 'Export & Sharing - Demo ERP')
@section('header_title', 'Data Export & Sharing')

@section('content')
<div class="row">
    <!-- Sales Export -->
    <div class="col-12 col-md-6 col-lg-4 mb-4">
        <div class="card card-custom h-100 shadow-sm border-0">
            <div class="card-body text-center p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="mb-3">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-3 text-primary fs-2">
                            <i class="ph ph-receipt"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-2">Sales Data</h5>
                    <p class="text-muted small mb-4">Export a complete report of all sales, invoices, and payment statuses.</p>
                </div>
                <a href="{{ route('export.sales') }}" class="btn btn-primary-custom w-100">
                    <i class="ph ph-file-xls me-2"></i> Export to Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Purchases Export -->
    <div class="col-12 col-md-6 col-lg-4 mb-4">
        <div class="card card-custom h-100 shadow-sm border-0">
            <div class="card-body text-center p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="mb-3">
                        <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex p-3 text-danger fs-2">
                            <i class="ph ph-shopping-cart"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-2">Purchases Data</h5>
                    <p class="text-muted small mb-4">Export all purchase orders, vendor details, and procurement history.</p>
                </div>
                <a href="{{ route('export.purchases') }}" class="btn btn-outline-danger w-100">
                    <i class="ph ph-file-xls me-2"></i> Export to Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Products Inventory Export -->
    <div class="col-12 col-md-6 col-lg-4 mb-4">
        <div class="card card-custom h-100 shadow-sm border-0">
            <div class="card-body text-center p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="mb-3">
                        <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex p-3 text-warning fs-2">
                            <i class="ph ph-package"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-2">Inventory Status</h5>
                    <p class="text-muted small mb-4">Download complete product catalog with current stock quantities.</p>
                </div>
                <a href="{{ route('export.products') }}" class="btn btn-outline-warning w-100 text-dark">
                    <i class="ph ph-file-xls me-2"></i> Export to Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Customers Export -->
    <div class="col-12 col-md-6 col-lg-4 mb-4">
        <div class="card card-custom h-100 shadow-sm border-0">
            <div class="card-body text-center p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="mb-3">
                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex p-3 text-success fs-2">
                            <i class="ph ph-users"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-2">Customer Directory</h5>
                    <p class="text-muted small mb-4">Export your complete customer database for marketing and sharing.</p>
                </div>
                <a href="{{ route('export.customers') }}" class="btn btn-outline-success w-100">
                    <i class="ph ph-file-xls me-2"></i> Export to Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Vendors Export -->
    <div class="col-12 col-md-6 col-lg-4 mb-4">
        <div class="card card-custom h-100 shadow-sm border-0">
            <div class="card-body text-center p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="mb-3">
                        <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex p-3 text-info fs-2">
                            <i class="ph ph-storefront"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-2">Vendor Directory</h5>
                    <p class="text-muted small mb-4">Export all approved vendors, contact persons, and supply lead times.</p>
                </div>
                <a href="{{ route('export.vendors') }}" class="btn btn-outline-info w-100">
                    <i class="ph ph-file-xls me-2"></i> Export to Excel
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
