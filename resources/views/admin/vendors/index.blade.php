@extends('layouts.app')

@section('title', 'Vendors - Demo ERP')
@section('header_title', 'Vendors')

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
    }
    .action-btn-group form {
        display: inline-flex;
        margin: 0;
        padding: 0;
    }
</style>

<div class="card card-custom">
    <div class="card-header bg-white d-flex justify-content-between align-items-center p-3">
        <h5 class="mb-0 fw-bold text-primary-custom">Vendor List</h5>
        <div>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#importExcelModal">
                <i class="bi bi-file-earmark-excel"></i> Import Excel
            </button>
            <a href="{{ route('vendors.create') }}" class="btn btn-primary-custom btn-sm ms-2">
                <i class="bi bi-plus-lg"></i> Add New Vendor
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="vendorsTable" class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th class="text-nowrap">Code</th>
                        <th class="text-nowrap">Company Name</th>
                        <th class="text-nowrap">Contact Person</th>
                        <th class="text-nowrap">Mobile</th>
                        <th class="text-center text-nowrap">Lead Time (Days)</th>
                        <th class="text-center text-nowrap">Status</th>
                        <th class="text-center text-nowrap" style="width: 130px; min-width: 130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendors as $vendor)
                    <tr>
                        <td class="text-nowrap fw-semibold">{{ $vendor->vendor_code }}</td>
                        <td>
                            <a href="{{ route('vendors.show', $vendor->id) }}" class="fw-semibold text-primary-custom text-decoration-none">
                                {{ $vendor->company_name }}
                            </a>
                        </td>
                        <td>{{ $vendor->contact_person ?? 'N/A' }}</td>
                        <td class="text-nowrap">{{ $vendor->mobile }}</td>
                        <td class="text-center">{{ $vendor->lead_time_days }}</td>
                        <td class="text-center text-nowrap">
                            <form action="{{ route('vendors.toggleStatus', $vendor->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to {{ $vendor->status ? 'deactivate' : 'activate' }} this vendor?');">
                                @csrf
                                <button type="submit" class="border-0 bg-transparent p-0" title="Click to {{ $vendor->status ? 'Deactivate' : 'Activate' }}">
                                    @if($vendor->status)
                                        <span class="badge bg-success text-white px-2 py-1 rounded-pill" style="cursor: pointer;"><i class="bi bi-check-circle-fill me-1"></i>Active</span>
                                    @else
                                        <span class="badge bg-danger text-white px-2 py-1 rounded-pill" style="cursor: pointer;"><i class="bi bi-x-circle-fill me-1"></i>Inactive</span>
                                    @endif
                                </button>
                            </form>
                        </td>
                        <td class="text-center text-nowrap" style="width: 130px; min-width: 130px;">
                            <div class="action-btn-group">
                                <a href="{{ route('vendors.show', $vendor->id) }}" class="btn btn-sm btn-info text-white shadow-sm" title="View Vendor">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('vendors.edit', $vendor->id) }}" class="btn btn-sm btn-warning text-white shadow-sm" title="Edit Vendor">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('vendors.destroy', $vendor->id) }}" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('Are you sure you want to delete vendor \'{{ addslashes($vendor->company_name) }}\'?\n\nNote: If this vendor has existing purchase records or bills, it will be safely deactivated to protect audit history.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger shadow-sm" title="Delete Vendor">
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
</div>

<!-- Import Excel Modal -->
<div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importExcelModalLabel">Import Vendors</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('vendors.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">Upload Excel File (.xlsx, .xls, .csv)</label>
                        <input class="form-control" type="file" id="excel_file" name="file" accept=".xlsx, .xls, .csv" required>
                        <div class="form-text">
                            Please ensure your Excel file contains headers like: <code>vendor_code, company_name, contact_person, mobile, email, gst_no, lead_time_days</code>.
                            <br>
                            <a href="{{ route('vendors.import.sample') }}" class="text-primary text-decoration-none mt-1 d-inline-block">
                                <i class="bi bi-download"></i> Download Sample Template
                            </a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import Vendors</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#vendorsTable').DataTable({
            autoWidth: false,
            columnDefs: [
                { orderable: false, targets: [6] }
            ]
        });
    });
</script>
@endpush
