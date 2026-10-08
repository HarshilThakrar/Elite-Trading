@extends('layouts.app')

@section('title', 'Purchase Orders - Demo ERP')
@section('header_title', 'Purchase Orders')

@section('content')
<div class="card card-custom">
    <div class="card-header bg-white d-flex justify-content-between align-items-center p-3">
        <h5 class="mb-0 fw-bold text-primary-custom">Purchase Orders List</h5>
        <a href="{{ route('purchases.create') }}" class="btn btn-primary-custom btn-sm">
            <i class="bi bi-plus-lg"></i> Create New PO
        </a>
    </div>
    <div class="card-body">
        <div class="data-table">
            <table id="purchasesTable" class="data-table">
                <thead class="data-table">
                    <tr>
                        <th>Date</th>
                        <th>PO Number</th>
                        <th>Vendor</th>
                        <th class="text-end">Total Amount (₹)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchases as $purchase)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($purchase->po_date)->format('d M Y') }}</td>
                        <td class="fw-bold">{{ $purchase->po_number }}</td>
                        <td>{{ $purchase->vendor->company_name ?? 'N/A' }}</td>
                        <td class="text-end fw-bold">{{ number_format($purchase->total_amount, 2) }}</td>
                        <td>
                            @if($purchase->status == 'Draft')
                                <span class="fw-bold text-secondary">Draft</span>
                            @elseif($purchase->status == 'Approved')
                                <span class="fw-bold text-primary">Approved</span>
                            @elseif($purchase->status == 'Received')
                                <span class="fw-bold text-success">Received</span>
                            @else
                                <span class="fw-bold text-danger">Cancelled</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('purchases.show', $purchase->id) }}" class="btn btn-sm btn-info text-white" title="View PO"><i class="bi bi-eye"></i> View</a>
                                <a href="{{ route('purchases.pdf', $purchase->id) }}" class="btn btn-sm btn-secondary" title="Download PDF"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#purchasesTable').DataTable({
            "order": [[ 0, "desc" ]]
        });
    });
</script>
@endpush
