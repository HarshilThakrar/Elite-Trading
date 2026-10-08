@extends('layouts.app')

@section('title', 'Pending Approvals - Demo ERP')
@section('header_title', 'Pending Approvals')

@section('content')
<div class="card card-custom">
    <div class="card-header bg-white p-3">
        <h5 class="mb-0 fw-bold text-primary-custom">Pending Quotation Approvals</h5>
    </div>
    <div class="card-body">
        <div class="data-table">
            <table class="table table-hover align-middle border-0 shadow-sm rounded-3" id="quotations-table">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3 text-muted">DATE</th>
                        <th class="px-4 py-3 text-muted">QUOTATION NO</th>
                        <th class="px-4 py-3 text-muted">CUSTOMER</th>
                        <th class="px-4 py-3 text-muted">AMOUNT</th>
                        <th class="px-4 py-3 text-muted text-center">STATUS</th>
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
                            @endphp
                            <span class="badge bg-{{ $badgeClass }} text-white px-3 py-2 rounded-pill shadow-sm" style="font-weight: 500; font-size: 0.85rem;">
                                {{ $quotation->status }}
                            </span>
                        </td>
                        @if(auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Admin']))
                        <td class="px-4 py-3">
                            <span class="text-muted small"><i class="bi bi-person me-1"></i>{{ $quotation->creator->name ?? 'N/A' }}</span>
                        </td>
                        @endif
                        <td class="px-4 py-3">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('quotations.show', $quotation->id) }}" target="_blank" class="btn btn-sm btn-light text-primary border shadow-sm" title="View"><i class="bi bi-eye"></i></a>
                                
                                <form action="{{ route('quotations.approve', $quotation->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success border shadow-sm" title="Approve" onclick="return confirm('Are you sure you want to approve this quotation?')">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </button>
                                </form>
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
                "emptyTable": "<div class='text-center py-4 text-muted'><i class='bi bi-inbox fs-1 d-block mb-3'></i>No pending approvals found.</div>"
            }
        });
        
        // Style the search input
        $('.dataTables_filter input').addClass('form-control shadow-sm border-0').css('min-width', '300px');
    });
</script>
@endpush
