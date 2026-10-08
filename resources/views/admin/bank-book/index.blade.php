@extends('layouts.app')

@section('title', 'Bank Book - Demo ERP')
@section('header_title', 'Bank Book')

@section('content')
@php
    $currentLedger = $ledgerId === 'all' ? null : $bankLedgers->firstWhere('id', $ledgerId);
    $currentLedgerName = $ledgerId === 'all' ? 'All Bank Accounts' : ($currentLedger->name ?? 'Bank Account');
    $exportParams = [
        'financial_year_id' => $financialYear?->id,
        'ledger_id' => $ledgerId,
        'from_date' => $fromDate,
        'to_date' => $toDate,
        'voucher_type' => request('voucher_type'),
        'search' => request('search'),
    ];
@endphp
<div class="container-fluid">
    <!-- Header Row (Hidden in Print) -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 print-hide d-print-none">
        <div>
            <h2 class="h3 mb-1 text-gray-800 fw-bold">Bank Book</h2>
            <p class="text-muted mb-0 small">Bank transaction register, balances & reconciliation status</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('bank-book.export', $exportParams) }}" class="btn btn-outline-secondary btn-sm shadow-sm" title="Export CSV">
                <i class="ph ph-file-csv me-1"></i> Export CSV
            </a>
            <a href="{{ route('bank-book.pdf', $exportParams) }}" class="btn btn-outline-danger btn-sm shadow-sm" target="_blank" title="Download / Preview PDF">
                <i class="ph ph-file-pdf me-1"></i> PDF
            </a>
            <button class="btn btn-primary btn-sm shadow-sm" onclick="window.print()" title="Print Bank Book">
                <i class="ph ph-printer me-1"></i> Print / Save PDF
            </button>
        </div>
    </div>

    <!-- Filter Card (Hidden in Print) -->
    <div class="card shadow-sm border-0 mb-4 print-hide d-print-none">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('bank-book.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold small text-muted">Financial Year</label>
                    <select name="financial_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy->id }}" {{ $financialYear && $fy->id == $financialYear->id ? 'selected' : '' }}>
                                {{ $fy->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold small text-muted">Bank Ledger</label>
                    <select name="ledger_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ $ledgerId === 'all' ? 'selected' : '' }}>-- All Bank Accounts --</option>
                        @foreach($bankLedgers as $ledger)
                            <option value="{{ $ledger->id }}" {{ $ledgerId == $ledger->id ? 'selected' : '' }}>
                                {{ $ledger->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold small text-muted">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}" required>
                </div>

                <div class="col-6 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold small text-muted">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}" required>
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold small text-muted">Search / Particulars</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Vch no, party, ref..." value="{{ request('search') }}">
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">
                            <i class="ph ph-magnifying-glass me-1"></i> Apply
                        </button>
                        <a href="{{ route('bank-book.index') }}" class="btn btn-light border btn-sm w-100 text-secondary">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Official Printable Header (Visible Only in Print) -->
    <div class="print-only mb-3 pb-3 border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-0 text-dark">DEMO ERP SYSTEM</h3>
                <div class="small text-muted">Accounting & Financial Records</div>
            </div>
            <div class="text-end">
                <h4 class="fw-bold mb-0 text-primary">BANK BOOK REGISTER</h4>
                <div class="small">Bank Account: <strong class="text-dark">{{ $currentLedgerName }}</strong></div>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top small text-muted">
            <div>Financial Year: <strong class="text-dark">{{ $financialYear->name ?? '-' }}</strong></div>
            <div>Period: <strong class="text-dark">{{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }}</strong> to <strong class="text-dark">{{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}</strong></div>
            <div>Printed On: <strong class="text-dark">{{ now()->format('d-M-Y h:i A') }}</strong></div>
        </div>
    </div>

    @if($ledgerId && $financialYear)
    
    @if($ledgerId === 'all')
        @if($bankLedgers->isNotEmpty())
        <div class="alert alert-warning shadow-sm border-0 d-flex align-items-center mb-4 print-hide d-print-none">
            <i class="ph ph-warning-circle fs-3 me-3 text-warning"></i>
            <div>
                <strong>Consolidated View:</strong> You are viewing a consolidated report across all Bank Accounts. The running balance reflects the combined total.
            </div>
        </div>
        @else
        <div class="alert alert-info shadow-sm border-0 d-flex align-items-center mb-4 print-hide d-print-none">
            <i class="ph ph-info fs-3 me-3 text-info"></i>
            <div>
                <strong>No Bank Accounts Found:</strong> There are no bank ledgers created yet. You can add Bank Accounts in Masters / Banking.
            </div>
        </div>
        @endif
    @endif

    <!-- Screen View Summary Cards (Hidden in Print) -->
    <div class="row g-3 mb-4 print-hide d-print-none">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 bg-light h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-muted text-uppercase small mb-1">Opening Balance</h6>
                    <h5 class="mb-0 fw-bold {{ $openingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                        ₹{{ number_format($openingBalance['amount'], 2) }} <small class="fs-6 fw-semibold">{{ $openingBalance['type'] }}</small>
                    </h5>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-muted text-uppercase small mb-1">Total Receipts (Dr)</h6>
                    <h5 class="mb-0 fw-bold text-success">
                        ₹{{ number_format($summary['receipts'], 2) }}
                    </h5>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-muted text-uppercase small mb-1">Total Payments (Cr)</h6>
                    <h5 class="mb-0 fw-bold text-danger">
                        ₹{{ number_format($summary['payments'], 2) }}
                    </h5>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 bg-light h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-muted text-uppercase small mb-1">Closing Balance</h6>
                    <h5 class="mb-0 fw-bold {{ $closingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                        ₹{{ number_format($closingBalance['amount'], 2) }} <small class="fs-6 fw-semibold">{{ $closingBalance['type'] }}</small>
                    </h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Printable Summary Bar (Visible Only in Print) -->
    <div class="print-only mb-3">
        <div class="border rounded p-2 bg-light">
            <div class="row text-center small">
                <div class="col-3 border-end">
                    <span class="text-muted d-block">Opening Balance</span>
                    <strong class="{{ $openingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                        ₹{{ number_format($openingBalance['amount'], 2) }} {{ $openingBalance['type'] }}
                    </strong>
                </div>
                <div class="col-3 border-end">
                    <span class="text-muted d-block">Total Receipts (Dr)</span>
                    <strong class="text-success">₹{{ number_format($summary['receipts'], 2) }}</strong>
                </div>
                <div class="col-3 border-end">
                    <span class="text-muted d-block">Total Payments (Cr)</span>
                    <strong class="text-danger">₹{{ number_format($summary['payments'], 2) }}</strong>
                </div>
                <div class="col-3">
                    <span class="text-muted d-block">Closing Balance</span>
                    <strong class="{{ $closingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                        ₹{{ number_format($closingBalance['amount'], 2) }} {{ $closingBalance['type'] }}
                    </strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Bank Book Report Card & Table -->
    <div class="card shadow-sm border-0 mb-4 print-report-card">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 print-hide d-print-none">
            <div>
                <h5 class="mb-0 fw-bold text-dark">{{ $currentLedgerName }}</h5>
                <span class="text-muted small">Period: {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}</span>
            </div>
            <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill">
                {{ $transactions->total() }} Transactions
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 bank-book-table">
                    <thead class="bg-light table-group-divider text-muted">
                        <tr>
                            <th class="ps-3" style="width: 100px;">Date</th>
                            <th style="width: 110px;">Voucher No</th>
                            <th style="width: 85px;">Type</th>
                            @if($ledgerId === 'all')
                            <th style="width: 130px;">Bank Ledger</th>
                            @endif
                            <th>Particulars / Narration</th>
                            <th style="width: 95px;">Reference</th>
                            <th class="text-end" style="width: 120px;">Receipt (Dr)</th>
                            <th class="text-end" style="width: 120px;">Payment (Cr)</th>
                            <th class="text-end" style="width: 130px;">Balance</th>
                            <th class="text-center pe-3" style="width: 110px;">Recon Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($transactions->currentPage() == 1)
                        <tr class="table-light fw-bold opening-row">
                            <td class="ps-3">{{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary">OB</span></td>
                            <td>Opening</td>
                            @if($ledgerId === 'all')
                            <td>-</td>
                            @endif
                            <td>
                                <strong>Opening Balance b/f</strong>
                            </td>
                            <td>-</td>
                            <td class="text-end text-success">{{ $openingBalance['type'] == 'Dr' ? '₹' . number_format($openingBalance['amount'], 2) : '-' }}</td>
                            <td class="text-end text-danger">{{ $openingBalance['type'] == 'Cr' ? '₹' . number_format($openingBalance['amount'], 2) : '-' }}</td>
                            <td class="text-end {{ $openingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                                ₹{{ number_format($openingBalance['amount'], 2) }} {{ $openingBalance['type'] }}
                            </td>
                            <td class="text-center pe-3">-</td>
                        </tr>
                        @endif

                        @forelse($transactions as $t)
                            @php
                                $voucherRoute = '#';
                                if ($t->voucher) {
                                    $vType = $t->voucher->type;
                                    if ($vType === 'Receipt' && \Illuminate\Support\Facades\Route::has('receipt-vouchers.show')) {
                                        $voucherRoute = route('receipt-vouchers.show', $t->voucher->id);
                                    } elseif ($vType === 'Payment' && \Illuminate\Support\Facades\Route::has('payment-vouchers.show')) {
                                        $voucherRoute = route('payment-vouchers.show', $t->voucher->id);
                                    } elseif ($vType === 'Contra' && \Illuminate\Support\Facades\Route::has('contra-vouchers.show')) {
                                        $voucherRoute = route('contra-vouchers.show', $t->voucher->id);
                                    } elseif ($vType === 'Journal' && \Illuminate\Support\Facades\Route::has('journal-vouchers.show')) {
                                        $voucherRoute = route('journal-vouchers.show', $t->voucher->id);
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="ps-3 text-nowrap">{{ $t->voucher->date->format('d-m-Y') }}</td>
                                <td class="fw-semibold text-nowrap">
                                    @if($voucherRoute !== '#')
                                        <a href="{{ $voucherRoute }}" class="text-decoration-none text-primary print-no-link" target="_blank" title="View Voucher">{{ $t->voucher->voucher_number }}</a>
                                    @else
                                        {{ $t->voucher->voucher_number }}
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $t->voucher->type }}</span>
                                </td>
                                @if($ledgerId === 'all')
                                <td><span class="badge bg-secondary-subtle text-secondary border">{{ $t->ledger->name ?? '-' }}</span></td>
                                @endif
                                <td>
                                    <div class="fw-semibold text-dark">{{ $t->particulars }}</div>
                                    @if($t->narration || $t->voucher->narration)
                                        <div class="text-muted small fst-italic">{{ $t->narration ?: $t->voucher->narration }}</div>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $t->voucher->reference_id ?? '-' }}</td>
                                <td class="text-end text-success fw-semibold">
                                    {{ $t->type == 'Dr' ? '₹' . number_format($t->amount, 2) : '-' }}
                                </td>
                                <td class="text-end text-danger fw-semibold">
                                    {{ $t->type == 'Cr' ? '₹' . number_format($t->amount, 2) : '-' }}
                                </td>
                                <td class="text-end fw-bold {{ $t->running_balance_type == 'Dr' ? 'text-success' : 'text-danger' }}">
                                    ₹{{ number_format($t->running_balance, 2) }} <small class="fw-normal">{{ $t->running_balance_type }}</small>
                                </td>
                                <td class="text-center pe-3">
                                    <span class="print-hide">
                                        @if($t->recon_status == 'Reconciled')
                                            <span class="badge bg-success" title="Reconciled">
                                                <i class="ph ph-check-circle"></i> Reconciled
                                            </span>
                                        @elseif($t->recon_status == 'Ignored')
                                            <span class="badge bg-secondary" title="Ignored">
                                                <i class="ph ph-prohibit"></i> Ignored
                                            </span>
                                        @else
                                            <a href="{{ route('bank-reconciliation.index', ['bank_ledger_id' => $t->ledger_id]) }}" class="badge bg-warning text-dark text-decoration-none" title="Go to Bank Reconciliation">
                                                <i class="ph ph-clock"></i> Unreconciled
                                            </a>
                                        @endif
                                    </span>
                                    <span class="print-only">
                                        {{ $t->recon_status == 'Reconciled' ? 'Reconciled' : ($t->recon_status == 'Ignored' ? 'Ignored' : 'Unreconciled') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $ledgerId === 'all' ? 10 : 9 }}" class="text-center py-5 text-muted">
                                    <i class="ph ph-receipt fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    No transactions found in this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-group-divider bg-light fw-bold">
                        <tr>
                            <td colspan="{{ $ledgerId === 'all' ? 6 : 5 }}" class="text-end pe-4">Period Totals:</td>
                            <td class="text-end text-success">₹{{ number_format($summary['receipts'], 2) }}</td>
                            <td class="text-end text-danger">₹{{ number_format($summary['payments'], 2) }}</td>
                            <td class="text-end {{ $closingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                                ₹{{ number_format($closingBalance['amount'], 2) }} {{ $closingBalance['type'] }}
                            </td>
                            <td class="pe-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-3 print-hide d-print-none">
            {{ $transactions->links() }}
        </div>
    </div>

    <!-- Official Printable Signatures Block (Visible Only in Print) -->
    <div class="print-only mt-5 pt-4">
        <div class="row text-center">
            <div class="col-4">
                <div class="border-top pt-2">
                    <p class="mb-0 fw-bold">Prepared By</p>
                    <small class="text-muted">Accountant / Cashier</small>
                </div>
            </div>
            <div class="col-4">
                <div class="border-top pt-2">
                    <p class="mb-0 fw-bold">Verified By</p>
                    <small class="text-muted">Internal Auditor</small>
                </div>
            </div>
            <div class="col-4">
                <div class="border-top pt-2">
                    <p class="mb-0 fw-bold">Authorized Signatory</p>
                    <small class="text-muted">Finance Head / Manager</small>
                </div>
            </div>
        </div>
    </div>
    @else
        <div class="alert alert-info shadow-sm">
            <i class="ph ph-info me-2"></i> Please select a Bank Ledger to view the Bank Book.
        </div>
    @endif
</div>

<style>
.print-only {
    display: none;
}

@media print {
    .print-hide, .no-print, .d-print-none,
    .print-hide *, .no-print *, .d-print-none *,
    .navbar, .sidebar, .topbar, .topbar *, .btn, button, a.btn,
    .global-chat-widget, .global-chat-widget *, .chat-toggle-btn, #chatToggleBtn, #chatWindow, .chat-window,
    .alert, .alert-dismissible, #toast-container, #toast-container *,
    .card-footer, .pagination {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }

    .print-only {
        display: block !important;
        visibility: visible !important;
    }

    html, body {
        background: #fff !important;
        color: #000 !important;
        font-size: 8.5pt !important;
        overflow: visible !important;
        height: auto !important;
    }

    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
    }

    .card, .print-report-card {
        border: 1px solid #cbd5e1 !important;
        box-shadow: none !important;
        background: transparent !important;
        margin-bottom: 0 !important;
    }

    .table-responsive {
        overflow: visible !important;
    }

    table, .bank-book-table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 8pt !important;
    }

    table th, table td {
        border: 1px solid #cbd5e1 !important;
        padding: 4px 6px !important;
        color: #000 !important;
    }

    table thead th {
        background-color: #f1f5f9 !important;
        color: #000 !important;
        font-weight: bold !important;
    }

    a.print-no-link {
        color: #000 !important;
        text-decoration: none !important;
    }

    tr {
        page-break-inside: avoid;
    }

    @page {
        size: A4 landscape;
        margin: 10mm;
    }
}
</style>
@endsection
