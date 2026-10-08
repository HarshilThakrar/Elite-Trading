@extends('layouts.app')

@section('title', 'Vendors - Demo ERP')
@section('header_title', 'Vendors')

@section('content')
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
        <div class="data-table">
            <table id="vendorsTable" class="data-table">
                <thead class="data-table">
                    <tr>
                        <th>Code</th>
                        <th>Company Name</th>
                        <th>Contact Person</th>
                        <th>Mobile</th>
                        <th>Lead Time (Days)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendors as $vendor)
                    <tr>
                        <td>{{ $vendor->vendor_code }}</td>
                        <td>{{ $vendor->company_name }}</td>
                        <td>{{ $vendor->contact_person }}</td>
                        <td>{{ $vendor->mobile }}</td>
                        <td>{{ $vendor->lead_time_days }}</td>
                        <td>
                            @if($vendor->status)
                                <span class="badge bg-success text-white px-2 py-1 rounded-pill">Active</span>
                            @else
                                <span class="badge bg-danger text-white px-2 py-1 rounded-pill">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('vendors.show', $vendor->id) }}" class="btn btn-sm btn-info text-white"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('vendors.edit', $vendor->id) }}" class="btn btn-sm btn-warning text-white"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('vendors.destroy', $vendor->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this vendor?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                            </form>
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
        $('#vendorsTable').DataTable();
    });
</script>
@endpush
