@extends('layouts.app')

@section('title', 'Sales Report - Demo ERP')
@section('header_title', 'Sales Report')

@section('content')
<!-- Filter Card (Hidden in Print) -->
<div class="card card-custom mb-4 print-hide">
    <div class="card-body">
        <form action="{{ route('reports.sales') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4 col-sm-6">
                <label class="form-label fw-semibold">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-md-4 col-sm-6">
                <label class="form-label fw-semibold">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-md-4 col-12">
                <button type="submit" class="btn btn-primary-custom w-100"><i class="bi bi-filter"></i> Filter Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Print-Only Header (Visible only when Printing/PDF) -->
<div class="print-only mb-3">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
        <div>
            <h3 class="fw-bold mb-0 text-dark">Demo ERP System</h3>
            <div class="text-muted small">Sales Summary & Ledger Report</div>
        </div>
        <div class="text-end small">
            <div><strong>Period:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</div>
            <div><strong>Printed on:</strong> {{ now()->format('d M Y, h:i A') }}</div>
        </div>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header bg-white d-flex justify-content-between align-items-center p-3 flex-wrap gap-2">
        <h5 class="mb-0 fw-bold text-primary-custom">Sales from {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</h5>
        <div class="d-flex gap-2 align-items-center print-hide">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
            <button type="button" class="btn btn-danger btn-sm text-white" onclick="window.print()" title="Print to PDF">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="salesReportTable" class="table table-hover table-bordered align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Invoice Number</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th class="text-end">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalAmount = 0; @endphp
                    @foreach($sales as $sale)
                        @php 
                            if(in_array($sale->status, ['Approved', 'Dispatched'])) {
                                $totalAmount += $sale->total_amount; 
                            }
                        @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</td>
                        <td><a href="{{ route('sales.show', $sale->id) }}" class="fw-semibold">{{ $sale->invoice_number }}</a></td>
                        <td>{{ $sale->customer->company_name ?? 'N/A' }}</td>
                        <td>
                            @if($sale->status == 'Draft')
                                <span class="badge bg-secondary text-white">Draft</span>
                            @elseif($sale->status == 'Approved')
                                <span class="badge bg-primary text-white">Approved</span>
                            @elseif($sale->status == 'Dispatched')
                                <span class="badge bg-success text-white">Dispatched</span>
                            @else
                                <span class="badge bg-danger text-white">Cancelled</span>
                            @endif
                        </td>
                        <td class="text-end fw-semibold">{{ number_format($sale->total_amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Total Revenue (Approved/Dispatched):</th>
                        <th class="text-end fs-5 text-success">₹ {{ number_format($totalAmount, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#salesReportTable').DataTable({
            "order": [[ 0, "desc" ]],
            "paging": false,
            "info": false
        });
    });
</script>
@endpush
