@extends('layouts.app')

@section('title', 'Goods Receipt Notes (GRN) - Demo ERP')
@section('header_title', 'Goods Receipt Notes (GRN)')

@section('content')
<div class="card card-custom border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center p-3 border-bottom">
        <div>
            <h5 class="mb-0 fw-bold text-primary-custom">
                <i class="bi bi-box-arrow-in-down me-2"></i>Goods Receipt Notes (GRN)
            </h5>
            <div class="text-muted small">Warehouse physical stock receipt vouchers</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-cart me-1"></i> Purchase Orders
            </a>
            <a href="{{ route('purchases.create') }}" class="btn btn-primary-custom btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Create PO
            </a>
        </div>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table id="grnTable" class="table table-bordered table-hover align-middle w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width: 12%;">Receipt Date</th>
                        <th style="width: 18%;">GRN Number</th>
                        <th style="width: 15%;">PO Reference</th>
                        <th style="width: 25%;">Vendor</th>
                        <th style="width: 10%;" class="text-center">Status</th>
                        <th style="width: 10%;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grns as $grn)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($grn->received_date)->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('grn.show', $grn->id) }}" class="fw-bold text-primary text-decoration-none">
                                {{ $grn->grn_number }}
                            </a>
                        </td>
                        <td>
                            @if($grn->purchase)
                                <a href="{{ route('purchases.show', $grn->purchase->id) }}" class="badge bg-light text-dark border text-decoration-none">
                                    #{{ $grn->purchase->po_number }}
                                </a>
                            @else
                                <span class="text-muted small">N/A</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $grn->purchase->vendor->company_name ?? 'N/A' }}</div>
                            @if(!empty($grn->purchase->vendor->mobile))
                                <div class="small text-muted"><i class="bi bi-telephone me-1"></i>{{ $grn->purchase->vendor->mobile }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                {{ $grn->status }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('grn.show', $grn->id) }}" class="btn btn-sm btn-info text-white" title="View GRN">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                @if($grn->purchase)
                                <a href="{{ route('purchases.show', $grn->purchase->id) }}" class="btn btn-sm btn-outline-secondary" title="View PO">
                                    <i class="bi bi-cart"></i> PO
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#grnTable').DataTable({
            "order": [[ 0, "desc" ]],
            "language": {
                "emptyTable": "No Goods Receipt Notes generated yet."
            }
        });
    });
</script>
@endpush
