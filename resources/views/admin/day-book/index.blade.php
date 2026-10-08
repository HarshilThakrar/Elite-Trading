@extends('layouts.app')

@section('content')
@php
    $exportParams = [
        'financial_year_id' => $financialYear?->id,
        'from_date' => $fromDate,
        'to_date' => $toDate,
        'voucher_type' => $filters['voucher_type'] ?? request('voucher_type'),
        'ledger_id' => $filters['ledger_id'] ?? request('ledger_id'),
        'search' => $filters['search'] ?? request('search'),
        'voucher_number' => $filters['voucher_number'] ?? request('voucher_number'),
    ];
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-0 text-gray-800">Day Book</h2>
            <p class="text-muted mb-0">Accounting Transaction Register</p>
        </div>
        <div class="btn-group shadow-sm print-hide">
            <a href="{{ route('day-book.export', $exportParams) }}" class="btn btn-light"><i class="ph ph-export"></i> Export</a>
            <a href="{{ route('day-book.pdf', $exportParams) }}" class="btn btn-light" target="_blank"><i class="ph ph-file-pdf"></i> PDF</a>
            <button class="btn btn-light" onclick="window.print()"><i class="ph ph-printer"></i> Print</button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4 print-hide">
        <div class="card-body">
            <form action="{{ route('day-book.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Financial Year</label>
                    <select name="financial_year_id" class="form-select select2">
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy->id }}" {{ $financialYear && $fy->id == $financialYear->id ? 'selected' : '' }}>
                                {{ $fy->name }} ({{ $fy->start_date->format('M Y') }} - {{ $fy->end_date->format('M Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $toDate }}" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Voucher Type</label>
                    <select name="voucher_type" class="form-select select2">
                        <option value="">All Types</option>
                        @foreach($voucherTypes as $type)
                            <option value="{{ $type }}" {{ $filters['voucher_type'] == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Ledger</label>
                    <select name="ledger_id" class="form-select select2">
                        <option value="">All Ledgers</option>
                        @foreach($ledgers as $ledger)
                            <option value="{{ $ledger->id }}" {{ $filters['ledger_id'] == $ledger->id ? 'selected' : '' }}>{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Voucher No.</label>
                    <input type="text" name="voucher_number" class="form-control" value="{{ $filters['voucher_number'] }}">
                </div>

                <div class="col-md-10">
                    <label class="form-label">Search (Narration, Reference, Ledger)</label>
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Type to search...">
                </div>

                <div class="col-md-2 text-end">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    @if($report)
    
    @if(!$report['is_balanced'])
        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center print-hide mb-4">
            <i class="ph ph-warning-octagon fs-3 me-3 text-danger"></i>
            <div>
                <strong>ACCOUNTING IMBALANCE DETECTED:</strong> There are {{ $report['unbalanced_count'] }} unbalanced vouchers on this page, or the total debit (₹{{ number_format($report['total_dr'], 2) }}) does not match the total credit (₹{{ number_format($report['total_cr'], 2) }}).
            </div>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="row mb-4 print-hide">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1" style="opacity: 0.8">Total Vouchers</h6>
                    <h3 class="mb-0">{{ $report['vouchers']->total() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-success text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1" style="opacity: 0.8">Total Debit</h6>
                    <h3 class="mb-0">₹{{ number_format($report['total_dr'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-danger text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1" style="opacity: 0.8">Total Credit</h6>
                    <h3 class="mb-0">₹{{ number_format($report['total_cr'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Day Book Table -->
    <div class="card shadow-sm border-0 mb-4 print-container">
        <div class="card-header bg-white py-3 border-bottom text-center">
            <h4 class="mb-1 text-uppercase">DAY BOOK</h4>
            <p class="mb-0 text-muted">
                Period: {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}<br>
                Financial Year: {{ $financialYear->name }}
            </p>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Particulars</th>
                            <th>Voucher Type</th>
                            <th>Voucher No.</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['vouchers'] as $voucher)
                            <!-- Voucher Header -->
                            <tr class="table-secondary">
                                <td class="fw-bold">{{ $voucher->date->format('d-M-Y') }}</td>
                                <td colspan="3" class="fw-bold">
                                    @php
                                        // Try to map to the correct show route based on type
                                        $route = '#';
                                        $type = strtolower(str_replace(' ', '-', $voucher->type));
                                        if (Route::has($type . '-vouchers.show')) {
                                            $route = route($type . '-vouchers.show', $voucher->id);
                                        } elseif (Route::has($type . 's.show')) {
                                            $route = route($type . 's.show', $voucher->id);
                                        } elseif ($voucher->type === 'Debit Note' && Route::has('debit-notes.show')) {
                                            $route = route('debit-notes.show', $voucher->id);
                                        } elseif ($voucher->type === 'Credit Note' && Route::has('credit-notes.show')) {
                                            $route = route('credit-notes.show', $voucher->id);
                                        }
                                    @endphp
                                    <a href="{{ $route }}" class="text-decoration-none text-dark">
                                        {{ $voucher->voucher_number }}
                                    </a>
                                    @if(!$voucher->is_balanced)
                                        <span class="badge bg-danger ms-2">Imbalanced</span>
                                    @endif
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            
                            <!-- Journal Entries -->
                            @foreach($voucher->entries as $entry)
                                <tr>
                                    <td></td>
                                    <td class="{{ $entry->type === 'Cr' ? 'ps-5' : 'ps-3 fw-bold' }}">
                                        {{ $entry->type === 'Cr' ? 'To ' : 'By ' }} {{ $entry->ledger->name }}
                                    </td>
                                    <td>{{ $voucher->type }}</td>
                                    <td></td>
                                    <td class="text-end fw-bold">{{ $entry->type === 'Dr' ? number_format($entry->amount, 2) : '' }}</td>
                                    <td class="text-end fw-bold">{{ $entry->type === 'Cr' ? number_format($entry->amount, 2) : '' }}</td>
                                </tr>
                            @endforeach

                            <!-- Narration -->
                            @if($voucher->narration)
                                <tr>
                                    <td></td>
                                    <td colspan="5" class="text-muted fst-italic ps-3">
                                        Narration: {{ $voucher->narration }}
                                    </td>
                                </tr>
                            @endif

                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No vouchers found for the selected criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <td colspan="4" class="text-end fw-bold">TOTAL FOR CURRENT PAGE</td>
                            <td class="text-end fw-bold">₹{{ number_format($report['total_dr'], 2) }}</td>
                            <td class="text-end fw-bold">₹{{ number_format($report['total_cr'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="p-3">
                {{ $report['vouchers']->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
    
    <!-- Daily Summary Card (Optional) -->
    <div class="card shadow-sm border-0 print-hide">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 text-uppercase">Daily Summary</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th class="text-center">Vouchers</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['daily_summary'] as $date => $summary)
                            <tr>
                                <td class="fw-bold">{{ \Carbon\Carbon::parse($date)->format('d-M-Y') }}</td>
                                <td class="text-center">{{ $summary['vouchers'] }}</td>
                                <td class="text-end">₹{{ number_format($summary['dr'], 2) }}</td>
                                <td class="text-end">₹{{ number_format($summary['cr'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @endif
</div>

<style>
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
    .card {
        border: 1px solid #cbd5e1 !important;
        box-shadow: none !important;
        background: transparent !important;
    }
    .table-responsive {
        overflow: visible !important;
    }
    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 8.5pt !important;
    }
    table th, table td {
        border: 1px solid #cbd5e1 !important;
        padding: 4px 6px !important;
        color: #000 !important;
    }
    @page {
        size: A4 portrait;
        margin: 12mm 10mm;
    }
}
</style>
@endsection
