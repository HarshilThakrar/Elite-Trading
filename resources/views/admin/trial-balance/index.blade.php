@extends('layouts.app')

@section('title', 'Trial Balance - Demo ERP')
@section('header_title', 'Trial Balance')

@section('content')
@php
    $exportParams = array_merge(request()->all(), [
        'financial_year_id' => $financialYear?->id,
        'account_group_id' => $filters['account_group_id'],
        'ledger_id' => $filters['ledger_id'],
        'from_date' => $fromDate,
        'to_date' => $toDate,
        'search' => $filters['search'],
        'show_zero' => $filters['show_zero'] ? '1' : '0',
        'view_mode' => $filters['view_mode'],
    ]);
@endphp
<div class="container-fluid">
    <!-- Header Row (Hidden in Print) -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 print-hide d-print-none">
        <div>
            <h2 class="h3 mb-1 text-gray-800 fw-bold">Trial Balance</h2>
            <p class="text-muted mb-0 small">General Ledger balances summary & verification</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('trial-balance.export', $exportParams) }}" class="btn btn-outline-secondary btn-sm shadow-sm" title="Export CSV">
                <i class="ph ph-file-csv me-1"></i> Export CSV
            </a>
            <a href="{{ route('trial-balance.pdf', $exportParams) }}" class="btn btn-outline-danger btn-sm shadow-sm" target="_blank" title="Download / Preview PDF">
                <i class="ph ph-file-pdf me-1"></i> PDF
            </a>
            <button class="btn btn-primary btn-sm shadow-sm" onclick="window.print()" title="Print Trial Balance">
                <i class="ph ph-printer me-1"></i> Print / Save PDF
            </button>
        </div>
    </div>

    <!-- Filter Card (Hidden in Print) -->
    <div class="card shadow-sm border-0 mb-4 print-hide d-print-none">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('trial-balance.index') }}" method="GET" id="tbFilterForm" class="row g-3 align-items-end">
                
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold small text-muted">Financial Year</label>
                    <select name="financial_year_id" id="financialYearSelect" class="form-select form-select-sm">
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy->id }}" 
                                data-start="{{ $fy->start_date->format('Y-m-d') }}" 
                                data-end="{{ min($fy->end_date, Carbon\Carbon::now())->format('Y-m-d') }}"
                                {{ $financialYear && $fy->id == $financialYear->id ? 'selected' : '' }}>
                                {{ $fy->name }} ({{ $fy->start_date->format('M Y') }} - {{ $fy->end_date->format('M Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold small text-muted">Account Group</label>
                    <select name="account_group_id" id="accountGroupSelect" class="form-select form-select-sm">
                        <option value="">-- All Groups --</option>
                        @foreach($accountGroups as $grp)
                            <option value="{{ $grp->id }}" {{ $filters['account_group_id'] == $grp->id ? 'selected' : '' }}>
                                {{ $grp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold small text-muted">Ledger</label>
                    <select name="ledger_id" id="ledgerSelect" class="form-select form-select-sm">
                        <option value="">-- All Ledgers --</option>
                        @foreach($ledgers as $ledger)
                            <option value="{{ $ledger->id }}" data-group="{{ $ledger->account_group_id }}" {{ $filters['ledger_id'] == $ledger->id ? 'selected' : '' }}>
                                {{ $ledger->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold small text-muted">Date From</label>
                    <input type="date" name="from_date" id="fromDateInput" class="form-control form-control-sm" value="{{ $fromDate }}" required>
                </div>

                <div class="col-6 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold small text-muted">Date To</label>
                    <input type="date" name="to_date" id="toDateInput" class="form-control form-control-sm" value="{{ $toDate }}" required>
                </div>

                <div class="col-6 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold small text-muted">View Mode</label>
                    <select name="view_mode" class="form-select form-select-sm">
                        <option value="detailed" {{ $filters['view_mode'] == 'detailed' ? 'selected' : '' }}>Detailed (Flat)</option>
                        <option value="grouped" {{ $filters['view_mode'] == 'grouped' ? 'selected' : '' }}>Grouped (Tally)</option>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold small text-muted">Search Ledgers</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Code, Name..." value="{{ $filters['search'] }}">
                </div>
                
                <div class="col-12 col-sm-6 col-lg-2">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="show_zero" value="1" id="showZero" {{ $filters['show_zero'] ? 'checked' : '' }}>
                        <label class="form-check-label small text-muted fw-semibold" for="showZero">
                            Show Zero Balances
                        </label>
                    </div>
                </div>

                <div class="col-12 col-sm-12 col-lg-2 ms-auto">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">
                            <i class="ph ph-magnifying-glass me-1"></i> Apply
                        </button>
                        <a href="{{ route('trial-balance.index') }}" class="btn btn-light border btn-sm w-100 text-secondary">
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
                <div class="small text-muted">Accounting & Financial Records System</div>
            </div>
            <div class="text-end">
                <h4 class="fw-bold mb-0 text-primary">TRIAL BALANCE</h4>
                <div class="small">FY: <strong class="text-dark">{{ $financialYear->name ?? '-' }}</strong> | Mode: <strong class="text-dark">{{ ucfirst($filters['view_mode']) }}</strong></div>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top small text-muted">
            <div>Period: <strong class="text-dark">{{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }}</strong> to <strong class="text-dark">{{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}</strong></div>
            <div>Printed On: <strong class="text-dark">{{ now()->format('d-M-Y h:i A') }}</strong></div>
        </div>
    </div>

    @if($report)
    
    @php
        $gt = $report['grand_totals'];
        $diff = $gt['difference'];
    @endphp

    @if(abs($diff) > 0.01)
        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center mb-4 print-hide d-print-none">
            <i class="ph ph-warning-octagon fs-3 me-3 text-danger"></i>
            <div>
                <strong>TRIAL BALANCE IMBALANCE:</strong> The Trial Balance is not balanced. There is a difference of ₹{{ number_format(abs($diff), 2) }} {{ $diff > 0 ? 'Dr' : 'Cr' }}. Please check for orphan journal entries or data integrity issues.
            </div>
        </div>
    @endif

    <!-- Trial Balance Table Card -->
    <div class="card shadow-sm border-0 mb-4 print-report-card">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 print-hide d-print-none">
            <div>
                <h5 class="mb-0 fw-bold text-dark">Trial Balance Register</h5>
                <span class="text-muted small">Period: {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }} (FY {{ $financialYear->name }})</span>
            </div>
            <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill">
                {{ count($report['rows']) }} Ledgers
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 tb-table">
                    <thead class="bg-light table-group-divider text-center">
                        <tr>
                            <th rowspan="2" class="align-middle text-start ps-3" style="min-width: 220px;">Particulars</th>
                            <th colspan="2" class="border-bottom">Opening Balance</th>
                            <th colspan="2" class="border-bottom">Transactions</th>
                            <th colspan="2" class="border-bottom">Closing Balance</th>
                        </tr>
                        <tr>
                            <th class="text-end" style="width: 12%;">Debit (₹)</th>
                            <th class="text-end" style="width: 12%;">Credit (₹)</th>
                            <th class="text-end" style="width: 12%;">Debit (₹)</th>
                            <th class="text-end" style="width: 12%;">Credit (₹)</th>
                            <th class="text-end" style="width: 12%;">Debit (₹)</th>
                            <th class="text-end" style="width: 12%;">Credit (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($filters['view_mode'] == 'detailed')
                            <!-- DETAILED (FLAT) VIEW -->
                            @forelse($report['rows'] as $row)
                                <tr>
                                    <td class="ps-3">
                                        <a href="{{ route('ledgers.show', ['ledger' => $row['ledger']->id, 'financial_year_id' => $financialYear->id, 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="text-decoration-none fw-bold text-dark print-no-link">
                                            {{ $row['ledger']->name }}
                                        </a>
                                        <div class="text-muted small" style="font-size: 11px;">{{ $row['ledger']->accountGroup->name ?? 'Unknown Group' }}</div>
                                    </td>
                                    <td class="text-end {{ $row['opening_dr'] > 0 ? 'text-success' : 'text-muted' }}">{{ $row['opening_dr'] > 0 ? number_format($row['opening_dr'], 2) : '-' }}</td>
                                    <td class="text-end {{ $row['opening_cr'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $row['opening_cr'] > 0 ? number_format($row['opening_cr'], 2) : '-' }}</td>
                                    <td class="text-end {{ $row['period_dr'] > 0 ? 'text-success' : 'text-muted' }}">{{ $row['period_dr'] > 0 ? number_format($row['period_dr'], 2) : '-' }}</td>
                                    <td class="text-end {{ $row['period_cr'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $row['period_cr'] > 0 ? number_format($row['period_cr'], 2) : '-' }}</td>
                                    <td class="text-end {{ $row['closing_dr'] > 0 ? 'text-success fw-bold' : 'text-muted' }}">{{ $row['closing_dr'] > 0 ? number_format($row['closing_dr'], 2) : '-' }}</td>
                                    <td class="text-end {{ $row['closing_cr'] > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $row['closing_cr'] > 0 ? number_format($row['closing_cr'], 2) : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="ph ph-files fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        No accounting records found for the selected criteria.
                                    </td>
                                </tr>
                            @endforelse
                        @else
                            <!-- GROUPED (TALLY) VIEW -->
                            @if(empty($report['grouped']['tree']))
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="ph ph-files fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        No accounting records found for the selected criteria.
                                    </td>
                                </tr>
                            @else
                                @php
                                    $renderGroup = function($node, $groupId, $level) use (&$renderGroup, $report, $financialYear, $fromDate, $toDate) {
                                        if ($level > 8) return;
                                        $group = $report['grouped']['allGroups']->get($groupId);
                                        $padding = $level * 18;
                                        
                                        // Display Group Row
                                        echo '<tr class="bg-light fw-bold">';
                                        echo '<td style="padding-left: ' . (15 + $padding) . 'px;">' . strtoupper($group->name ?? 'Unknown Group') . '</td>';
                                        echo '<td class="text-end text-success">' . ($node['opening_dr'] > 0 ? number_format($node['opening_dr'], 2) : '-') . '</td>';
                                        echo '<td class="text-end text-danger">' . ($node['opening_cr'] > 0 ? number_format($node['opening_cr'], 2) : '-') . '</td>';
                                        echo '<td class="text-end text-success">' . ($node['period_dr'] > 0 ? number_format($node['period_dr'], 2) : '-') . '</td>';
                                        echo '<td class="text-end text-danger">' . ($node['period_cr'] > 0 ? number_format($node['period_cr'], 2) : '-') . '</td>';
                                        echo '<td class="text-end text-success">' . ($node['closing_dr'] > 0 ? number_format($node['closing_dr'], 2) : '-') . '</td>';
                                        echo '<td class="text-end text-danger">' . ($node['closing_cr'] > 0 ? number_format($node['closing_cr'], 2) : '-') . '</td>';
                                        echo '</tr>';

                                        // Display Ledgers
                                        foreach ($node['ledgers'] as $row) {
                                            $ledgerUrl = route('ledgers.show', ['ledger' => $row['ledger']->id, 'financial_year_id' => $financialYear->id, 'from_date' => $fromDate, 'to_date' => $toDate]);
                                            echo '<tr>';
                                            echo '<td style="padding-left: ' . (28 + $padding) . 'px;">';
                                            echo '<a href="'.$ledgerUrl.'" class="text-decoration-none text-dark print-no-link">'.$row['ledger']->name.'</a>';
                                            echo '</td>';
                                            echo '<td class="text-end ' . ($row['opening_dr'] > 0 ? 'text-success' : 'text-muted') . '">' . ($row['opening_dr'] > 0 ? number_format($row['opening_dr'], 2) : '-') . '</td>';
                                            echo '<td class="text-end ' . ($row['opening_cr'] > 0 ? 'text-danger' : 'text-muted') . '">' . ($row['opening_cr'] > 0 ? number_format($row['opening_cr'], 2) : '-') . '</td>';
                                            echo '<td class="text-end ' . ($row['period_dr'] > 0 ? 'text-success' : 'text-muted') . '">' . ($row['period_dr'] > 0 ? number_format($row['period_dr'], 2) : '-') . '</td>';
                                            echo '<td class="text-end ' . ($row['period_cr'] > 0 ? 'text-danger' : 'text-muted') . '">' . ($row['period_cr'] > 0 ? number_format($row['period_cr'], 2) : '-') . '</td>';
                                            echo '<td class="text-end ' . ($row['closing_dr'] > 0 ? 'text-success fw-bold' : 'text-muted') . '">' . ($row['closing_dr'] > 0 ? number_format($row['closing_dr'], 2) : '-') . '</td>';
                                            echo '<td class="text-end ' . ($row['closing_cr'] > 0 ? 'text-danger fw-bold' : 'text-muted') . '">' . ($row['closing_cr'] > 0 ? number_format($row['closing_cr'], 2) : '-') . '</td>';
                                            echo '</tr>';
                                        }

                                        // Display Children
                                        foreach ($node['children'] as $childId => $childNode) {
                                            $renderGroup($childNode, $childId, $level + 1);
                                        }
                                    };
                                @endphp

                                @foreach($report['grouped']['tree'] as $groupId => $node)
                                    @php $renderGroup($node, $groupId, 0); @endphp
                                @endforeach
                            @endif
                        @endif
                    </tbody>
                    <tfoot class="table-group-divider bg-dark text-white fw-bold">
                        <tr>
                            <td class="text-end pe-3">GRAND TOTAL:</td>
                            <td class="text-end">{{ number_format($gt['opening_dr'], 2) }}</td>
                            <td class="text-end">{{ number_format($gt['opening_cr'], 2) }}</td>
                            <td class="text-end">{{ number_format($gt['period_dr'], 2) }}</td>
                            <td class="text-end">{{ number_format($gt['period_cr'], 2) }}</td>
                            <td class="text-end text-success">{{ number_format($gt['closing_dr'], 2) }}</td>
                            <td class="text-end text-danger">{{ number_format($gt['closing_cr'], 2) }}</td>
                        </tr>
                        @if(abs($diff) > 0.01)
                        <tr class="bg-danger text-white">
                            <td class="text-end pe-3">DIFFERENCE:</td>
                            <td colspan="4" class="text-center">Trial Balance is out of balance by ₹{{ number_format(abs($diff), 2) }}</td>
                            <td class="text-end">{{ $diff > 0 ? number_format($diff, 2) : '-' }}</td>
                            <td class="text-end">{{ $diff < 0 ? number_format(abs($diff), 2) : '-' }}</td>
                        </tr>
                        @endif
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Official Printable Signatures Block (Visible Only in Print) -->
    <div class="print-only mt-5 pt-4">
        <div class="row text-center">
            <div class="col-4">
                <div class="border-top pt-2">
                    <p class="mb-0 fw-bold">Prepared By</p>
                    <small class="text-muted">Accountant</small>
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
                    <small class="text-muted">Finance Head / Director</small>
                </div>
            </div>
        </div>
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

    table, .tb-table {
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fySelect = document.getElementById('financialYearSelect');
    const fromInput = document.getElementById('fromDateInput');
    const toInput = document.getElementById('toDateInput');
    
    if (fySelect && fromInput && toInput) {
        fySelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.start && opt.dataset.end) {
                fromInput.value = opt.dataset.start;
                toInput.value = opt.dataset.end;
            }
        });
    }

    // Filter ledgers by selected group dynamically in frontend
    const groupSelect = document.getElementById('accountGroupSelect');
    const ledgerSelect = document.getElementById('ledgerSelect');
    
    if (groupSelect && ledgerSelect) {
        groupSelect.addEventListener('change', function() {
            const selectedGroup = this.value;
            Array.from(ledgerSelect.options).forEach(opt => {
                if (!opt.value) {
                    opt.style.display = '';
                    return;
                }
                if (!selectedGroup || opt.dataset.group === selectedGroup) {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                }
            });
        });
    }
});
</script>
@endsection
