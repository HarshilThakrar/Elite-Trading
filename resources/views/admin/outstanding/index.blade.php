@extends('layouts.app')

@section('title', 'Outstanding Analysis - Demo ERP')
@section('header_title', 'Outstanding Analysis')

@section('content')
<div class="container-fluid outstanding-page-container">
    <!-- Top Action Bar (Hidden in Print) -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 print-hide d-print-none">
        <div>
            <h2 class="h3 mb-1 text-gray-800 fw-bold">Outstanding Analysis</h2>
            <p class="text-muted mb-0 small">Aging analysis, bill-by-bill tracking & FIFO settlement</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('outstanding.export', array_merge(request()->all(), ['type' => $type])) }}" class="btn btn-outline-secondary btn-sm shadow-sm" title="Export CSV">
                <i class="ph ph-file-csv me-1"></i> Export CSV
            </a>
            <a href="{{ route('outstanding.pdf', array_merge(request()->all(), ['type' => $type])) }}" class="btn btn-outline-danger btn-sm shadow-sm" target="_blank" title="Download / Preview PDF">
                <i class="ph ph-file-pdf me-1"></i> PDF
            </a>
            <button class="btn btn-primary btn-sm shadow-sm" onclick="triggerSmoothPrint()" title="Print Outstanding Analysis">
                <i class="ph ph-printer me-1"></i> Print / Save PDF
            </button>
        </div>
    </div>

    <!-- Navigation Tabs (Hidden in Print) -->
    <ul class="nav nav-pills mb-4 print-hide d-print-none p-1 bg-light rounded-3 d-inline-flex border">
        <li class="nav-item">
            <a class="nav-link px-4 py-2 fw-semibold {{ $type == 'receivables' ? 'active shadow-sm' : 'text-secondary' }}" 
               href="{{ route('outstanding.index', array_merge(request()->all(), ['type' => 'receivables'])) }}">
                <i class="ph ph-users me-1"></i> Receivables (Debtors)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link px-4 py-2 fw-semibold {{ $type == 'payables' ? 'active shadow-sm' : 'text-secondary' }}" 
               href="{{ route('outstanding.index', array_merge(request()->all(), ['type' => 'payables'])) }}">
                <i class="ph ph-storefront me-1"></i> Payables (Creditors)
            </a>
        </li>
    </ul>

    <!-- Filter Card (Hidden in Print) -->
    <div class="card shadow-sm border-0 mb-4 print-hide d-print-none">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('outstanding.index') }}" method="GET" id="filterForm" class="row g-3 align-items-end">
                <input type="hidden" name="type" value="{{ $type }}">
                
                <div class="col-12 col-sm-6 col-lg-3">
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
                
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label fw-semibold small text-muted">As Of Date</label>
                    <input type="date" name="as_of_date" id="asOfDateInput" class="form-control form-control-sm" value="{{ $asOfDate }}" required>
                </div>

                <div class="col-12 col-sm-8 col-lg-4">
                    <label class="form-label fw-semibold small text-muted">Search Party Name</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ph ph-magnifying-glass"></i></span>
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Customer or Vendor name...">
                    </div>
                </div>

                <div class="col-12 col-sm-4 col-lg-2 ms-auto">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">
                            <i class="ph ph-funnel me-1"></i> Apply
                        </button>
                        <a href="{{ route('outstanding.index', ['type' => $type]) }}" class="btn btn-light border btn-sm w-100 text-secondary" title="Reset Filters">
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
                <h4 class="fw-bold mb-0 text-primary">OUTSTANDING {{ strtoupper($type) }} ANALYSIS</h4>
                <div class="small">
                    As Of: <strong class="text-dark">{{ \Carbon\Carbon::parse($asOfDate)->format('d-M-Y') }}</strong> | 
                    FY: <strong class="text-dark">{{ $financialYear->name ?? '-' }}</strong>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top small text-muted">
            <div>Total Parties: <strong class="text-dark">{{ $report['summary']['party_count'] ?? 0 }}</strong></div>
            <div>Print Timestamp: <strong class="text-dark">{{ date('d-M-Y h:i A') }}</strong></div>
        </div>
    </div>

    @if($report)
    
    <!-- Aging Summary Cards (Screen View) -->
    <div class="row g-3 mb-4 print-hide d-print-none">
        <div class="col-12 col-md-4 col-xl-3">
            <div class="card shadow-sm border-0 bg-primary text-white h-100 rounded-3">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-uppercase small fw-bold opacity-75">Total {{ ucfirst($type) }}</span>
                            <i class="ph {{ $type == 'receivables' ? 'ph-hand-coins' : 'ph-credit-card' }} fs-4 opacity-75"></i>
                        </div>
                        <h3 class="fw-bold mb-0">₹{{ number_format($report['summary']['total_outstanding'], 2) }}</h3>
                    </div>
                    <div class="mt-2 pt-2 border-top border-white border-opacity-25 small opacity-90 d-flex justify-content-between">
                        <span>Active Parties:</span>
                        <strong>{{ $report['summary']['party_count'] }}</strong>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-8 col-xl-9">
            <div class="card shadow-sm border-0 h-100 rounded-3">
                <div class="card-body p-0">
                    <div class="row g-0 h-100 align-items-center text-center">
                        <div class="col-6 col-sm-4 col-md border-end border-bottom border-md-bottom-0 p-3">
                            <span class="text-muted d-block text-uppercase small fw-semibold mb-1">Not Due</span>
                            <h6 class="mb-0 fw-bold text-success">₹{{ number_format($report['summary']['aging']['not_due'], 2) }}</h6>
                        </div>
                        <div class="col-6 col-sm-4 col-md border-end border-bottom border-md-bottom-0 p-3 bg-light bg-opacity-50">
                            <span class="text-muted d-block text-uppercase small fw-semibold mb-1">0 - 30 Days</span>
                            <h6 class="mb-0 fw-bold text-dark">₹{{ number_format($report['summary']['aging']['0_30'], 2) }}</h6>
                        </div>
                        <div class="col-6 col-sm-4 col-md border-end border-bottom border-md-bottom-0 p-3 bg-light bg-opacity-50">
                            <span class="text-muted d-block text-uppercase small fw-semibold mb-1">31 - 60 Days</span>
                            <h6 class="mb-0 fw-bold text-dark">₹{{ number_format($report['summary']['aging']['31_60'], 2) }}</h6>
                        </div>
                        <div class="col-6 col-sm-6 col-md border-end p-3 bg-light bg-opacity-50">
                            <span class="text-muted d-block text-uppercase small fw-semibold mb-1">61 - 90 Days</span>
                            <h6 class="mb-0 fw-bold text-warning">₹{{ number_format($report['summary']['aging']['61_90'], 2) }}</h6>
                        </div>
                        <div class="col-6 col-sm-6 col-md p-3">
                            <span class="text-muted d-block text-uppercase small fw-semibold mb-1">> 90 Days</span>
                            <h6 class="mb-0 fw-bold text-danger">
                                ₹{{ number_format($report['summary']['aging']['91_180'] + $report['summary']['aging']['181_365'] + $report['summary']['aging']['above_365'], 2) }}
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Printable Summary Table (Visible Only in Print) -->
    <div class="print-only mb-3">
        <table class="table table-bordered text-center small mb-0" style="font-size: 8pt !important;">
            <thead class="table-light">
                <tr>
                    <th>Total Outstanding</th>
                    <th>Not Due</th>
                    <th>0 - 30 Days</th>
                    <th>31 - 60 Days</th>
                    <th>61 - 90 Days</th>
                    <th>> 90 Days (Critical)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="fw-bold fs-6">₹{{ number_format($report['summary']['total_outstanding'], 2) }}</td>
                    <td class="text-success fw-bold">₹{{ number_format($report['summary']['aging']['not_due'], 2) }}</td>
                    <td>₹{{ number_format($report['summary']['aging']['0_30'], 2) }}</td>
                    <td>₹{{ number_format($report['summary']['aging']['31_60'], 2) }}</td>
                    <td class="text-warning">₹{{ number_format($report['summary']['aging']['61_90'], 2) }}</td>
                    <td class="text-danger fw-bold">
                        ₹{{ number_format($report['summary']['aging']['91_180'] + $report['summary']['aging']['181_365'] + $report['summary']['aging']['above_365'], 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Outstanding Table Card -->
    <div class="card shadow-sm border-0 mb-4 print-report-card rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 print-hide d-print-none">
            <div>
                <h5 class="mb-0 fw-bold text-dark text-uppercase"><i class="ph ph-receipt me-1 text-primary"></i> {{ ucfirst($type) }} Aging Schedule</h5>
                <small class="text-muted">As Of {{ \Carbon\Carbon::parse($asOfDate)->format('d-M-Y') }} | Showing all active ledger balances</small>
            </div>
            <div>
                <span class="badge bg-light text-dark border px-3 py-2 fw-semibold">
                    <i class="ph ph-users me-1 text-primary"></i> Parties Found: {{ count($report['data']) }}
                </span>
            </div>
        </div>
        
        <div class="card-body p-0">
            <!-- Smooth Scrollable Table Wrapper -->
            <div class="table-responsive outstanding-scroll-container">
                <table class="table table-hover align-middle mb-0 outstanding-table">
                    <thead class="table-light sticky-table-header">
                        <tr>
                            <th style="min-width: 250px;">Party / Invoice Ref</th>
                            <th style="min-width: 105px;">Date</th>
                            <th style="min-width: 105px;">Due Date</th>
                            <th class="text-end" style="min-width: 130px;">Invoice Amount</th>
                            <th class="text-end" style="min-width: 120px;">Paid / Adj</th>
                            <th class="text-end" style="min-width: 130px;">Outstanding</th>
                            <th class="text-center" style="min-width: 110px;">Overdue</th>
                            <th class="text-center" style="min-width: 100px;">Bucket</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['data'] as $party)
                            <!-- Party Group Header -->
                            <tr class="party-group-row">
                                <td colspan="5" class="fw-bold py-2">
                                    <a href="{{ route('ledgers.show', ['ledger' => $party['ledger']->id, 'financial_year_id' => $financialYear->id, 'as_of_date' => $asOfDate]) }}" 
                                       class="text-decoration-none text-dark print-no-link d-inline-flex align-items-center">
                                        <i class="ph ph-folder-open me-2 text-primary fs-5"></i>
                                        <span>{{ $party['ledger']->name }}</span>
                                    </a>
                                </td>
                                <td class="text-end fw-bold {{ $party['total_outstanding'] < 0 ? 'text-success' : 'text-primary' }} fs-6">
                                    ₹{{ number_format($party['total_outstanding'], 2) }}
                                    @if($party['total_outstanding'] < 0) <small class="text-success fw-normal">(Adv)</small> @endif
                                </td>
                                <td colspan="2" class="text-end">
                                    <span class="badge bg-white text-muted border print-hide">{{ count($party['invoices']) }} item(s)</span>
                                </td>
                            </tr>
                            
                            <!-- Detailed Invoices / Advances -->
                            @foreach($party['invoices'] as $inv)
                                <tr class="invoice-item-row">
                                    <td class="ps-4">
                                        @if($inv['is_opening'])
                                            <span class="text-muted fw-semibold">
                                                <i class="ph ph-clock-counter-clockwise me-1 text-secondary"></i> {{ $inv['voucher_number'] }}
                                            </span>
                                        @else
                                            @php
                                                $route = '#';
                                                if(isset($inv['type']) && isset($inv['voucher_id'])) {
                                                    $vtype = strtolower(str_replace(' ', '-', $inv['type']));
                                                    if (Route::has($vtype . '-vouchers.show')) $route = route($vtype . '-vouchers.show', $inv['voucher_id']);
                                                    elseif (Route::has($vtype . 's.show')) $route = route($vtype . 's.show', $inv['voucher_id']);
                                                    elseif ($inv['type'] === 'Debit Note' && Route::has('debit-notes.show')) $route = route('debit-notes.show', $inv['voucher_id']);
                                                    elseif ($inv['type'] === 'Credit Note' && Route::has('credit-notes.show')) $route = route('credit-notes.show', $inv['voucher_id']);
                                                }
                                            @endphp
                                            @if($route !== '#')
                                                <a href="{{ $route }}" class="text-decoration-none fw-semibold text-primary print-no-link">
                                                    {{ $inv['voucher_number'] }}
                                                </a>
                                            @else
                                                <span class="text-dark fw-semibold">{{ $inv['voucher_number'] }}</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>{{ $inv['date']->format('d-M-Y') }}</td>
                                    <td>{{ $inv['due_date']->format('d-M-Y') }}</td>
                                    <td class="text-end">{{ $inv['amount'] > 0 ? '₹' . number_format($inv['amount'], 2) : '-' }}</td>
                                    <td class="text-end">{{ $inv['paid'] > 0 ? '₹' . number_format($inv['paid'], 2) : '-' }}</td>
                                    <td class="text-end fw-bold {{ $inv['outstanding'] < 0 ? 'text-success' : 'text-dark' }}">
                                        ₹{{ number_format($inv['outstanding'], 2) }}
                                        @if($inv['outstanding'] < 0) <small class="text-success">(Adv)</small> @endif
                                    </td>
                                    <td class="text-center">
                                        @if($inv['days'] > 0)
                                            <span class="badge-overdue overdue-active">
                                                <i class="ph ph-hourglass-simple me-1"></i>{{ (int)$inv['days'] }} {{ (int)$inv['days'] === 1 ? 'day' : 'days' }}
                                            </span>
                                        @else
                                            <span class="badge-overdue overdue-none">
                                                <i class="ph ph-shield-check me-1"></i>On Time
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $bucketKey = $inv['bucket'] ?? 'not_due';
                                            $bucketLabel = match($bucketKey) {
                                                'not_due' => 'Not Due',
                                                '0_30' => '0 - 30 Days',
                                                '31_60' => '31 - 60 Days',
                                                '61_90' => '61 - 90 Days',
                                                '91_180' => '91 - 180 Days',
                                                '181_365' => '181 - 365 Days',
                                                'above_365' => '> 365 Days',
                                                default => str_replace('_', ' ', ucwords($bucketKey))
                                            };
                                            $bucketClass = match($bucketKey) {
                                                'not_due' => 'bucket-pill-not-due',
                                                '0_30' => 'bucket-pill-0-30',
                                                '31_60' => 'bucket-pill-31-60',
                                                '61_90' => 'bucket-pill-61-90',
                                                '91_180' => 'bucket-pill-91-180',
                                                '181_365' => 'bucket-pill-181-365',
                                                'above_365' => 'bucket-pill-above-365',
                                                default => 'bucket-pill-not-due'
                                            };
                                            $bucketIcon = match($bucketKey) {
                                                'not_due' => 'ph-check-circle',
                                                '0_30' => 'ph-clock',
                                                '31_60' => 'ph-clock-countdown',
                                                '61_90' => 'ph-warning',
                                                '91_180' => 'ph-warning-octagon',
                                                '181_365' => 'ph-shield-warning',
                                                'above_365' => 'ph-flame',
                                                default => 'ph-tag'
                                            };
                                        @endphp
                                        <span class="badge-bucket {{ $bucketClass }}">
                                            <i class="ph {{ $bucketIcon }} me-1"></i>{{ $bucketLabel }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="ph ph-check-circle display-4 text-success mb-3 d-block"></i>
                                    <h6 class="fw-bold">No Outstanding Invoices Found</h6>
                                    <p class="small text-muted mb-0">All accounts are settled up to the selected As-Of Date.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-dark sticky-table-footer">
                        <tr>
                            <td colspan="5" class="text-end fw-bold py-3 text-uppercase">TOTAL OUTSTANDING</td>
                            <td class="text-end fw-bold fs-5 text-warning py-3">₹{{ number_format($report['summary']['total_outstanding'], 2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Official Print Footer (Visible Only in Print) -->
    <div class="print-only mt-4 pt-3 border-top d-flex justify-content-between align-items-center text-muted small">
        <div>Generated by Demo ERP System</div>
        <div>Page 1 of 1</div>
        <div>Authorised Signatory ___________________</div>
    </div>

    @endif
</div>

<style>
/* Smooth Scroll & Desktop UI Enhancements */
html {
    scroll-behavior: smooth;
}

.outstanding-scroll-container {
    max-height: 72vh;
    overflow-y: auto;
    overflow-x: auto;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
}

/* Custom Sleek Scrollbar */
.outstanding-scroll-container::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.outstanding-scroll-container::-webkit-scrollbar-track {
    background: #f1f5f9;
}
.outstanding-scroll-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.outstanding-scroll-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Sticky Header & Footer */
.sticky-table-header th {
    position: sticky;
    top: 0;
    z-index: 5;
    background-color: #f8fafc !important;
    border-bottom: 2px solid #e2e8f0;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}

.sticky-table-footer td {
    position: sticky;
    bottom: 0;
    z-index: 4;
}

.party-group-row {
    background-color: #f1f5f9 !important;
    border-top: 2px solid #e2e8f0 !important;
    border-left: 3px solid #3b82f6 !important;
}
.party-group-row:hover {
    background-color: #e2e8f0 !important;
}

.invoice-item-row:hover {
    background-color: #f8fafc !important;
}

/* Aging Bucket Badges */
.badge-bucket {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 50rem;
    white-space: nowrap;
    line-height: 1.25;
    transition: all 0.15s ease-in-out;
    letter-spacing: 0.2px;
}
.badge-bucket:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.06);
}

.bucket-pill-not-due {
    background-color: #ecfdf5 !important;
    color: #059669 !important;
    border: 1px solid rgba(16, 185, 129, 0.3) !important;
}
.bucket-pill-0-30 {
    background-color: #eff6ff !important;
    color: #1d4ed8 !important;
    border: 1px solid rgba(59, 130, 246, 0.3) !important;
}
.bucket-pill-31-60 {
    background-color: #eef2ff !important;
    color: #4338ca !important;
    border: 1px solid rgba(99, 102, 241, 0.3) !important;
}
.bucket-pill-61-90 {
    background-color: #fffbeb !important;
    color: #b45309 !important;
    border: 1px solid rgba(245, 158, 11, 0.35) !important;
}
.bucket-pill-91-180 {
    background-color: #fff7ed !important;
    color: #c2410c !important;
    border: 1px solid rgba(249, 115, 22, 0.35) !important;
}
.bucket-pill-181-365 {
    background-color: #fef2f2 !important;
    color: #dc2626 !important;
    border: 1px solid rgba(239, 68, 68, 0.35) !important;
}
.bucket-pill-above-365 {
    background-color: #fee2e2 !important;
    color: #991b1b !important;
    border: 1px solid rgba(220, 38, 38, 0.45) !important;
    font-weight: 700;
}

/* Overdue Days Badges */
.badge-overdue {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 50rem;
    white-space: nowrap;
    line-height: 1.25;
}
.overdue-active {
    background-color: #fef2f2 !important;
    color: #dc2626 !important;
    border: 1px solid rgba(239, 68, 68, 0.3) !important;
}
.overdue-none {
    background-color: #ecfdf5 !important;
    color: #059669 !important;
    border: 1px solid rgba(16, 185, 129, 0.3) !important;
}

.print-only {
    display: none;
}

/* Clean, Fast, Freeze-Free Print Styles */
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
        height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
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

    .container-fluid, .outstanding-page-container {
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

    .table-responsive, .outstanding-scroll-container {
        max-height: none !important;
        overflow: visible !important;
    }

    table, .outstanding-table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 8pt !important;
        table-layout: auto !important;
    }

    table th, table td {
        border: 1px solid #cbd5e1 !important;
        padding: 4px 6px !important;
        color: #000 !important;
    }

    table thead th, .sticky-table-header th {
        background-color: #f1f5f9 !important;
        color: #000 !important;
        position: static !important;
        box-shadow: none !important;
    }

    .party-group-row {
        background-color: #f8fafc !important;
        page-break-after: avoid;
    }

    a.print-no-link {
        color: #000 !important;
        text-decoration: none !important;
    }

    thead {
        display: table-header-group;
    }

    tr {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    @page {
        size: A4 landscape;
        margin: 8mm 10mm;
    }
}
</style>

<script>
// Smooth print trigger: clears any active focus or hover states before print
function triggerSmoothPrint() {
    window.print();
}

// Financial Year select auto-dates
document.addEventListener('DOMContentLoaded', function() {
    const fySelect = document.getElementById('financialYearSelect');
    const asOfInput = document.getElementById('asOfDateInput');
    
    if (fySelect && asOfInput) {
        fySelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.end) {
                asOfInput.value = opt.dataset.end;
            }
        });
    }
});
</script>
@endsection
