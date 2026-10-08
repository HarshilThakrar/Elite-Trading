@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-0 text-gray-800">Profit & Loss Account</h2>
            <p class="text-muted mb-0">Accounting Profit & Loss Report</p>
        </div>
        <div class="btn-group shadow-sm print-hide">
            <a href="{{ route('profit-loss.export', request()->all()) }}" class="btn btn-light"><i class="ph ph-export"></i> Export</a>
            <a href="{{ route('profit-loss.pdf', request()->all()) }}" class="btn btn-light" target="_blank"><i class="ph ph-file-pdf"></i> PDF</a>
            <button class="btn btn-light" onclick="window.print()"><i class="ph ph-printer"></i> Print</button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4 print-hide">
        <div class="card-body">
            <form action="{{ route('profit-loss.index') }}" method="GET" class="row g-3 align-items-end">
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
                    <label class="form-label">Date From</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $toDate }}" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">View Mode</label>
                    <select name="view_mode" class="form-select">
                        <option value="grouped" {{ $filters['view_mode'] == 'grouped' ? 'selected' : '' }}>Grouped (Tally)</option>
                        <option value="detailed" {{ $filters['view_mode'] == 'detailed' ? 'selected' : '' }}>Detailed (Flat)</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="show_zero" value="1" id="showZero" {{ $filters['show_zero'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="showZero">
                            Show Zero Balances
                        </label>
                    </div>
                </div>

                <div class="col-md-2 ms-auto text-end">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    @if($report)
    
    @php
        $net = $report['net_profit'];
    @endphp

    <!-- Summary Cards -->
    <div class="row mb-4 print-hide">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-success text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1" style="opacity: 0.8">Total Income</h6>
                    <h3 class="mb-0">₹{{ number_format($report['total_income'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-danger text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1" style="opacity: 0.8">Total Expenses</h6>
                    <h3 class="mb-0">₹{{ number_format($report['total_expenses'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 {{ $net > 0 ? 'bg-primary' : ($net < 0 ? 'bg-warning text-dark' : 'bg-secondary') }} text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1" style="opacity: 0.8">{{ $net > 0 ? 'Net Profit' : ($net < 0 ? 'Net Loss' : 'Net Result') }}</h6>
                    <h3 class="mb-0">₹{{ number_format(abs($net), 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Profit & Loss Table -->
    <div class="card shadow-sm border-0 mb-4 print-container">
        <div class="card-header bg-white py-3 border-bottom text-center">
            <h4 class="mb-1 text-uppercase">PROFIT & LOSS ACCOUNT</h4>
            <p class="mb-0 text-muted">
                Financial Year: {{ $financialYear->name }} <br>
                Period: {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}
            </p>
        </div>
        <div class="card-body p-0">
            <div class="row g-0">
                
                <!-- Expenses Side -->
                <div class="col-md-6 border-end">
                    <h5 class="p-3 mb-0 bg-light border-bottom text-center">Particulars (Expenses)</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <tbody>
                                @if($filters['view_mode'] == 'detailed')
                                    @foreach($report['expense_rows'] as $row)
                                    <tr>
                                        <td class="ps-4">
                                            <a href="{{ route('ledgers.show', ['ledger' => $row['ledger']->id, 'financial_year_id' => $financialYear->id, 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="text-decoration-none fw-bold text-dark">
                                                {{ $row['ledger']->name }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $row['ledger']->accountGroup->name ?? 'Unknown Group' }}</small>
                                        </td>
                                        <td class="text-end fw-bold pe-4">₹{{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                @else
                                    @php
                                        $renderGroup = function($node, $groupId, $level, $allGroups) use (&$renderGroup, $financialYear, $fromDate, $toDate) {
                                            $group = $allGroups->get($groupId);
                                            $padding = $level * 20;
                                            
                                            echo '<tr class="bg-light">';
                                            echo '<td class="fw-bold" style="padding-left: ' . (15 + $padding) . 'px;">' . strtoupper($group->name ?? 'Unknown') . '</td>';
                                            echo '<td class="text-end fw-bold pe-4">' . number_format($node['amount'], 2) . '</td>';
                                            echo '</tr>';

                                            foreach ($node['ledgers'] as $row) {
                                                $ledgerUrl = route('ledgers.show', ['ledger' => $row['ledger']->id, 'financial_year_id' => $financialYear->id, 'from_date' => $fromDate, 'to_date' => $toDate]);
                                                echo '<tr>';
                                                echo '<td style="padding-left: ' . (30 + $padding) . 'px;">';
                                                echo '<a href="'.$ledgerUrl.'" class="text-decoration-none text-dark">'.$row['ledger']->name.'</a>';
                                                echo '</td>';
                                                echo '<td class="text-end pe-4">' . number_format($row['amount'], 2) . '</td>';
                                                echo '</tr>';
                                            }

                                            foreach ($node['children'] as $childId => $childNode) {
                                                $renderGroup($childNode, $childId, $level + 1, $allGroups);
                                            }
                                        };
                                    @endphp

                                    @foreach($report['grouped_expense']['tree'] as $groupId => $node)
                                        @php $renderGroup($node, $groupId, 0, $report['grouped_expense']['allGroups']); @endphp
                                    @endforeach
                                @endif
                                
                                @if($net > 0)
                                    <tr>
                                        <td class="fw-bold text-primary ps-4">NET PROFIT</td>
                                        <td class="text-end fw-bold text-primary pe-4">₹{{ number_format($net, 2) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Income Side -->
                <div class="col-md-6">
                    <h5 class="p-3 mb-0 bg-light border-bottom text-center">Particulars (Income)</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <tbody>
                                @if($filters['view_mode'] == 'detailed')
                                    @foreach($report['income_rows'] as $row)
                                    <tr>
                                        <td class="ps-4">
                                            <a href="{{ route('ledgers.show', ['ledger' => $row['ledger']->id, 'financial_year_id' => $financialYear->id, 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="text-decoration-none fw-bold text-dark">
                                                {{ $row['ledger']->name }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $row['ledger']->accountGroup->name ?? 'Unknown Group' }}</small>
                                        </td>
                                        <td class="text-end fw-bold pe-4">₹{{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                @else
                                    @foreach($report['grouped_income']['tree'] as $groupId => $node)
                                        @php $renderGroup($node, $groupId, 0, $report['grouped_income']['allGroups']); @endphp
                                    @endforeach
                                @endif

                                @if($net < 0)
                                    <tr>
                                        <td class="fw-bold text-warning ps-4">NET LOSS</td>
                                        <td class="text-end fw-bold text-warning pe-4">₹{{ number_format(abs($net), 2) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            
            <div class="row g-0 border-top bg-dark text-white fw-bold">
                <div class="col-6 p-3 d-flex justify-content-between border-end">
                    <span>TOTAL</span>
                    <span>₹{{ number_format(max($report['total_expenses'], $report['total_income']), 2) }}</span>
                </div>
                <div class="col-6 p-3 d-flex justify-content-between">
                    <span>TOTAL</span>
                    <span>₹{{ number_format(max($report['total_expenses'], $report['total_income']), 2) }}</span>
                </div>
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
