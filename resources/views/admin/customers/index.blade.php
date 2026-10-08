@extends('layouts.app')

@section('title', 'Customers - Demo ERP')
@section('header_title', 'Customers')

@section('content')
<div class="table-container mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center p-3 border-bottom">
        <h5 class="mb-0 fw-bold text-primary-custom">Customer List</h5>
        <div>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importExcelModal">
                <i class="bi bi-file-earmark-excel"></i> Import Excel
            </button>
            <a href="{{ route('customers.create') }}" class="btn btn-primary ms-2">
                <i class="bi bi-plus-lg"></i> Add New Customer
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="customersTable" class="data-table">
                <!-- ... table content ... -->
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th class="d-none">Hidden ID</th>
                        <th>Code</th>
                        <th>Company Name</th>
                        <th>Contact Person</th>
                        <th>Mobile</th>
                        <th>City</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                    <tr>
                        <td class="fw-bold text-center"></td>
                        <td class="d-none">{{ $customer->id }}</td>
                        <td>{{ $customer->customer_code }}</td>
                        <td><a href="{{ route('customers.show', $customer->id) }}" class="fw-semibold text-primary-custom text-decoration-none">{{ $customer->company_name }}</a></td>
                        <td>{{ $customer->contact_person }}</td>
                        <td>{{ $customer->mobile }}</td>
                        <td>{{ $customer->city }}</td>
                        <td>
                            @if($customer->status)
                                <span class="status-badge status-active"><i class="bi bi-check-circle-fill me-1"></i>Active</span>
                            @else
                                <span class="status-badge status-inactive"><i class="bi bi-x-circle-fill me-1"></i>Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('customers.show', $customer->id) }}" class="icon-btn btn-sm" title="View"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('customers.edit', $customer->id) }}" class="icon-btn btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form action="{{ route('customers.toggleStatus', $customer->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to {{ $customer->status ? 'mark this customer as inactive' : 'activate this customer' }}?');">
                                    @csrf
                                    <button type="submit" class="icon-btn btn-sm {{ $customer->status ? 'text-danger' : 'text-success' }} border-0 bg-transparent" title="{{ $customer->status ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi {{ $customer->status ? 'bi-person-x' : 'bi-person-check' }}"></i>
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
                <h5 class="modal-title" id="importExcelModalLabel">Import Customers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('customers.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">Upload Excel File (.xlsx, .xls, .csv)</label>
                        <input class="form-control" type="file" id="excel_file" name="file" accept=".xlsx, .xls, .csv" required>
                        <div class="form-text">
                            Please ensure your Excel file contains headers like: <code>customer_code, company_name, contact_person, mobile, email, gst_no, address, city, state, pincode, credit_limit, payment_terms</code>.
                            <br>
                            <a href="{{ route('customers.import.sample') }}" class="text-primary text-decoration-none mt-1 d-inline-block">
                                <i class="bi bi-download"></i> Download Sample Template
                            </a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import Customers</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        var t = $('#customersTable').DataTable({
            columnDefs: [
                { searchable: false, orderable: false, targets: 0 },
                { visible: false, targets: 1 }
            ],
            order: [[1, 'desc']],
            language: {
                paginate: {
                    previous: '<i class="bi bi-chevron-left"></i>',
                    next: '<i class="bi bi-chevron-right"></i>'
                }
            }
        });

        t.on('order.dt search.dt', function () {
            let i = 1;
            t.cells(null, 0, { search: 'applied', order: 'applied' }).every(function (cell) {
                this.data(i++);
            });
        }).draw();
    });
</script>
@endpush
