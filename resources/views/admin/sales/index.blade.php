@extends('layouts.app')

@section('title', 'Sales Orders - Demo ERP')
@section('header_title', 'Sales Orders')

@section('content')
<div class="card card-custom">
    <div class="card-header bg-white d-flex justify-content-between align-items-center p-3">
        <h5 class="mb-0 fw-bold text-primary-custom">Sales Orders List</h5>
        <a href="{{ route('sales.create') }}" class="btn btn-primary-custom btn-sm">
            <i class="bi bi-plus-lg"></i> Create New Sale
        </a>
    </div>
    <div class="card-body">
        <div class="data-table">
            <table id="salesTable" class="data-table">
                <thead class="data-table">
                    <tr>
                        <th>Date</th>
                        <th>Invoice Number</th>
                        <th>Customer</th>
                        <th class="text-end">Total Amount (₹)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sales as $sale)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</td>
                        <td class="fw-bold">{{ $sale->invoice_number }}</td>
                        <td>{{ $sale->customer->company_name ?? 'N/A' }}</td>
                        <td class="text-end fw-bold">{{ number_format($sale->total_amount, 2) }}</td>
                        <td>
                            @if($sale->status == 'Draft')
                                <span class="fw-bold text-secondary">Draft</span>
                            @elseif($sale->status == 'Approved')
                                <span class="fw-bold text-primary">Approved</span>
                            @elseif($sale->status == 'Dispatched')
                                <span class="fw-bold text-success">Dispatched</span>
                            @else
                                <span class="fw-bold text-danger">Cancelled</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-sm btn-info text-white"><i class="bi bi-eye"></i> View</a>
                                <a href="{{ route('sales.pdf', $sale->id) }}" class="btn btn-sm btn-danger text-white" title="Download PDF"><i class="bi bi-file-pdf"></i></a>
                                <a href="{{ route('sales.pdf', ['sale' => $sale->id, 'type' => 'einvoice']) }}" class="btn btn-sm btn-warning text-dark" title="Download E-Invoice PDF"><i class="bi bi-qr-code"></i></a>
                                <form action="{{ route('sales.destroy', $sale->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this Sales Order?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger text-white" title="Delete"><i class="bi bi-trash"></i></button>
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
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#salesTable').DataTable({
            "order": [[ 0, "desc" ]]
        });
    });
</script>
@endpush
