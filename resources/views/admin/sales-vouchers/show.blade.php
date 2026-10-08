@extends('layouts.app')

@section('title', 'Sales Voucher - ' . $sales_voucher->voucher_number)
@section('header_title', 'Sales Voucher Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Sales Voucher: {{ $sales_voucher->voucher_number }}</h2>
        <div class="d-flex gap-2 print-hide flex-wrap">
            <a href="{{ route('sales-vouchers.pdf', $sales_voucher->id) }}" class="btn btn-primary shadow-sm" target="_blank" download>
                <i class="ph ph-download-simple me-1"></i> Download PDF
            </a>
            <a href="{{ route('sales-vouchers.pdf', ['sales_voucher' => $sales_voucher->id, 'preview' => 1]) }}" class="btn btn-outline-primary shadow-sm" target="_blank">
                <i class="ph ph-eye me-1"></i> Preview PDF
            </a>
            <a href="{{ route('sales-vouchers.index') }}" class="btn btn-light border shadow-sm">
                <i class="ph ph-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Voucher Details -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Voucher Information</h6>
                    <span class="badge {{ $sales_voucher->status === 'Posted' ? 'bg-success' : 'bg-warning' }}">
                        {{ $sales_voucher->status }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <span class="text-muted d-block small">Voucher Date</span>
                            <strong>{{ \Carbon\Carbon::parse($sales_voucher->date)->format('d M, Y') }}</strong>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted d-block small">Customer Name</span>
                            <strong>
                                @if($sales_voucher->reference_type == 'App\Models\Invoice' && $sales_voucher->reference && $sales_voucher->reference->sale && $sales_voucher->reference->sale->customer)
                                    {{ $sales_voucher->reference->sale->customer->company_name ?? $sales_voucher->reference->sale->customer->customer_name }}
                                @elseif($sales_voucher->reference_type == 'App\Models\Sale' && $sales_voucher->reference && $sales_voucher->reference->customer)
                                    {{ $sales_voucher->reference->customer->company_name ?? $sales_voucher->reference->customer->customer_name }}
                                @else
                                    N/A
                                @endif
                            </strong>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <span class="text-muted d-block small">Reference Invoice</span>
                            @if($sales_voucher->reference)
                                @if($sales_voucher->reference_type == 'App\Models\Invoice')
                                    <a href="{{ route('invoices.show', $sales_voucher->reference_id) }}"><strong>{{ $sales_voucher->reference->invoice_number }}</strong></a>
                                @elseif($sales_voucher->reference_type == 'App\Models\Sale')
                                    <a href="{{ route('sales.show', $sales_voucher->reference_id) }}"><strong>{{ $sales_voucher->reference->invoice_number }}</strong></a>
                                @endif
                            @else
                                <strong>N/A</strong>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted d-block small">Narration</span>
                        <p class="mb-0">{{ $sales_voucher->narration ?: 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accounting Impact -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Accounting Entries</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ledger</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalDr = 0;
                                    $totalCr = 0;
                                @endphp
                                @foreach($sales_voucher->entries as $entry)
                                    @php
                                        if($entry->type === 'Dr') $totalDr += $entry->amount;
                                        if($entry->type === 'Cr') $totalCr += $entry->amount;
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('ledgers.show', $entry->ledger_id ?? 0) }}">{{ $entry->ledger?->name ?? 'Ledger' }}</a>
                                        </td>
                                        <td class="text-end">{{ $entry->type === 'Dr' ? '₹'.number_format($entry->amount, 2) : '-' }}</td>
                                        <td class="text-end">{{ $entry->type === 'Cr' ? '₹'.number_format($entry->amount, 2) : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td class="text-end">Total:</td>
                                    <td class="text-end text-success">₹{{ number_format($totalDr, 2) }}</td>
                                    <td class="text-end text-danger">₹{{ number_format($totalCr, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
