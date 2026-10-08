@extends('layouts.app')

@section('title', 'Export & Sharing - Demo ERP')
@section('header_title', 'Data Export & Sharing')

@section('content')
<div class="row">
    <!-- Sales Export -->
    <div class="col-md-6 mb-4">
        <div class="card card-custom h-100">
            <div class="card-body text-center p-5">
                <div class="mb-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-4 text-primary fs-1">
                        <i class="ph ph-receipt"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-3">Sales Data</h4>
                <p class="text-muted mb-4">Export a complete report of all sales, invoices, and payment statuses.</p>
                <a href="{{ route('export.sales') }}" class="btn btn-primary-custom w-100 mb-2">
                    <i class="ph ph-file-xls me-2"></i> Export to Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Purchases Export -->
    <div class="col-md-6 mb-4">
        <div class="card card-custom h-100">
            <div class="card-body text-center p-5">
                <div class="mb-4">
                    <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex p-4 text-danger fs-1">
                        <i class="ph ph-shopping-cart"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-3">Purchases Data</h4>
                <p class="text-muted mb-4">Export all purchase orders, vendor details, and procurement history.</p>
                <a href="{{ route('export.purchases') }}" class="btn btn-outline-danger w-100 mb-2">
                    <i class="ph ph-file-xls me-2"></i> Export to Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Products Inventory Export -->
    <div class="col-md-6 mb-4">
        <div class="card card-custom h-100">
            <div class="card-body text-center p-5">
                <div class="mb-4">
                    <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex p-4 text-warning fs-1">
                        <i class="ph ph-package"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-3">Inventory Status</h4>
                <p class="text-muted mb-4">Download complete product catalog with current stock quantities.</p>
                <a href="{{ route('export.products') }}" class="btn btn-outline-warning w-100 mb-2 text-dark">
                    <i class="ph ph-file-xls me-2"></i> Export to Excel
                </a>
            </div>
        </div>
    </div>

    <!-- Customers Export -->
    <div class="col-md-6 mb-4">
        <div class="card card-custom h-100">
            <div class="card-body text-center p-5">
                <div class="mb-4">
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex p-4 text-success fs-1">
                        <i class="ph ph-users"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-3">Customer Directory</h4>
                <p class="text-muted mb-4">Export your complete customer database for marketing and sharing.</p>
                <a href="{{ route('export.customers') }}" class="btn btn-outline-success w-100 mb-2">
                    <i class="ph ph-file-xls me-2"></i> Export to Excel
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
