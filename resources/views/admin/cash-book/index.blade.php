@extends('layouts.app')

@section('title', 'Cash Book - Demo ERP')
@section('header_title', 'Cash Book')

@section('content')
@php
    $currentLedger = $cashLedgers->firstWhere('id', $ledgerId);
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
            <h2 class="h3 mb-1 text-gray-800 fw-bold">Cash Book</h2>
            <p class="text-muted mb-0 small">Cash-in-Hand transaction register and balance tracking</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('cash-book.export', $exportParams) }}" class="btn btn-outline-secondary btn-sm shadow-sm" title="Export CSV">
                <i class="ph ph-file-csv me-1"></i> Export CSV
            </a>
            <a href="{{ route('cash-book.pdf', $exportParams) }}" class="btn btn-outline-danger btn-sm shadow-sm" target="_blank" title="Download PDF">
                <i class="ph ph-file-pdf me-1"></i> PDF
            </a>
            <button class="btn btn-primary btn-sm shadow-sm" onclick="window.print()" title="Print Cash Book">
                <i class="ph ph-printer me-1"></i> Print / Save PDF
            </button>
        </div>
    </div>

    <!-- Filter Card (Hidden in Print) -->
    <div class="card shadow-sm border-0 mb-4 print-hide d-print-none">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('cash-book.index') }}" method="GET" class="row g-3 align-items-end">
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
                    <label class="form-label fw-semibold small text-muted">Cash Ledger</label>
                    <select name="ledger_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($cashLedgers as $ledger)
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
                    <label class="form-label fw-semibold small text-muted">Search Particulars</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Vch no, party..." value="{{ request('search') }}">
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">
                            <i class="ph ph-magnifying-glass me-1"></i> Apply
                        </button>
                        <a href="{{ route('cash-book.index') }}" class="btn btn-light border btn-sm w-100 text-secondary">
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
                <h4 class="fw-bold mb-0 text-primary">CASH BOOK REGISTER</h4>
                <div class="small">Ledger: <strong class="text-dark">{{ $currentLedger->name ?? 'Cash Account' }}</strong></div>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top small text-muted">
            <div>Financial Year: <strong class="text-dark">{{ $financialYear->name ?? '-' }}</strong></div>
            <div>Period: <strong class="text-dark">{{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }}</strong> to <strong class="text-dark">{{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}</strong></div>
            <div>Printed On: <strong class="text-dark">{{ now()->format('d-M-Y h:i A') }}</strong></div>
        </div>
    </div>

    @if($ledgerId && $financialYear)
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
                    <h6 class="text-muted text-uppercase small mb-1">Total Receipts</h6>
                    <h5 class="mb-0 fw-bold text-success">
                        ₹{{ number_format($summary['receipts'], 2) }}
                    </h5>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 bg-white h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-muted text-uppercase small mb-1">Total Payments</h6>
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

    <!-- Cash Book Report Card & Table -->
    <div class="card shadow-sm border-0 mb-4 print-report-card">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 print-hide d-print-none">
            <div>
                <h5 class="mb-0 fw-bold text-dark">{{ $currentLedger->name ?? 'Cash Account' }}</h5>
                <span class="text-muted small">Period: {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}</span>
            </div>
            <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill">
                {{ $transactions->total() }} Transactions
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 cash-book-table">
                    <thead class="bg-light table-group-divider text-muted">
                        <tr>
                            <th class="ps-3" style="width: 105px;">Date</th>
                            <th style="width: 120px;">Voucher No</th>
                            <th style="width: 90px;">Type</th>
                            <th>Particulars / Narration</th>
                            <th style="width: 100px;">Reference</th>
                            <th class="text-end" style="width: 125px;">Receipt (Dr)</th>
                            <th class="text-end" style="width: 125px;">Payment (Cr)</th>
                            <th class="text-end pe-3" style="width: 135px;">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($transactions->currentPage() == 1)
                        <tr class="table-light fw-bold opening-row">
                            <td class="ps-3">{{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary">OB</span></td>
                            <td>Opening</td>
                            <td>
                                <strong>Opening Balance b/f</strong>
                            </td>
                            <td>-</td>
                            <td class="text-end text-success">{{ $openingBalance['type'] == 'Dr' ? '₹' . number_format($openingBalance['amount'], 2) : '-' }}</td>
                            <td class="text-end text-danger">{{ $openingBalance['type'] == 'Cr' ? '₹' . number_format($openingBalance['amount'], 2) : '-' }}</td>
                            <td class="text-end pe-3 {{ $openingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                                ₹{{ number_format($openingBalance['amount'], 2) }} {{ $openingBalance['type'] }}
                            </td>
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
                                        <a href="{{ $voucherRoute }}" class="text-decoration-none text-primary print-no-link" title="View Voucher">{{ $t->voucher->voucher_number }}</a>
                                    @else
                                        {{ $t->voucher->voucher_number }}
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $t->voucher->type }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $t->particulars }}</div>
                                    @if($t->narration || $t->voucher->narration)
                                        <div class="text-muted small">{{ $t->narration ?: $t->voucher->narration }}</div>
                                    @endif
                                </td>
                                <td class="text-nowrap">{{ $t->voucher->reference_id ?? '-' }}</td>
                                <td class="text-end text-success fw-semibold text-nowrap">
                                    {{ $t->type == 'Dr' ? '₹' . number_format($t->amount, 2) : '-' }}
                                </td>
                                <td class="text-end text-danger fw-semibold text-nowrap">
                                    {{ $t->type == 'Cr' ? '₹' . number_format($t->amount, 2) : '-' }}
                                </td>
                                <td class="text-end pe-3 fw-bold text-nowrap {{ $t->running_balance_type == 'Dr' ? 'text-success' : 'text-danger' }}">
                                    ₹{{ number_format($t->running_balance, 2) }} <span class="small">{{ $t->running_balance_type }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="ph ph-receipt fs-1 d-block mb-2 text-secondary"></i>
                                    No cash transactions found for this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-group-divider bg-light fw-bold">
                        <tr>
                            <td colspan="5" class="text-end pe-3">Period Totals:</td>
                            <td class="text-end text-success text-nowrap">₹{{ number_format($summary['receipts'], 2) }}</td>
                            <td class="text-end text-danger text-nowrap">₹{{ number_format($summary['payments'], 2) }}</td>
                            <td class="pe-3"></td>
                        </tr>
                        <tr class="table-secondary">
                            <td colspan="5" class="text-end pe-3">Closing Balance:</td>
                            <td colspan="3" class="text-end pe-3 {{ $closingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                                ₹{{ number_format($closingBalance['amount'], 2) }} {{ $closingBalance['type'] }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-3 print-hide d-print-none">
            {{ $transactions->links() }}
        </div>
    </div>

    <!-- Printable Signatures (Visible Only in Print) -->
    <div class="print-only mt-5 pt-4">
        <div class="row text-center">
            <div class="col-4">
                <div class="border-top pt-2">
                    <span class="fw-bold">Prepared By</span>
                    <div class="small text-muted">{{ auth()->user()->name ?? 'Cashier' }}</div>
                </div>
            </div>
            <div class="col-4">
                <div class="border-top pt-2">
                    <span class="fw-bold">Checked & Verified By</span>
                    <div class="small text-muted">Internal Auditor / Accountant</div>
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

    @else
        <div class="alert alert-info shadow-sm">
            <i class="ph ph-info me-2"></i> Please select a Cash Ledger to view the Cash Book.
        </div>
    @endif
</div>

<style>
/* Clean Responsive Enhancements */
.cash-book-table th, .cash-book-table td {
    padding: 0.65rem 0.75rem;
    font-size: 0.875rem;
}

@media (max-width: 767.98px) {
    .cash-book-table th, .cash-book-table td {
        font-size: 0.8rem;
        padding: 0.5rem 0.5rem;
    }
}

/* Bulletproof Print Media Styles */
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
        font-size: 9pt !important;
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

    table, .cash-book-table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 8.5pt !important;
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

    @page {
        size: A4 portrait;
        margin: 12mm 10mm;
    }
}
</style>
@endsection
