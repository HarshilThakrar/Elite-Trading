@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-0 text-gray-800">Bank Book</h2>
            <p class="text-muted mb-0">Bank transaction book & reconciliation status</p>
        </div>
        <div class="btn-group shadow-sm">
            <a href="{{ route('bank-book.export', request()->all()) }}" class="btn btn-light"><i class="ph ph-export"></i> Export</a>
            <a href="{{ route('bank-book.pdf', request()->all()) }}" class="btn btn-light" target="_blank"><i class="ph ph-file-pdf"></i> PDF</a>
            <button class="btn btn-light" onclick="window.print()"><i class="ph ph-printer"></i> Print</button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="{{ route('bank-book.index') }}" method="GET" class="row g-3 align-items-end">
                
                <div class="col-md-2">
                    <label class="form-label">Financial Year</label>
                    <select name="financial_year_id" class="form-select" onchange="this.form.submit()">
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy->id }}" {{ $financialYear && $fy->id == $financialYear->id ? 'selected' : '' }}>
                                {{ $fy->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Bank Ledger</label>
                    <select name="ledger_id" class="form-select" onchange="this.form.submit()">
                        <option value="all" {{ $ledgerId === 'all' ? 'selected' : '' }}>-- All Bank Accounts --</option>
                        @foreach($bankLedgers as $ledger)
                            <option value="{{ $ledger->id }}" {{ $ledgerId == $ledger->id ? 'selected' : '' }}>
                                {{ $ledger->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $toDate }}" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Search / Particulars</label>
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Apply Filters</button>
                    <a href="{{ route('bank-book.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    @if($ledgerId && $financialYear)
    
    @if($ledgerId === 'all')
        <div class="alert alert-warning shadow-sm border-0 d-flex align-items-center">
            <i class="ph ph-warning-circle fs-3 me-3 text-warning"></i>
            <div>
                <strong>Consolidated View:</strong> You are viewing a consolidated report of all Bank Accounts. The running balance reflects the combined total.
            </div>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body text-center">
                    <h6 class="text-muted text-uppercase mb-2">Opening Balance</h6>
                    <h4 class="mb-0 {{ $openingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                        ₹{{ number_format($openingBalance['amount'], 2) }} <small class="fs-6">{{ $openingBalance['type'] }}</small>
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-white">
                <div class="card-body text-center">
                    <h6 class="text-muted text-uppercase mb-2">Total Receipts (Deposits)</h6>
                    <h4 class="mb-0 text-success">
                        ₹{{ number_format($summary['receipts'], 2) }}
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-white">
                <div class="card-body text-center">
                    <h6 class="text-muted text-uppercase mb-2">Total Payments (Withdrawals)</h6>
                    <h4 class="mb-0 text-danger">
                        ₹{{ number_format($summary['payments'], 2) }}
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body text-center">
                    <h6 class="text-muted text-uppercase mb-2">Closing Balance</h6>
                    <h4 class="mb-0 {{ $closingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                        ₹{{ number_format($closingBalance['amount'], 2) }} <small class="fs-6">{{ $closingBalance['type'] }}</small>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Tally-style Report -->
    <div class="card shadow-sm border-0 mb-4 print-container">
        <div class="card-header bg-white py-3 border-bottom text-center">
            <h4 class="mb-1">BANK BOOK</h4>
            <p class="mb-0 text-muted">
                {{ $ledgerId === 'all' ? 'All Bank Accounts' : \App\Models\Ledger::find($ledgerId)->name }} <br>
                Period: {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}
            </p>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 text-nowrap">
                    <thead class="bg-light table-group-divider">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Voucher No</th>
                            <th>Type</th>
                            @if($ledgerId === 'all')
                            <th>Bank Ledger</th>
                            @endif
                            <th>Particulars</th>
                            <th>Reference</th>
                            <th class="text-end">Receipt (Dr)</th>
                            <th class="text-end">Payment (Cr)</th>
                            <th class="text-end">Balance</th>
                            <th class="text-center pe-4">Recon Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($transactions->currentPage() == 1)
                        <tr class="table-light fw-bold">
                            <td class="ps-4">{{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</td>
                            <td>OB</td>
                            <td>Opening</td>
                            @if($ledgerId === 'all')
                            <td>-</td>
                            @endif
                            <td>Opening Balance</td>
                            <td>-</td>
                            <td class="text-end text-success">{{ $openingBalance['type'] == 'Dr' ? number_format($openingBalance['amount'], 2) : '-' }}</td>
                            <td class="text-end text-danger">{{ $openingBalance['type'] == 'Cr' ? number_format($openingBalance['amount'], 2) : '-' }}</td>
                            <td class="text-end {{ $openingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                                {{ number_format($openingBalance['amount'], 2) }} {{ $openingBalance['type'] }}
                            </td>
                            <td class="text-center pe-4">-</td>
                        </tr>
                        @endif

                        @forelse($transactions as $t)
                            <tr>
                                <td class="ps-4">{{ $t->voucher->date->format('d-m-Y') }}</td>
                                <td>
                                    <!-- Drill down links -->
                                    @php
                                        $route = '#';
                                        if ($t->voucher->type == 'Receipt') $route = route('receipt-vouchers.show', $t->voucher->id);
                                        elseif ($t->voucher->type == 'Payment') $route = route('payment-vouchers.show', $t->voucher->id);
                                        elseif ($t->voucher->type == 'Contra') $route = route('contra-vouchers.show', $t->voucher->id);
                                        elseif ($t->voucher->type == 'Journal') $route = route('journal-vouchers.show', $t->voucher->id);
                                    @endphp
                                    @if($route !== '#')
                                        <a href="{{ $route }}" class="text-decoration-none fw-bold" target="_blank" title="View Voucher">{{ $t->voucher->voucher_number }}</a>
                                    @else
                                        {{ $t->voucher->voucher_number }}
                                    @endif
                                </td>
                                <td>{{ $t->voucher->type }}</td>
                                @if($ledgerId === 'all')
                                <td><span class="badge bg-secondary">{{ $t->ledger->name ?? '-' }}</span></td>
                                @endif
                                <td class="text-wrap" style="min-width: 200px;">
                                    {{ $t->particulars }}
                                    @if($t->narration || $t->voucher->narration)
                                        <div class="text-muted small">{{ $t->narration ?: $t->voucher->narration }}</div>
                                    @endif
                                </td>
                                <td>{{ $t->voucher->reference_id ?? '-' }}</td>
                                <td class="text-end text-success">
                                    {{ $t->type == 'Dr' ? number_format($t->amount, 2) : '-' }}
                                </td>
                                <td class="text-end text-danger">
                                    {{ $t->type == 'Cr' ? number_format($t->amount, 2) : '-' }}
                                </td>
                                <td class="text-end fw-bold {{ $t->running_balance_type == 'Dr' ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($t->running_balance, 2) }} <small>{{ $t->running_balance_type }}</small>
                                </td>
                                <td class="text-center pe-4">
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
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $ledgerId === 'all' ? 10 : 9 }}" class="text-center py-5 text-muted">
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
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-3">
            {{ $transactions->links() }}
        </div>
    </div>
    @else
        <div class="alert alert-info shadow-sm">
            <i class="ph ph-info me-2"></i> Please select a Bank Ledger to view the Bank Book.
        </div>
    @endif
</div>

<style>
@media print {
    body * { visibility: hidden; }
    .print-container, .print-container * { visibility: visible; }
    .print-container { position: absolute; left: 0; top: 0; width: 100%; }
    .card-footer { display: none; }
}
</style>
@endsection
