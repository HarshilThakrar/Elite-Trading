@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-0 text-gray-800">Cash Book</h2>
            <p class="text-muted mb-0">Cash-in-Hand transaction book</p>
        </div>
        <div class="btn-group shadow-sm">
            <!-- <a href="{{ route('cash-book.export', request()->all()) }}" class="btn btn-light"><i class="ph ph-export"></i> Export</a>
            <a href="{{ route('cash-book.pdf', request()->all()) }}" class="btn btn-light"><i class="ph ph-file-pdf"></i> PDF</a> -->
            <button class="btn btn-light" onclick="window.print()"><i class="ph ph-printer"></i> Print</button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="{{ route('cash-book.index') }}" method="GET" class="row g-3 align-items-end">
                
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
                    <label class="form-label">Cash Ledger</label>
                    <select name="ledger_id" class="form-select" onchange="this.form.submit()">
                        @foreach($cashLedgers as $ledger)
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
                    <a href="{{ route('cash-book.index') }}" class="btn btn-light w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    @if($ledgerId && $financialYear)
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
                    <h6 class="text-muted text-uppercase mb-2">Total Receipts</h6>
                    <h4 class="mb-0 text-success">
                        ₹{{ number_format($summary['receipts'], 2) }}
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-white">
                <div class="card-body text-center">
                    <h6 class="text-muted text-uppercase mb-2">Total Payments</h6>
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
            <h4 class="mb-1">CASH BOOK</h4>
            <p class="mb-0 text-muted">Period: {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}</p>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 text-nowrap">
                    <thead class="bg-light table-group-divider">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Voucher No</th>
                            <th>Type</th>
                            <th>Particulars</th>
                            <th>Reference</th>
                            <th class="text-end">Receipt (Dr)</th>
                            <th class="text-end">Payment (Cr)</th>
                            <th class="text-end pe-4">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($transactions->currentPage() == 1)
                        <tr class="table-light fw-bold">
                            <td class="ps-4">{{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</td>
                            <td>OB</td>
                            <td>Opening</td>
                            <td>Opening Balance</td>
                            <td>-</td>
                            <td class="text-end text-success">{{ $openingBalance['type'] == 'Dr' ? number_format($openingBalance['amount'], 2) : '-' }}</td>
                            <td class="text-end text-danger">{{ $openingBalance['type'] == 'Cr' ? number_format($openingBalance['amount'], 2) : '-' }}</td>
                            <td class="text-end pe-4 {{ $openingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                                {{ number_format($openingBalance['amount'], 2) }} {{ $openingBalance['type'] }}
                            </td>
                        </tr>
                        @endif

                        @forelse($transactions as $t)
                            <tr>
                                <td class="ps-4">{{ $t->voucher->date->format('d-m-Y') }}</td>
                                <td>{{ $t->voucher->voucher_number }}</td>
                                <td>{{ $t->voucher->type }}</td>
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
                                <td class="text-end pe-4 fw-bold {{ $t->running_balance_type == 'Dr' ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($t->running_balance, 2) }} {{ $t->running_balance_type }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    No transactions found in this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-group-divider bg-light fw-bold">
                        <tr>
                            <td colspan="5" class="text-end pe-4">Period Totals:</td>
                            <td class="text-end text-success">₹{{ number_format($summary['receipts'], 2) }}</td>
                            <td class="text-end text-danger">₹{{ number_format($summary['payments'], 2) }}</td>
                            <td class="pe-4"></td>
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
            <i class="ph ph-info me-2"></i> Please select a Cash Ledger to view the Cash Book.
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
