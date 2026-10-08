@extends('layouts.app')

@section('title', 'Payment Voucher - ' . $voucher->voucher_number)
@section('header_title', 'Payment Voucher Details')

@section('content')
<div class="container-fluid payment-voucher-container">
    <!-- Top Action Bar (Hidden in Print) -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 print-hide d-print-none">
        <div>
            <h2 class="h3 mb-1 text-gray-800 fw-bold">Payment Voucher Details</h2>
            <div class="text-muted small">Voucher Reference: <strong class="text-dark">{{ $voucher->voucher_number }}</strong></div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('payment-vouchers.index') }}" class="btn btn-light border shadow-sm btn-sm">
                <i class="ph ph-arrow-left me-1"></i> Back to List
            </a>
            <button class="btn btn-primary shadow-sm btn-sm" onclick="triggerCleanPrint()" title="Print Voucher">
                <i class="ph ph-printer me-1"></i> Print / Save PDF
            </button>
        </div>
    </div>

    <!-- Alert Messages (Hidden in Print) -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show print-hide d-print-none" role="alert">
            <i class="ph ph-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Official Printable Header (Visible Only in Print) -->
    <div class="print-only mb-4 pb-3 border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-0 text-dark">DEMO ERP SYSTEM</h3>
                <div class="small text-muted">Accounting & Financial Records System</div>
            </div>
            <div class="text-end">
                <h4 class="fw-bold mb-0 text-primary">PAYMENT VOUCHER</h4>
                <div class="small">Voucher No: <strong class="text-dark">{{ $voucher->voucher_number }}</strong></div>
                <div class="small">Date: <strong class="text-dark">{{ $voucher->date->format('d-M-Y') }}</strong></div>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top small text-muted">
            <div>Financial Year: <strong class="text-dark">{{ $voucher->financialYear->name ?? '-' }}</strong></div>
            <div>Print Timestamp: <strong class="text-dark">{{ date('d-M-Y h:i A') }}</strong></div>
        </div>
    </div>

    <!-- Voucher Details Card -->
    <div class="card shadow-sm border-0 mb-4 print-voucher-card rounded-3">
        <div class="card-header bg-white py-3 border-bottom print-hide d-print-none d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-primary">{{ $voucher->voucher_number }}</h5>
            <span class="badge {{ $voucher->status === 'Posted' ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-1">
                {{ $voucher->status }}
            </span>
        </div>
        <div class="card-body p-3 p-md-4">
            <!-- Metadata Grid (Fully Responsive) -->
            <div class="row g-3 g-md-4 mb-4">
                <div class="col-6 col-sm-6 col-md-3">
                    <span class="text-muted small d-block mb-1">Date</span>
                    <span class="fw-bold text-dark">{{ $voucher->date->format('d-M-Y') }}</span>
                </div>
                <div class="col-6 col-sm-6 col-md-3">
                    <span class="text-muted small d-block mb-1">Financial Year</span>
                    <span class="fw-bold text-dark">{{ $voucher->financialYear->name ?? '-' }}</span>
                </div>
                <div class="col-6 col-sm-6 col-md-3">
                    <span class="text-muted small d-block mb-1">Payment Mode</span>
                    <span class="fw-bold text-dark">{{ $voucher->metadata['payment_mode'] ?? '-' }}</span>
                </div>
                <div class="col-6 col-sm-6 col-md-3">
                    <span class="text-muted small d-block mb-1">Reference No / Cheque</span>
                    <span class="fw-bold text-dark">{{ $voucher->metadata['reference_number'] ?? '-' }}</span>
                </div>
                <div class="col-12">
                    <span class="text-muted small d-block mb-1">Master Narration</span>
                    <span class="fw-bold text-dark">{{ $voucher->narration ?? '-' }}</span>
                </div>
            </div>

            <h5 class="fw-bold mb-3 text-dark">Accounting Details</h5>
            <div class="table-responsive mb-4 voucher-table-container">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 250px;">Ledger</th>
                            <th style="min-width: 180px;">Line Narration</th>
                            <th style="min-width: 130px;" class="text-end">Debit (Dr)</th>
                            <th style="min-width: 130px;" class="text-end">Credit (Cr)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalDr = 0;
                            $totalCr = 0;
                            $lines = [];

                            if ($voucher->status === 'Draft' && !empty($voucher->draft_data['lines'])) {
                                foreach($voucher->draft_data['lines'] as $line) {
                                    $ledger = \App\Models\Ledger::find($line['ledger_id']);
                                    $lines[] = [
                                        'ledger_name' => $ledger ? $ledger->getPath() : 'Unknown Ledger',
                                        'narration' => $line['narration'],
                                        'debit' => $line['debit'],
                                        'credit' => 0
                                    ];
                                    $totalDr += (float)$line['debit'];
                                }
                                $paymentSource = \App\Models\Ledger::find($voucher->draft_data['metadata']['payment_from_id']);
                                $lines[] = [
                                    'ledger_name' => $paymentSource ? $paymentSource->getPath() : 'Unknown Payment Source',
                                    'narration' => '-',
                                    'debit' => 0,
                                    'credit' => $totalDr
                                ];
                                $totalCr = $totalDr;
                            } else {
                                foreach($voucher->entries as $entry) {
                                    $debit = $entry->type === 'Dr' ? $entry->amount : 0;
                                    $credit = $entry->type === 'Cr' ? $entry->amount : 0;
                                    $lines[] = [
                                        'ledger_name' => $entry->ledger->getPath(),
                                        'narration' => $entry->narration,
                                        'debit' => $debit,
                                        'credit' => $credit
                                    ];
                                    $totalDr += $debit;
                                    $totalCr += $credit;
                                }
                            }
                        @endphp

                        @foreach($lines as $line)
                            <tr>
                                <td class="align-middle">{!! $line['ledger_name'] !!}</td>
                                <td class="align-middle text-muted">{{ $line['narration'] ?? '-' }}</td>
                                <td class="text-end align-middle fw-semibold">{{ $line['debit'] > 0 ? '₹' . number_format($line['debit'], 2) : '-' }}</td>
                                <td class="text-end align-middle fw-semibold">{{ $line['credit'] > 0 ? '₹' . number_format($line['credit'], 2) : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="2" class="text-end text-uppercase">Totals:</td>
                            <td class="text-end text-primary fs-6">₹{{ number_format($totalDr, 2) }}</td>
                            <td class="text-end text-primary fs-6">₹{{ number_format($totalCr, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Printable Signatures (Visible Only in Print) -->
            <div class="print-only mt-5 pt-4">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="border-top pt-2">
                            <span class="fw-bold">Prepared By</span>
                            <div class="small text-muted">{{ \App\Models\User::find($voucher->created_by)->name ?? 'System' }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border-top pt-2">
                            <span class="fw-bold">Verified By</span>
                            <div class="small text-muted">Accounts Department</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border-top pt-2">
                            <span class="fw-bold">Authorized Signatory</span>
                            <div class="small text-muted">Demo ERP System</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* Responsive Container & Table Styling */
.payment-voucher-container {
    max-width: 1200px;
}

.voucher-table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.print-only {
    display: none;
}

/* Print Styles: Hide all symbols, icons, buttons, widgets completely */
@media print {
    .print-hide, .no-print, .d-print-none,
    .print-hide *, .no-print *, .d-print-none *,
    .navbar, .sidebar, .topbar, .topbar *, .btn, button, a.btn,
    .global-chat-widget, .global-chat-widget *, .chat-toggle-btn, .chat-toggle-btn *,
    #chatToggleBtn, #chatWindow, .chat-window, .chat-window *, [class*="chat-"],
    .alert, .alert-dismissible, #toast-container, #toast-container *,
    .card-footer, .pagination {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        position: absolute !important;
        left: -9999px !important;
        top: -9999px !important;
        width: 0 !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .print-only {
        display: block !important;
        visibility: visible !important;
    }

    html, body {
        background: #fff !important;
        color: #000 !important;
        font-size: 9pt !important;
        overflow: visible !important;
        height: auto !important;
    }

    .container-fluid, .payment-voucher-container {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
    }

    .card, .print-voucher-card {
        border: 1px solid #cbd5e1 !important;
        box-shadow: none !important;
        background: transparent !important;
        margin-bottom: 0 !important;
    }

    .table-responsive, .voucher-table-container {
        overflow: visible !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 8.5pt !important;
    }

    table th, table td {
        border: 1px solid #cbd5e1 !important;
        padding: 5px 8px !important;
        color: #000 !important;
    }

    thead th {
        background-color: #f1f5f9 !important;
        color: #000 !important;
    }

    tr {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    @page {
        size: A4 portrait;
        margin: 12mm 10mm;
    }
}
</style>

<script>
function triggerCleanPrint() {
    window.print();
}
</script>
@endsection
