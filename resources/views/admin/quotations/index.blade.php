@extends('layouts.app')

@section('title', 'Quotations - Demo ERP')
@section('header_title', 'Quotations')

@section('content')
<div class="card card-custom">
    <div class="card-header bg-white d-flex justify-content-between align-items-center p-3">
        <h5 class="mb-0 fw-bold text-primary-custom">Quotation List</h5>
        <a href="{{ route('quotations.create') }}" class="btn btn-primary-custom btn-sm">
            <i class="bi bi-plus-lg"></i> Create New Quotation
        </a>
    </div>
    <div class="card-body">
        <div class="data-table table-responsive">
            <table class="table table-hover align-middle border-0 shadow-sm rounded-3" id="quotations-table" style="min-width: 1100px;">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3 text-muted">DATE</th>
                        <th class="px-4 py-3 text-muted">QUOTATION NO</th>
                        <th class="px-4 py-3 text-muted">CUSTOMER</th>
                        <th class="px-4 py-3 text-muted">AMOUNT</th>
                        <th class="px-4 py-3 text-muted text-center">ADMIN STATUS</th>
                        <th class="px-4 py-3 text-muted text-center">CUSTOMER STATUS</th>
                        @if(auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Admin']))
                        <th class="px-4 py-3 text-muted">CREATED BY</th>
                        @endif
                        <th class="px-4 py-3 text-muted text-end">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    @foreach($quotations as $quotation)
                    <tr>
                        <td class="px-4 py-3">{{ \Carbon\Carbon::parse($quotation->quotation_date)->format('d M, Y') }}</td>
                        <td class="px-4 py-3 fw-bold text-primary"><a href="{{ route('quotations.edit', $quotation->id) }}" class="text-decoration-none">{{ $quotation->quotation_number }}</a></td>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar bg-light text-primary me-2 d-flex align-items-center justify-content-center fw-bold rounded-circle" style="width: 32px; height: 32px; font-size: 12px;">
                                    {{ substr($quotation->customer->company_name ?? $quotation->customer->customer_name ?? 'C', 0, 1) }}
                                </div>
                                {{ $quotation->customer->company_name ?? $quotation->customer->customer_name }}
                            </div>
                        </td>
                        <td class="px-4 py-3 fw-bold">₹{{ number_format($quotation->grand_total, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $badgeClass = 'secondary';
                                if($quotation->status === 'Approved' || $quotation->status === 'Accepted') $badgeClass = 'success';
                                elseif($quotation->status === 'Sent') $badgeClass = 'info';
                                elseif($quotation->status === 'Rejected') $badgeClass = 'danger';
                                elseif($quotation->status === 'Pending Approval') $badgeClass = 'warning text-dark';
                            @endphp
                            <span class="badge bg-{{ $badgeClass }} text-white px-3 py-2 rounded-pill shadow-sm" style="font-weight: 500; font-size: 0.85rem;">
                                {{ $quotation->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <select class="form-select form-select-sm customer-status-select shadow-sm" data-id="{{ $quotation->id }}" style="min-width: 120px; font-weight: 500;">
                                <option value="Pending" {{ $quotation->customer_status === 'Pending' ? 'selected' : '' }}>Pending</option>
                                <option value="Accepted" {{ $quotation->customer_status === 'Accepted' ? 'selected' : '' }}>Accepted</option>
                                <option value="Rejected" {{ $quotation->customer_status === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="Negotiating" {{ $quotation->customer_status === 'Negotiating' ? 'selected' : '' }}>Negotiating</option>
                            </select>
                        </td>
                        @if(auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Admin']))
                        <td class="px-4 py-3">
                            <span class="text-muted small"><i class="bi bi-person me-1"></i>{{ $quotation->creator->name ?? 'N/A' }}</span>
                        </td>
                        @endif
                        <td class="px-4 py-3">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('quotations.show', $quotation->id) }}" target="_blank" class="btn btn-sm btn-light text-primary border shadow-sm" title="View"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('quotations.edit', $quotation->id) }}" class="btn btn-sm btn-light text-warning border shadow-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                                
                                @if($quotation->status !== 'Pending Approval')
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light text-success border shadow-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                                        <i class="bi bi-download"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow">
                                        <li><a class="dropdown-item" href="{{ route('quotations.export', [$quotation->id, 'pdf']) }}" target="_blank"><i class="bi bi-file-pdf text-danger me-2"></i> Admin PDF</a></li>
                                        <li><a class="dropdown-item" href="{{ route('quotations.pdf', $quotation->id) }}" target="_blank"><i class="bi bi-file-pdf text-danger me-2"></i> Normal PDF</a></li>
                                        <li><a class="dropdown-item" href="{{ route('quotations.export', [$quotation->id, 'excel']) }}"><i class="bi bi-file-excel text-success me-2"></i> Excel Format</a></li>
                                        <li><a class="dropdown-item" href="{{ route('quotations.export', [$quotation->id, 'image']) }}" target="_blank"><i class="bi bi-file-image text-primary me-2"></i> Image Format</a></li>
                                    </ul>
                                </div>
                                <form action="{{ route('quotations.convertToSale', $quotation->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to convert this quotation to a Sales Order?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-light text-primary border shadow-sm" title="Convert to Sales Order">
                                        <i class="bi bi-cart-plus"></i> Convert
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4 d-flex justify-content-center">
                {{ $quotations->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#quotations-table').DataTable({
            "paging": false,       // Let Laravel handle pagination
            "info": false,         // Hide "Showing 1 to X of Y" since Laravel does it
            "searching": true,     // Enable search box
            "ordering": true,      // Enable column sorting
            "dom": '<"d-flex justify-content-between align-items-center mb-3"f>rt', // Custom layout for search bar
            "language": {
                "search": "",
                "searchPlaceholder": "Search quotations...",
                "emptyTable": "<div class='text-center py-4 text-muted'><i class='bi bi-inbox fs-1 d-block mb-3'></i>No quotations found. Click 'Create New Quotation' to get started.</div>"
            }
        });
        
        // Style the search input
        $('.dataTables_filter input').addClass('form-control shadow-sm border-0').css('min-width', '300px');

        // Handle Customer Status Change
        $(document).on('change', '.customer-status-select', function() {
            let select = $(this);
            let quotationId = select.data('id');
            let newStatus = select.val();
            
            // Show processing state
            select.prop('disabled', true);
            
            $.ajax({
                url: `/quotations/${quotationId}/update-customer-status`,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    customer_status: newStatus
                },
                success: function(response) {
                    select.prop('disabled', false);
                    if(response.success) {
                        toastr.success(response.message);
                        // Optional: adjust visual style based on status
                        if(newStatus === 'Accepted') select.removeClass('bg-warning bg-danger').addClass('bg-success text-white');
                        else if(newStatus === 'Rejected') select.removeClass('bg-warning bg-success').addClass('bg-danger text-white');
                        else select.removeClass('bg-success bg-danger text-white');
                    }
                },
                error: function(xhr) {
                    select.prop('disabled', false);
                    toastr.error('An error occurred while updating status.');
                    // Revert to old value if failed (simplified)
                }
            });
        });
    });
</script>
@endpush
