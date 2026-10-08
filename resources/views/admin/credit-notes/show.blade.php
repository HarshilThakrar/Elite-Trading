@extends('layouts.app')

@section('title', 'Credit Note Details')
@section('header_title', 'Credit Note Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Credit Note: {{ $voucher->voucher_number }}</h2>
        <div>
            @if($voucher->status === 'Posted')
                <a href="{{ route('credit-notes.pdf', $voucher->id) }}" class="btn btn-secondary shadow-sm">
                    <i class="ph ph-download-simple me-1"></i> Download PDF
                </a>
            @endif
            <a href="{{ route('credit-notes.index') }}" class="btn btn-light border shadow-sm">
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
                    <span class="badge {{ $voucher->status === 'Posted' ? 'bg-success' : ($voucher->status === 'Draft' ? 'bg-warning' : 'bg-danger') }}">
                        {{ $voucher->status }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <span class="text-muted d-block small">Credit Note Date</span>
                            <strong>{{ \Carbon\Carbon::parse($voucher->date)->format('d M, Y') }}</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block small">Financial Year</span>
                            <strong>{{ $voucher->financialYear->name ?? 'N/A' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block small">Customer</span>
                            @if($voucher->metadata && isset($voucher->metadata['customer_id']))
                                @php $cData = \App\Models\Customer::find($voucher->metadata['customer_id']); @endphp
                                <strong>{{ $cData->company_name ?? 'N/A' }}</strong>
                            @else
                                <strong>N/A</strong>
                            @endif
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <span class="text-muted d-block small">Original Sales Voucher No</span>
                            @if($voucher->reference)
                                <a href="{{ route('sales-vouchers.show', $voucher->reference->id) }}">
                                    <strong>{{ $voucher->reference->voucher_number }}</strong>
                                </a>
                            @else
                                <strong>{{ $voucher->metadata['original_sales_voucher_number'] ?? 'N/A' }}</strong>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block small">Reason for Return</span>
                            <strong>{{ $voucher->metadata['reason'] ?? 'N/A' }}</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted d-block small">Narration</span>
                        <p class="mb-0">{{ $voucher->narration ?: 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Returned Items -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Returned Items</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Return Qty</th>
                                    <th>Original Rate</th>
                                    <th>Taxable Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $lines = $voucher->status === 'Draft' ? ($voucher->draft_data['lines'] ?? []) : ($voucher->metadata['lines'] ?? []);
                                @endphp
                                
                                @if(empty($lines))
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No items found.</td>
                                    </tr>
                                @else
                                    @foreach($lines as $line)
                                        @php $product = \App\Models\Product::find($line['product_id']); @endphp
                                        <tr>
                                            <td>{{ $product->item_name ?? 'Unknown' }}</td>
                                            <td>{{ $line['return_qty'] }}</td>
                                            <td>₹{{ number_format($line['unit_price'], 2) }}</td>
                                            <td>₹{{ number_format($line['amount'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end">Total Taxable:</th>
                                    <th>₹{{ number_format($voucher->metadata['total_taxable'] ?? 0, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Output CGST Reversal:</th>
                                    <th>₹{{ number_format($voucher->metadata['total_cgst'] ?? 0, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Output SGST Reversal:</th>
                                    <th>₹{{ number_format($voucher->metadata['total_sgst'] ?? 0, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Output IGST Reversal:</th>
                                    <th>₹{{ number_format($voucher->metadata['total_igst'] ?? 0, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end fs-5">Grand Total:</th>
                                    <th class="fs-5 text-primary">₹{{ number_format($voucher->metadata['grand_total'] ?? 0, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
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
                    @if($voucher->status === 'Draft')
                        <div class="p-4 text-center text-muted">
                            <i class="ph ph-info me-1"></i> Draft credit notes do not have accounting impact.
                        </div>
                    @else
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
                                    @foreach($voucher->entries as $entry)
                                        @php
                                            if($entry->type === 'Dr') $totalDr += $entry->amount;
                                            if($entry->type === 'Cr') $totalCr += $entry->amount;
                                        @endphp
                                        <tr>
                                            <td>
                                                <a href="{{ route('ledgers.show', $entry->ledger_id) }}">{{ $entry->ledger->name }}</a>
                                            </td>
                                            <td class="text-end">{{ $entry->type === 'Dr' ? '₹'.number_format($entry->amount, 2) : '-' }}</td>
                                            <td class="text-end">{{ $entry->type === 'Cr' ? '₹'.number_format($entry->amount, 2) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td class="text-end">Total:</td>
                                        <td class="text-end">₹{{ number_format($totalDr, 2) }}</td>
                                        <td class="text-end">₹{{ number_format($totalCr, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            
            @if($voucher->status === 'Posted')
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Inventory Impact</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <i class="ph ph-check-circle me-1"></i> Physical stock was increased successfully and an <strong>IN</strong> transaction was recorded in the Inventory Ledger.
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
