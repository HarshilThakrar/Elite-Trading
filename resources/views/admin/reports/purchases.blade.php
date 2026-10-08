@extends('layouts.app')

@section('title', 'Purchase Report - Demo ERP')
@section('header_title', 'Purchase Report')

@section('content')
<div class="card card-custom mb-4">
    <div class="card-body">
        <form action="{{ route('reports.purchases') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary-custom w-100"><i class="bi bi-filter"></i> Filter Report</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header bg-white d-flex justify-content-between align-items-center p-3">
        <h5 class="mb-0 fw-bold text-primary-custom">Purchases from {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</h5>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="purchasesReportTable" class="table table-hover table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>PO Number</th>
                        <th>Vendor</th>
                        <th>Status</th>
                        <th class="text-end">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalAmount = 0; @endphp
                    @foreach($purchases as $purchase)
                        @php 
                            if(in_array($purchase->status, ['Approved', 'Received'])) {
                                $totalAmount += $purchase->total_amount; 
                            }
                        @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($purchase->po_date)->format('d M Y') }}</td>
                        <td><a href="{{ route('purchases.show', $purchase->id) }}">{{ $purchase->po_number }}</a></td>
                        <td>{{ $purchase->vendor->company_name ?? 'N/A' }}</td>
                        <td>
                            @if($purchase->status == 'Draft')
                                <span class="badge bg-secondary">Draft</span>
                            @elseif($purchase->status == 'Approved')
                                <span class="badge bg-primary">Approved</span>
                            @elseif($purchase->status == 'Received')
                                <span class="badge bg-success">Received</span>
                            @else
                                <span class="badge bg-danger">Cancelled</span>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format($purchase->total_amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Total Cost (Approved/Received):</th>
                        <th class="text-end fs-5 text-danger">₹ {{ number_format($totalAmount, 2) }}</th>
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
        $('#purchasesReportTable').DataTable({
            "order": [[ 0, "desc" ]],
            "paging": false,
            "info": false
        });
    });
</script>
@endpush
