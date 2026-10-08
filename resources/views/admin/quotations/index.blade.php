@extends('layouts.app')

@section('title', 'Quotations - Demo ERP')
@section('header_title', 'Quotations')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i> {{ session('info') }}
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
        justify-content: flex-end;
        gap: 5px;
        flex-wrap: nowrap;
        white-space: nowrap;
    }
    .action-btn-group .action-btn {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        flex-shrink: 0;
        font-size: 0.85rem;
        transition: all 0.2s ease-in-out;
        border: none;
    }
    .action-btn-group .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.12) !important;
    }
    .action-btn-group .convert-btn {
        height: 32px;
        padding: 0 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        flex-shrink: 0;
        white-space: nowrap;
        transition: all 0.2s ease-in-out;
        border: none;
    }
    .action-btn-group .convert-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.15) !important;
    }
    .action-btn-group form {
        display: inline-flex;
        margin: 0;
        padding: 0;
    }
    .action-btn-group .dropdown-toggle::after {
        display: none !important;
    }
    .action-btn-group .dropdown-menu {
        font-size: 0.85rem;
        border-radius: 8px;
    }
</style>

<div class="card card-custom">
    <div class="card-header bg-white d-flex justify-content-between align-items-center p-3 flex-wrap gap-2">
        <h5 class="mb-0 fw-bold text-primary-custom">Quotation List</h5>
        <a href="{{ route('quotations.create') }}" class="btn btn-primary-custom btn-sm">
            <i class="bi bi-plus-lg"></i> Create New Quotation
        </a>
    </div>
    <div class="card-body">
        <div class="data-table table-responsive" style="-webkit-overflow-scrolling: touch;">
            <table class="table table-hover align-middle border-0 shadow-sm rounded-3" id="quotations-table" style="width: 100%; min-width: 1100px;">
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
                        <th class="px-3 py-3 text-muted text-end" style="min-width: 220px; width: 220px;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    @foreach($quotations as $quotation)
                    <tr>
                        <td class="px-4 py-3 text-nowrap">{{ \Carbon\Carbon::parse($quotation->quotation_date)->format('d M, Y') }}</td>
                        <td class="px-4 py-3 fw-bold text-primary text-nowrap"><a href="{{ route('quotations.edit', $quotation->id) }}" class="text-decoration-none">{{ $quotation->quotation_number }}</a></td>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar bg-light text-primary me-2 d-flex align-items-center justify-content-center fw-bold rounded-circle flex-shrink-0" style="width: 32px; height: 32px; font-size: 12px;">
                                    {{ substr($quotation->customer->company_name ?? $quotation->customer->customer_name ?? 'C', 0, 1) }}
                                </div>
                                <span class="text-truncate" style="max-width: 220px;" title="{{ $quotation->customer->company_name ?? $quotation->customer->customer_name }}">
                                    {{ $quotation->customer->company_name ?? $quotation->customer->customer_name }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 fw-bold text-nowrap">₹{{ number_format($quotation->grand_total, 2) }}</td>
                        <td class="px-4 py-3 text-center text-nowrap">
                            @php
                                $badgeClass = 'secondary';
                                if($quotation->status === 'Approved' || $quotation->status === 'Accepted') $badgeClass = 'success';
                                elseif($quotation->status === 'Converted') $badgeClass = 'primary';
                                elseif($quotation->status === 'Sent') $badgeClass = 'info';
                                elseif($quotation->status === 'Rejected') $badgeClass = 'danger';
                                elseif($quotation->status === 'Pending Approval') $badgeClass = 'warning text-dark';
                            @endphp
                            <span class="badge bg-{{ $badgeClass }} text-white px-3 py-2 rounded-pill shadow-sm" style="font-weight: 500; font-size: 0.85rem;">
                                {{ $quotation->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-nowrap">
                            <select class="form-select form-select-sm customer-status-select shadow-sm" data-id="{{ $quotation->id }}" style="min-width: 120px; font-weight: 500;">
                                <option value="Pending" {{ $quotation->customer_status === 'Pending' ? 'selected' : '' }}>Pending</option>
                                <option value="Accepted" {{ $quotation->customer_status === 'Accepted' ? 'selected' : '' }}>Accepted</option>
                                <option value="Rejected" {{ $quotation->customer_status === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="Negotiating" {{ $quotation->customer_status === 'Negotiating' ? 'selected' : '' }}>Negotiating</option>
                            </select>
                        </td>
                        @if(auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Admin']))
                        <td class="px-4 py-3 text-nowrap">
                            <span class="text-muted small"><i class="bi bi-person me-1"></i>{{ $quotation->creator->name ?? 'N/A' }}</span>
                        </td>
                        @endif
                        <td class="px-3 py-3 text-end" style="min-width: 220px; width: 220px; white-space: nowrap;">
                            <div class="action-btn-group">
                                {{-- View Button --}}
                                <a href="{{ route('quotations.show', $quotation->id) }}" target="_blank" class="btn btn-sm btn-info text-white shadow-sm action-btn" title="View Quotation">
                                    <i class="bi bi-eye"></i>
                                </a>

                                {{-- Edit Button --}}
                                <a href="{{ route('quotations.edit', $quotation->id) }}" class="btn btn-sm btn-warning text-white shadow-sm action-btn" title="Edit Quotation">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                {{-- Export Options --}}
                                <div class="dropdown d-inline-block">
                                    <button class="btn btn-sm btn-success text-white shadow-sm action-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Download Options">
                                        <i class="bi bi-download"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item py-2" href="{{ route('quotations.export', [$quotation->id, 'pdf']) }}" target="_blank"><i class="bi bi-file-pdf text-danger me-2"></i> Admin PDF</a></li>
                                        <li><a class="dropdown-item py-2" href="{{ route('quotations.pdf', $quotation->id) }}" target="_blank"><i class="bi bi-file-pdf text-danger me-2"></i> Normal PDF</a></li>
                                        <li><a class="dropdown-item py-2" href="{{ route('quotations.export', [$quotation->id, 'excel']) }}"><i class="bi bi-file-excel text-success me-2"></i> Excel Format</a></li>
                                        <li><a class="dropdown-item py-2" href="{{ route('quotations.export', [$quotation->id, 'image']) }}" target="_blank"><i class="bi bi-file-image text-primary me-2"></i> Image Format</a></li>
                                    </ul>
                                </div>

                                {{-- Convert Button / Converted Status --}}
                                @if($quotation->status === 'Converted')
                                    @php
                                        $linkedSale = isset($convertedSalesMap[$quotation->quotation_number]) ? $convertedSalesMap[$quotation->quotation_number] : null;
                                    @endphp
                                    @if($linkedSale)
                                        <a href="{{ route('sales.show', $linkedSale->id) }}" class="btn btn-sm btn-outline-success shadow-sm convert-btn" title="View Sales Order: {{ $linkedSale->invoice_number }}">
                                            <i class="bi bi-check-circle-fill text-success"></i> <span>SO View</span>
                                        </a>
                                    @else
                                        <span class="btn btn-sm btn-outline-success shadow-sm convert-btn" style="cursor: default; pointer-events: none;" title="Quotation Converted">
                                            <i class="bi bi-check-circle-fill text-success"></i> <span>Converted</span>
                                        </span>
                                    @endif
                                @elseif($quotation->status === 'Pending Approval' && !(auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Admin'])))
                                    <button type="button" class="btn btn-sm btn-light text-muted border shadow-sm convert-btn" disabled title="Pending Admin Approval">
                                        <i class="bi bi-clock-history"></i> <span>Pending</span>
                                    </button>
                                @else
                                    <form action="{{ route('quotations.convertToSale', $quotation->id) }}" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('Are you sure you want to convert Quotation {{ $quotation->quotation_number }} into a Sales Order?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary-custom text-white shadow-sm convert-btn" title="Convert to Sales Order">
                                            <i class="bi bi-cart-plus"></i> <span>Convert</span>
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
