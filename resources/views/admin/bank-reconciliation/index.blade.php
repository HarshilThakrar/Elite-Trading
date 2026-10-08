@extends('layouts.app')

@section('title', 'Bank Reconciliation')
@section('header_title', 'Bank Reconciliation')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Bank Reconciliation</h2>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('bank-reconciliation.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Select Bank Ledger <span class="text-danger">*</span></label>
                    <select name="bank_ledger_id" class="form-select select2" required>
                        <option value="">-- Choose Bank Account --</option>
                        @foreach($bankLedgers as $bLedger)
                            <option value="{{ $bLedger->id }}" {{ request('bank_ledger_id') == $bLedger->id ? 'selected' : '' }}>{{ $bLedger->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date', now()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ph ph-funnel me-1"></i> Load</button>
                </div>
            </form>
        </div>
    </div>

    @if($ledgerId && $summary)
    <div class="row">
        <!-- Summary Cards -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Book Balance (ERP)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($summary['book_balance'], 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="ph ph-book-bookmark fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Bank Statement Balance</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($summary['bank_balance'], 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="ph ph-bank fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Unreconciled Difference</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800 {{ abs($summary['difference']) > 0.01 ? 'text-danger' : '' }}">
                                ₹{{ number_format($summary['difference'], 2) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="ph ph-scales fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card shadow h-100 py-2">
                <div class="card-body d-flex flex-column justify-content-center gap-2">
                    <a href="{{ route('bank-reconciliation.reconcile', ['ledgerId' => $ledgerId, 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn btn-primary w-100">
                        <i class="ph ph-arrows-merge"></i> Start Reconciliation
                    </a>
                    <a href="{{ route('bank-reconciliation.import', ['ledgerId' => $ledgerId]) }}" class="btn btn-outline-secondary w-100">
                        <i class="ph ph-upload-simple"></i> Import Statement
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Reconciliation Summary Details</h6>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <td><strong>Balance as per Company Books</strong></td>
                        <td class="text-end"><strong>₹{{ number_format($summary['book_balance'], 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td>Add: Cheques issued but not presented to bank (Unreconciled Book Cr)</td>
                        <td class="text-end">₹{{ number_format($summary['unrec_book_cr'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Less: Cheques deposited but not cleared (Unreconciled Book Dr)</td>
                        <td class="text-end text-danger">-₹{{ number_format($summary['unrec_book_dr'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Add: Bank Credits (Deposits) not entered in books (Unreconciled Bank Cr)</td>
                        <td class="text-end">₹{{ number_format($summary['unrec_bank_cr'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Less: Bank Debits (Charges/Withdrawals) not entered in books (Unreconciled Bank Dr)</td>
                        <td class="text-end text-danger">-₹{{ number_format($summary['unrec_bank_dr'], 2) }}</td>
                    </tr>
                    <tr class="table-light">
                        <td><strong>Calculated Bank Balance</strong></td>
                        <td class="text-end"><strong>₹{{ number_format($summary['book_balance'] + $summary['unrec_book_cr'] - $summary['unrec_book_dr'] + $summary['unrec_bank_cr'] - $summary['unrec_bank_dr'], 2) }}</strong></td>
                    </tr>
                    <tr class="table-light">
                        <td><strong>Actual Bank Statement Balance</strong></td>
                        <td class="text-end"><strong>₹{{ number_format($summary['bank_balance'], 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @elseif($ledgerId)
    <div class="alert alert-warning mt-4">
        No summary available for this selection. Try adjusting the date range.
    </div>
    @else
    <div class="card shadow mt-4">
        <div class="card-body text-center py-5 text-muted">
            <i class="ph ph-bank display-1 mb-3 text-gray-300"></i>
            <h5>Select a Bank Ledger to begin reconciliation</h5>
            <p>You can import bank statements (CSV) and match them against your posted ERP transactions.</p>
        </div>
    </div>
    @endif
</div>
@endsection
