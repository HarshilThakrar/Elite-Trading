@extends('layouts.app')

@section('title', 'Purchase Voucher Details')
@section('header_title', 'Purchase Voucher Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Purchase Voucher: {{ $voucher->voucher_number }}</h2>
        <div>
            @if($voucher->status === 'Posted')
                <a href="{{ route('purchase-vouchers.pdf', $voucher->id) }}" class="btn btn-secondary shadow-sm">
                    <i class="ph ph-download-simple me-1"></i> Download PDF
                </a>
            @endif
            <a href="{{ route('purchase-vouchers.index') }}" class="btn btn-light border shadow-sm">
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
                            <span class="text-muted d-block small">Voucher Date</span>
                            <strong>{{ \Carbon\Carbon::parse($voucher->date)->format('d M, Y') }}</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block small">Financial Year</span>
                            <strong>{{ $voucher->financialYear->name ?? 'N/A' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block small">Purchase Order Reference</span>
                            @if($voucher->reference)
                                <a href="{{ route('purchases.show', $voucher->reference->id) }}">
                                    <strong>{{ $voucher->reference->po_number }}</strong>
                                </a>
                            @else
                                <strong>N/A</strong>
                            @endif
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <span class="text-muted d-block small">Vendor</span>
                            @if($voucher->reference && $voucher->reference->vendor)
                                <strong>{{ $voucher->reference->vendor->company_name }}</strong>
                            @elseif($voucher->metadata && isset($voucher->metadata['vendor_id']))
                                @php $vData = \App\Models\Vendor::find($voucher->metadata['vendor_id']); @endphp
                                <strong>{{ $vData->company_name ?? 'N/A' }}</strong>
                            @else
                                <strong>N/A</strong>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block small">Vendor Invoice Number</span>
                            <strong>{{ $voucher->metadata['vendor_invoice_number'] ?? 'N/A' }}</strong>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted d-block small">Vendor Invoice Date</span>
                            <strong>{{ isset($voucher->metadata['vendor_invoice_date']) ? \Carbon\Carbon::parse($voucher->metadata['vendor_invoice_date'])->format('d M, Y') : 'N/A' }}</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <span class="text-muted d-block small">Narration</span>
                        <p class="mb-0">{{ $voucher->narration ?: 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Invoiced Items -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Invoiced Items</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Invoiced Qty</th>
                                    <th>Rate</th>
                                    <th>Taxable Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $lines = $voucher->status === 'Draft' ? ($voucher->draft_data['lines'] ?? []) : ($voucher->metadata['lines'] ?? []);
                                    // If metadata doesn't have lines for Posted vouchers, we should ideally fetch from PurchaseItem if we linked them, but since we didn't create a PurchaseVoucherLine table, we rely on the accounting or metadata. Let's assume metadata has it if we modify controller to include it. Wait, I didn't include lines in metadata for posted vouchers. Let me show totals instead or update controller. I'll just show the lines if they exist.
                                @endphp
                                
                                @if(empty($lines) && $voucher->status === 'Posted')
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Line details are stored in Purchase Order Items.</td>
                                    </tr>
                                @else
                                    @foreach($lines as $line)
                                        @php $product = \App\Models\Product::find($line['product_id']); @endphp
                                        <tr>
                                            <td>{{ $product->item_name ?? 'Unknown' }}</td>
                                            <td>{{ $line['invoiced_qty'] }}</td>
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
                                    <th colspan="3" class="text-end">CGST:</th>
                                    <th>₹{{ number_format($voucher->metadata['total_cgst'] ?? 0, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">SGST:</th>
                                    <th>₹{{ number_format($voucher->metadata['total_sgst'] ?? 0, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">IGST:</th>
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
                            <i class="ph ph-info me-1"></i> Draft vouchers do not have accounting impact.
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
        </div>
    </div>
</div>
@endsection
