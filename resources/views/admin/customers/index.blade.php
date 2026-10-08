@extends('layouts.app')

@section('title', 'Customers - Demo ERP')
@section('header_title', 'Customers')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('warning') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-x-circle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<style>
    /* Status Badge Responsive Styling */
    .customer-status-badge {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        gap: 5px;
        white-space: nowrap !important;
        font-size: 12px;
        font-weight: 600;
        padding: 5px 12px;
        border-radius: 50rem;
        transition: all 0.2s ease-in-out;
        cursor: pointer;
        user-select: none;
        line-height: 1.2;
    }
    .customer-status-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    }
    .customer-status-badge.badge-active {
        background-color: #ecfdf5 !important;
        color: #059669 !important;
        border: 1px solid rgba(16, 185, 129, 0.3) !important;
    }
    .customer-status-badge.badge-active:hover {
        background-color: #d1fae5 !important;
        color: #047857 !important;
    }
    .customer-status-badge.badge-inactive {
        background-color: #fef2f2 !important;
        color: #dc2626 !important;
        border: 1px solid rgba(239, 68, 68, 0.3) !important;
    }
    .customer-status-badge.badge-inactive:hover {
        background-color: #fee2e2 !important;
        color: #b91c1c !important;
    }

    /* Action Buttons Group */
    .action-btn-group {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        flex-wrap: nowrap;
        white-space: nowrap;
    }
    .action-btn-group .btn {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        flex-shrink: 0;
        transition: all 0.15s ease-in-out;
    }
    .action-btn-group .btn:hover {
        transform: translateY(-1px);
    }
    .action-btn-group form {
        display: inline-flex;
        margin: 0;
        padding: 0;
    }

    /* Status Filter Pills */
    .status-filter-pills .btn {
        font-size: 13px;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 500;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .status-filter-pills .btn.active {
        box-shadow: 0 2px 4px rgba(0,0,0,0.12);
    }

    /* DataTables Container & Scroll Styling */
    .dataTables_wrapper {
        width: 100% !important;
    }
    .dataTables_wrapper .dataTables_scroll {
        width: 100% !important;
        border-radius: 6px;
    }
    .dataTables_wrapper .dataTables_scrollHead {
        overflow: hidden !important;
        border-bottom: 1px solid #e9ecef;
    }
    .dataTables_wrapper .dataTables_scrollBody {
        width: 100% !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
        border-bottom: 1px solid #e9ecef;
    }
    .dataTables_wrapper .dataTables_length select {
        padding: 0.3rem 1.8rem 0.3rem 0.6rem;
        border-radius: 6px;
        border: 1px solid #ced4da;
    }
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 6px;
        padding: 0.35rem 0.75rem;
        border: 1px solid #ced4da;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        outline: none;
    }

    @media (max-width: 768px) {
        .card-header .btn-sm {
            font-size: 12px;
            padding: 4px 8px;
        }
        .status-filter-pills {
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 2px;
            -webkit-overflow-scrolling: touch;
        }
        .dataTables_wrapper .row:first-child {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 10px;
        }
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            width: 100% !important;
            text-align: left !important;
        }
        .dataTables_wrapper .dataTables_filter label {
            width: 100% !important;
            display: flex !important;
            align-items: center !important;
            gap: 6px;
            margin: 0 !important;
        }
        .dataTables_wrapper .dataTables_filter input {
            width: 100% !important;
            margin-left: 0 !important;
            flex: 1;
        }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            width: 100% !important;
            text-align: center !important;
            justify-content: center !important;
            display: flex !important;
            margin-top: 8px;
        }
        .dataTables_wrapper .dataTables_paginate ul.pagination {
            justify-content: center !important;
            flex-wrap: wrap;
        }
        .customer-status-badge {
            font-size: 11px;
            padding: 3px 8px;
        }
        .action-btn-group .btn {
            width: 28px;
            height: 28px;
            font-size: 12px;
        }
    }
</style>

<div class="card card-custom mb-4 border-0 shadow-sm">
    <div class="card-header bg-white p-3 border-bottom">
        <div class="row g-2 align-items-center">
            <!-- Title & Mobile Add Button -->
            <div class="col-12 col-md-auto d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-bold text-primary-custom d-flex align-items-center">
                    <i class="bi bi-people-fill me-2"></i> Customer List
                </h5>
                <a href="{{ route('customers.create') }}" class="btn btn-primary-custom btn-sm d-md-none">
                    <i class="bi bi-plus-lg me-1"></i> Add Customer
                </a>
            </div>

            <!-- Status Filter Pills -->
            <div class="col-12 col-md-auto">
                <div class="status-filter-pills btn-group" role="group" aria-label="Status Filter">
                    <button type="button" class="btn btn-outline-secondary btn-sm active" data-filter="all" onclick="filterByStatus('all', this)">
                        All <span class="badge bg-secondary ms-1">{{ $customers->count() }}</span>
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" data-filter="Active" onclick="filterByStatus('Active', this)">
                        Active <span class="badge bg-success ms-1">{{ $customers->where('status', 1)->count() }}</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-filter="Inactive" onclick="filterByStatus('Inactive', this)">
                        Inactive <span class="badge bg-danger ms-1">{{ $customers->where('status', 0)->count() }}</span>
                    </button>
                </div>
            </div>

            <!-- Action Buttons (Export, Import, Desktop Add) -->
            <div class="col-12 col-md text-md-end d-flex gap-2 justify-content-start justify-content-md-end flex-wrap">
                <a href="{{ route('export.customers') }}" class="btn btn-outline-success btn-sm" title="Export All Customers to Excel">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                </a>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#importExcelModal">
                    <i class="bi bi-file-earmark-arrow-up me-1"></i> Import Excel
                </button>
                <a href="{{ route('customers.create') }}" class="btn btn-primary-custom btn-sm d-none d-md-inline-flex">
                    <i class="bi bi-plus-lg me-1"></i> Add New Customer
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-3">
        <!-- Customer Table -->
        <table id="customersTable" class="table table-hover align-middle mb-0 w-100">
            <thead class="table-light">
                <tr>
                    <th style="width: 45px;" class="text-center text-nowrap">#</th>
                    <th class="d-none">Hidden ID</th>
                    <th class="text-nowrap" style="min-width: 100px;">Code</th>
                    <th class="text-nowrap" style="min-width: 180px;">Company Name</th>
                    <th class="text-nowrap" style="min-width: 140px;">Contact Person</th>
                    <th class="text-nowrap" style="min-width: 120px;">Mobile</th>
                    <th class="text-nowrap" style="min-width: 110px;">City</th>
                    <th class="text-center text-nowrap" style="min-width: 110px;">Status</th>
                    <th class="text-center text-nowrap" style="width: 130px; min-width: 130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                <tr>
                    <td class="fw-bold text-center text-muted"></td>
                    <td class="d-none">{{ $customer->id }}</td>
                    <td class="text-nowrap fw-semibold font-monospace">{{ $customer->customer_code }}</td>
                    <td>
                        <a href="{{ route('customers.show', $customer->id) }}" class="fw-semibold text-primary-custom text-decoration-none d-block">
                            {{ $customer->company_name }}
                        </a>
                    </td>
                    <td class="text-nowrap">{{ $customer->contact_person ?: '-' }}</td>
                    <td class="text-nowrap">
                        @if($customer->mobile)
                            <a href="tel:{{ $customer->mobile }}" class="text-decoration-none text-dark">
                                <i class="bi bi-telephone text-muted me-1 small"></i>{{ $customer->mobile }}
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-nowrap">{{ $customer->city ?: '-' }}</td>
                    <td class="text-center text-nowrap">
                        <form action="{{ route('customers.toggleStatus', $customer->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to {{ $customer->status ? 'mark this customer as inactive' : 'activate this customer' }}?');">
                            @csrf
                            <button type="submit" class="border-0 bg-transparent p-0" title="Click to {{ $customer->status ? 'Deactivate' : 'Activate' }}">
                                @if($customer->status)
                                    <span class="customer-status-badge badge-active">
                                        <i class="bi bi-check-circle-fill"></i> Active
                                    </span>
                                @else
                                    <span class="customer-status-badge badge-inactive">
                                        <i class="bi bi-x-circle-fill"></i> Inactive
                                    </span>
                                @endif
                            </button>
                        </form>
                    </td>
                    <td class="text-center text-nowrap">
                        <div class="action-btn-group">
                            <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-outline-info" title="View Customer Profile">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-outline-primary" title="Edit Customer">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('Are you sure you want to delete customer \'{{ addslashes($customer->company_name) }}\'?\n\nNote: If this customer has existing sales or invoices, they will be safely deactivated to protect accounting history.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger" title="Delete Customer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Import Excel Modal -->
<div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="importExcelModalLabel">
                    <i class="bi bi-file-earmark-arrow-up text-success me-2"></i>Import Customers
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('customers.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label fw-semibold">Upload Excel File (.xlsx, .xls, .csv)</label>
                        <input class="form-control" type="file" id="excel_file" name="file" accept=".xlsx, .xls, .csv" required>
                        <div class="form-text mt-2">
                            Please ensure your Excel file contains headers like: <code>customer_code, company_name, contact_person, mobile, email, gst_no, address, city, state, pincode, credit_limit, payment_terms</code>.
                            <br>
                            <a href="{{ route('customers.import.sample') }}" class="text-primary text-decoration-none mt-2 d-inline-block fw-semibold">
                                <i class="bi bi-download me-1"></i> Download Sample Template
                            </a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i> Import Customers
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    var customersTableInstance;

    function filterByStatus(status, btn) {
        $('.status-filter-pills .btn').removeClass('active');
        if (btn) $(btn).addClass('active');

        if (!customersTableInstance) return;

        // Target column 7 (Status column) using word boundary regex to avoid partial matches
        if (status === 'all') {
            customersTableInstance.column(7).search('').draw();
        } else if (status === 'Active') {
            customersTableInstance.column(7).search('\\bActive\\b', true, false).draw();
        } else if (status === 'Inactive') {
            customersTableInstance.column(7).search('\\bInactive\\b', true, false).draw();
        }
    }

    $(document).ready(function() {
        customersTableInstance = $('#customersTable').DataTable({
            autoWidth: false,
            scrollX: true,
            scrollCollapse: true,
            responsive: false,
            columnDefs: [
                {
                    searchable: false,
                    orderable: false,
                    targets: 0,
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { visible: false, targets: 1 },
                { searchable: false, orderable: false, targets: 8 }
            ],
            order: [[1, 'desc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search customers...",
                paginate: {
                    previous: '<i class="bi bi-chevron-left"></i>',
                    next: '<i class="bi bi-chevron-right"></i>'
                }
            }
        });

        // Recalculate columns on window resize
        $(window).on('resize', function() {
            if (customersTableInstance) {
                customersTableInstance.columns.adjust();
            }
        });

        setTimeout(function() {
            if (customersTableInstance) {
                customersTableInstance.columns.adjust();
            }
        }, 200);
    });
</script>
@endpush
