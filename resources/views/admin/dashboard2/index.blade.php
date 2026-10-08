@extends('layouts.app')

@section('title', 'Dashboard')
@section('header_title', 'Dashboard')

@section('content')
<style>
    .bg-light-primary { background-color: #f0f7ff !important; }
    .bg-light-success { background-color: #f0fdf4 !important; }
    .bg-light-warning { background-color: #fffbeb !important; }
    .bg-light-danger { background-color: #fef2f2 !important; }
    .bg-light-info { background-color: #f5f3ff !important; }
    
    .dashboard-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }
    
    .top-metric-card {
        padding: 20px;
        border-radius: 12px;
        height: 100%;
    }
    .top-metric-title {
        font-size: 14px;
        font-weight: 500;
        color: #4b5563;
        margin-bottom: 8px;
    }
    .top-metric-value {
        font-size: 28px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 8px;
    }
    .top-metric-footer {
        font-size: 13px;
        color: #6b7280;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 16px;
    }
</style>

<div class="container-fluid pb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800 d-none d-md-block"></h2>
        <form method="GET" action="{{ route('dashboard2') }}" class="d-flex align-items-center" style="gap:15px; margin-left:auto;">
            <label class="mb-0 text-muted" style="white-space: nowrap;">Financial Year</label>
            <select name="financial_year_id" class="form-select border-0 shadow-sm" onchange="this.form.submit()" style="min-width: 150px; background-color: white;">
                @foreach($financialYears as $fy)
                    <option value="{{ $fy->id }}" {{ $selectedFyId == $fy->id ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::parse($fy->start_date)->format('Y') }}-{{ \Carbon\Carbon::parse($fy->end_date)->format('y') }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- 1. Top Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col">
            <div class="top-metric-card bg-light-primary">
                <div class="top-metric-title">Total Sales (₹)</div>
                <div class="top-metric-value">{{ number_format($totalSales, 2) }}</div>
                <div class="top-metric-footer">
                    <span>This Financial Year</span>
                    <i class="ph ph-arrow-up text-success fs-5"></i>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="top-metric-card bg-light-success">
                <div class="top-metric-title">Total Purchases (₹)</div>
                <div class="top-metric-value">{{ number_format($totalPurchases, 2) }}</div>
                <div class="top-metric-footer">
                    <span>This Financial Year</span>
                    <i class="ph ph-arrow-up text-success fs-5"></i>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="top-metric-card bg-light-warning">
                <div class="top-metric-title">Total Receipts (₹)</div>
                <div class="top-metric-value">{{ number_format($totalReceipts, 2) }}</div>
                <div class="top-metric-footer">
                    <span>This Financial Year</span>
                    <i class="ph ph-arrow-up text-success fs-5"></i>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="top-metric-card bg-light-danger">
                <div class="top-metric-title">Total Payments (₹)</div>
                <div class="top-metric-value">{{ number_format($totalPayments, 2) }}</div>
                <div class="top-metric-footer">
                    <span>This Financial Year</span>
                    <i class="ph ph-arrow-up text-success fs-5"></i>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="top-metric-card bg-light-info">
                <div class="top-metric-title">Bank Balance (₹)</div>
                <div class="top-metric-value">{{ number_format($bankBalance, 2) }}</div>
                <div class="top-metric-footer">
                    <span>As on Today</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2nd Row: Cash/Bank Position, Income/Expense, Top Ledgers -->
    <div class="row g-4 mb-4">
        <!-- Cash & Bank Position -->
        <div class="col-md-3">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="section-title">Cash & Bank Position</div>
                    <div class="d-flex align-items-center mb-4">
                        <div style="width: 120px; height: 120px; position: relative;">
                            <canvas id="cashBankChart"></canvas>
                            <div style="position: absolute; top:50%; left:50%; transform: translate(-50%, -50%); width:70px; height:70px; border-radius:50%; background:white;"></div>
                        </div>
                        <div class="ms-3" style="font-size: 13px;">
                            <div class="mb-2">
                                <i class="ph-fill ph-square text-success me-1"></i> Cash in Hand<br>
                                <strong class="ms-3">{{ number_format($cashBalance, 2) }}</strong>
                            </div>
                            <div class="mb-2">
                                <i class="ph-fill ph-square text-primary me-1"></i> Bank Accounts<br>
                                <strong class="ms-3">{{ number_format($bankBalance, 2) }}</strong>
                            </div>
                            <div>
                                <i class="ph-fill ph-square text-danger me-1"></i> Unreconciled Bank<br>
                                <strong class="ms-3">{{ number_format($unreconciledAmt, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-3 fw-bold">
                        <span>Total</span>
                        <span>{{ number_format($cashBalance + $bankBalance, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Income vs Expense -->
        <div class="col-md-5">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="section-title">Income vs Expense</div>
                    <div class="d-flex mb-3 text-muted" style="font-size: 13px; gap: 15px;">
                        <div><i class="ph-fill ph-square text-success me-1"></i> Income</div>
                        <div><i class="ph-fill ph-square text-danger me-1"></i> Expense</div>
                    </div>
                    <div style="height: 200px;">
                        <canvas id="incomeExpenseChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top 5 Ledgers -->
        <div class="col-md-4">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="section-title">Top 5 Ledgers by Balance</div>
                    <table class="table table-sm table-borderless" style="font-size: 14px;">
                        <thead class="border-bottom text-muted">
                            <tr>
                                <th class="fw-normal px-0">Ledger</th>
                                <th class="text-end fw-normal px-0">Balance (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topLedgers as $l)
                                <tr>
                                    <td class="px-0 py-2">{{ $l['name'] }}</td>
                                    <td class="text-end px-0 py-2">{{ number_format($l['balance'], 2) }} {{ $l['type'] }}</td>
                                </tr>
                            @endforeach
                            @if(count($topLedgers) == 0)
                                <tr><td colspan="2" class="text-center py-3 text-muted">No data available.</td></tr>
                            @endif
                        </tbody>
                    </table>
                    <a href="{{ route('ledgers.index') }}" class="text-decoration-none" style="font-size: 13px;">View All</a>
                </div>
            </div>
        </div>
    </div>

    <!-- 3rd Row: Outstanding Summary, Voucher Summary, Bank Rec -->
    <div class="row g-4 mb-4">
        <!-- Outstanding Summary -->
        <div class="col-md-4">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title mb-0">Outstanding Summary</div>
                        <a href="{{ route('outstanding.index', ['financial_year_id' => $selectedFyId]) }}" class="text-decoration-none small text-primary fw-semibold" title="View Full Outstanding Analysis">
                            Full Analysis <i class="ph ph-arrow-up-right"></i>
                        </a>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-light-success border border-success border-opacity-10 h-100 d-flex flex-column justify-content-between" style="border-radius: 8px;">
                                <div>
                                    <div style="font-size: 12px; font-weight: 500;" class="mb-1 text-dark">Accounts Receivable (₹)</div>
                                    <div class="fs-4 fw-bold text-success mb-1">₹{{ number_format($arBalance, 2) }}</div>
                                    <div style="font-size: 11px;" class="text-muted mb-2">From {{ $arCount }} Customers</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-success border-opacity-10">
                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 fw-semibold text-success d-inline-flex align-items-center" style="font-size: 12px;" data-bs-toggle="modal" data-bs-target="#arDetailsModal">
                                        <i class="ph ph-list-magnifying-glass me-1"></i> View Details
                                    </button>
                                    <a href="{{ route('outstanding.index', ['type' => 'receivables', 'financial_year_id' => $selectedFyId]) }}" class="text-decoration-none text-muted" title="Open Full Receivables Report" style="font-size: 13px;">
                                        <i class="ph ph-arrow-square-out"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light-danger border border-danger border-opacity-10 h-100 d-flex flex-column justify-content-between" style="border-radius: 8px;">
                                <div>
                                    <div style="font-size: 12px; font-weight: 500;" class="mb-1 text-dark">Accounts Payable (₹)</div>
                                    <div class="fs-4 fw-bold text-danger mb-1">₹{{ number_format($apBalance, 2) }}</div>
                                    <div style="font-size: 11px;" class="text-muted mb-2">To {{ $apCount }} Vendors</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-danger border-opacity-10">
                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 fw-semibold text-danger d-inline-flex align-items-center" style="font-size: 12px;" data-bs-toggle="modal" data-bs-target="#apDetailsModal">
                                        <i class="ph ph-list-magnifying-glass me-1"></i> View Details
                                    </button>
                                    <a href="{{ route('outstanding.index', ['type' => 'payables', 'financial_year_id' => $selectedFyId]) }}" class="text-decoration-none text-muted" title="Open Full Payables Report" style="font-size: 13px;">
                                        <i class="ph ph-arrow-square-out"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Voucher Summary -->
        <div class="col-md-5">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="section-title">Voucher Summary <span class="text-muted fw-normal" style="font-size:13px;">(This FY)</span></div>
                    <div class="row g-4 mt-1">
                        <div class="col-6 d-flex align-items-center">
                            <i class="ph ph-shopping-cart fs-3 me-3 text-secondary"></i>
                            <div>
                                <div style="font-size: 12px;" class="text-muted">Sales Vouchers</div>
                                <div class="fw-bold fs-5">{{ $salesCount }}</div>
                            </div>
                        </div>
                        <div class="col-6 d-flex align-items-center">
                            <i class="ph ph-file-text fs-3 me-3 text-secondary"></i>
                            <div>
                                <div style="font-size: 12px;" class="text-muted">Purchase Vouchers</div>
                                <div class="fw-bold fs-5">{{ $purchaseCount }}</div>
                            </div>
                        </div>
                        <div class="col-6 d-flex align-items-center">
                            <i class="ph ph-battery-charging fs-3 me-3 text-secondary"></i>
                            <div>
                                <div style="font-size: 12px;" class="text-muted">Payment Vouchers</div>
                                <div class="fw-bold fs-5">{{ $paymentCount }}</div>
                            </div>
                        </div>
                        <div class="col-6 d-flex align-items-center">
                            <i class="ph ph-battery-plus fs-3 me-3 text-secondary"></i>
                            <div>
                                <div style="font-size: 12px;" class="text-muted">Receipt Vouchers</div>
                                <div class="fw-bold fs-5">{{ $receiptCount }}</div>
                            </div>
                        </div>
                        <div class="col-6 d-flex align-items-center">
                            <i class="ph ph-arrows-left-right fs-3 me-3 text-secondary"></i>
                            <div>
                                <div style="font-size: 12px;" class="text-muted">Contra Vouchers</div>
                                <div class="fw-bold fs-5">{{ $contraCount }}</div>
                            </div>
                        </div>
                        <div class="col-6 d-flex align-items-center">
                            <i class="ph ph-list-dashes fs-3 me-3 text-secondary"></i>
                            <div>
                                <div style="font-size: 12px;" class="text-muted">Journal Vouchers</div>
                                <div class="fw-bold fs-5">{{ $journalCount }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank Reconciliation -->
        <div class="col-md-3">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="section-title">Bank Reconciliation</div>
                    <div class="d-flex justify-content-between mb-3" style="font-size: 13px;">
                        <span class="text-muted">Unreconciled Transactions</span>
                        <span class="fw-bold">{{ $unreconciledTxns }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4" style="font-size: 13px;">
                        <span class="text-muted">Unreconciled Amount (₹)</span>
                        <span class="fw-bold">{{ number_format($unreconciledAmt, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4" style="font-size: 13px;">
                        <span class="text-muted">Last Reconciled On</span>
                        <span class="fw-bold">{{ $lastReconciledOn }}</span>
                    </div>
                    <a href="{{ route('bank-reconciliation.index') }}" class="text-decoration-none" style="font-size: 13px;">Go to Reconciliation</a>
                </div>
            </div>
        </div>
    </div>

    <!-- 4th Row: Recent Vouchers, Quick Links -->
    <div class="row g-4">
        <!-- Recent Vouchers -->
        <div class="col-md-9">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="section-title mb-0">Recent Vouchers</div>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold d-inline-flex align-items-center shadow-sm" data-bs-toggle="modal" data-bs-target="#allVouchersModal">
                            <i class="ph ph-list-bullets me-1 fs-6"></i> View All Vouchers ({{ $allVouchers->count() }})
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless align-middle" style="font-size: 13px;">
                            <thead class="border-bottom text-muted">
                                <tr>
                                    <th class="fw-normal px-0">Date</th>
                                    <th class="fw-normal">Type</th>
                                    <th class="fw-normal">Voucher No.</th>
                                    <th class="fw-normal">Party/Ledger</th>
                                    <th class="text-end fw-normal">Amount (₹)</th>
                                    <th class="text-center fw-normal">Status</th>
                                    <th class="text-end fw-normal">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentVouchers as $v)
                                @php
                                    $route = '#';
                                    $typeSlug = strtolower(str_replace(' ', '-', $v->type));
                                    if (\Illuminate\Support\Facades\Route::has($typeSlug . '-vouchers.show')) {
                                        $route = route($typeSlug . '-vouchers.show', $v->id);
                                    } elseif (\Illuminate\Support\Facades\Route::has($typeSlug . 's.show')) {
                                        $route = route($typeSlug . 's.show', $v->id);
                                    } elseif ($v->type === 'Debit Note' && \Illuminate\Support\Facades\Route::has('debit-notes.show')) {
                                        $route = route('debit-notes.show', $v->id);
                                    } elseif ($v->type === 'Credit Note' && \Illuminate\Support\Facades\Route::has('credit-notes.show')) {
                                        $route = route('credit-notes.show', $v->id);
                                    }
                                @endphp
                                <tr>
                                    <td class="px-0 py-2 text-nowrap">{{ \Carbon\Carbon::parse($v->date)->format('d-M-Y') }}</td>
                                    <td class="py-2">
                                        <span class="badge bg-light text-dark border">{{ $v->type }}</span>
                                    </td>
                                    <td class="py-2">
                                        @if($route !== '#')
                                            <a href="{{ $route }}" class="text-decoration-none fw-semibold text-primary">{{ $v->voucher_number }}</a>
                                        @else
                                            <span class="fw-semibold">{{ $v->voucher_number }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2">
                                        @if($v->entries->count() > 0)
                                            {{ $v->entries->first()->ledger->name ?? '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end py-2 fw-semibold">₹{{ number_format($v->entries->where('type','Dr')->sum('amount'), 2) }}</td>
                                    <td class="text-center py-2">
                                        <span class="badge bg-success bg-opacity-10 text-success">{{ $v->status }}</span>
                                    </td>
                                    <td class="text-end py-2">
                                        @if($route !== '#')
                                            <a href="{{ $route }}" class="btn btn-sm btn-outline-primary py-0 px-2" title="View Voucher">
                                                <i class="ph ph-eye"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-3 text-muted">No recent vouchers found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <button type="button" class="btn btn-sm btn-primary px-3 shadow-sm d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#allVouchersModal">
                            <i class="ph ph-list-magnifying-glass me-1 fs-6"></i> View All Vouchers ({{ $allVouchers->count() }})
                        </button>
                        <a href="{{ route('day-book.index', ['financial_year_id' => $selectedFyId]) }}" class="text-decoration-none small text-muted hover-primary fw-semibold d-inline-flex align-items-center" title="Open Day Book Register">
                            Open in Day Book <i class="ph ph-arrow-square-out ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="col-md-3">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="section-title">Quick Links</div>
                    <ul class="list-unstyled" style="font-size: 14px;">
                        <li class="mb-3"><a href="{{ route('sales.create') }}" class="text-decoration-none"><i class="ph ph-plus me-2"></i> Create Sales Order</a></li>
                        <li class="mb-3"><a href="{{ route('purchase-vouchers.select-po') }}" class="text-decoration-none"><i class="ph ph-plus me-2"></i> Create Purchase Voucher</a></li>
                        <li class="mb-3"><a href="{{ route('payment-vouchers.create') }}" class="text-decoration-none"><i class="ph ph-plus me-2"></i> Create Payment Voucher</a></li>
                        <li class="mb-3"><a href="{{ route('receipt-vouchers.create') }}" class="text-decoration-none"><i class="ph ph-plus me-2"></i> Create Receipt Voucher</a></li>
                        <li class="mb-3"><a href="{{ route('bank-reconciliation.index') }}" class="text-decoration-none"><i class="ph ph-plus me-2"></i> Bank Reconciliation</a></li>
                        <li class="mb-3"><a href="{{ route('reports.sales') }}" class="text-decoration-none"><i class="ph ph-list-dashes me-2"></i> View Reports</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Accounts Receivable (Debtors) Modal -->
<div class="modal fade" id="arDetailsModal" tabindex="-1" aria-labelledby="arDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-success mb-0" id="arDetailsModalLabel">
                        <i class="ph ph-users-three me-2"></i> Accounts Receivable Details (Customers)
                    </h5>
                    <small class="text-muted">Total Outstanding: ₹{{ number_format($arBalance, 2) }} across {{ $arCount }} Parties</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="ph ph-magnifying-glass"></i></span>
                        <input type="text" class="form-control" id="arSearchInput" placeholder="Search customer name..." onkeyup="filterArTable()">
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 380px;">
                    <table class="table table-hover align-middle table-sm" id="arTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>#</th>
                                <th>Customer / Party Name</th>
                                <th>Type</th>
                                <th class="text-end">Outstanding Amount (₹)</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($arParties as $idx => $p)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td class="fw-semibold text-dark ar-party-name">{{ $p['name'] }}</td>
                                <td><span class="badge bg-success-subtle text-success">Dr (Receivable)</span></td>
                                <td class="text-end fw-bold text-success">₹{{ number_format($p['balance'], 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('ledgers.show', $p['id']) }}" class="btn btn-outline-primary btn-sm py-0 px-2" title="View Customer Ledger">
                                        <i class="ph ph-book-open me-1"></i> Ledger
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="ph ph-check-circle fs-3 d-block text-success mb-2"></i>
                                    No outstanding receivables found for this financial year.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <a href="{{ route('outstanding.index', ['type' => 'receivables', 'financial_year_id' => $selectedFyId]) }}" class="btn btn-success btn-sm">
                    <i class="ph ph-chart-line-up me-1"></i> Full Aging & Invoices Report <i class="ph ph-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Accounts Payable (Creditors) Modal -->
<div class="modal fade" id="apDetailsModal" tabindex="-1" aria-labelledby="apDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold text-danger mb-0" id="apDetailsModalLabel">
                        <i class="ph ph-storefront me-2"></i> Accounts Payable Details (Vendors)
                    </h5>
                    <small class="text-muted">Total Outstanding: ₹{{ number_format($apBalance, 2) }} across {{ $apCount }} Parties</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="ph ph-magnifying-glass"></i></span>
                        <input type="text" class="form-control" id="apSearchInput" placeholder="Search vendor name..." onkeyup="filterApTable()">
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 380px;">
                    <table class="table table-hover align-middle table-sm" id="apTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>#</th>
                                <th>Vendor / Party Name</th>
                                <th>Type</th>
                                <th class="text-end">Outstanding Amount (₹)</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($apParties as $idx => $p)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td class="fw-semibold text-dark ap-party-name">{{ $p['name'] }}</td>
                                <td><span class="badge bg-danger-subtle text-danger">Cr (Payable)</span></td>
                                <td class="text-end fw-bold text-danger">₹{{ number_format($p['balance'], 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('ledgers.show', $p['id']) }}" class="btn btn-outline-danger btn-sm py-0 px-2" title="View Vendor Ledger">
                                        <i class="ph ph-book-open me-1"></i> Ledger
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="ph ph-check-circle fs-3 d-block text-success mb-2"></i>
                                    No outstanding payables found for this financial year.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <a href="{{ route('outstanding.index', ['type' => 'payables', 'financial_year_id' => $selectedFyId]) }}" class="btn btn-danger btn-sm">
                    <i class="ph ph-chart-line-down me-1"></i> Full Aging & Invoices Report <i class="ph ph-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- All Vouchers Modal -->
<div class="modal fade" id="allVouchersModal" tabindex="-1" aria-labelledby="allVouchersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white">
                <div class="d-flex align-items-center">
                    <i class="ph ph-receipt fs-4 me-2"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="allVouchersModalLabel">All Vouchers Register</h5>
                        <small class="text-white-50">
                            FY: {{ $selectedFy ? \Carbon\Carbon::parse($selectedFy->start_date)->format('Y') . '-' . \Carbon\Carbon::parse($selectedFy->end_date)->format('y') : 'All' }}
                            &bull; Total {{ $allVouchers->count() }} vouchers
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Filters and Search Toolbar -->
                <div class="row g-3 align-items-center mb-3">
                    <div class="col-lg-5 col-md-12">
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="ph ph-magnifying-glass text-muted"></i></span>
                            <input type="text" id="voucherSearchInput" class="form-control border-start-0 ps-0" placeholder="Search voucher no., party name, amount..." onkeyup="filterVouchersTable()">
                        </div>
                    </div>
                    <div class="col-lg-7 col-md-12">
                        <div class="d-flex flex-wrap gap-1 justify-content-lg-end" id="voucherTypeFilterGroup">
                            <button type="button" class="btn btn-sm btn-outline-secondary active voucher-filter-btn px-2" onclick="filterVoucherType('ALL', this)">
                                All <span class="badge bg-secondary ms-1">{{ $allVouchers->count() }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary voucher-filter-btn px-2" onclick="filterVoucherType('Sales', this)">
                                Sales <span class="badge bg-primary ms-1">{{ $allVouchers->where('type', 'Sales')->count() }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger voucher-filter-btn px-2" onclick="filterVoucherType('Purchase', this)">
                                Purchase <span class="badge bg-danger ms-1">{{ $allVouchers->where('type', 'Purchase')->count() }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning voucher-filter-btn px-2" onclick="filterVoucherType('Payment', this)">
                                Payment <span class="badge bg-warning text-dark ms-1">{{ $allVouchers->where('type', 'Payment')->count() }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success voucher-filter-btn px-2" onclick="filterVoucherType('Receipt', this)">
                                Receipt <span class="badge bg-success ms-1">{{ $allVouchers->where('type', 'Receipt')->count() }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info voucher-filter-btn px-2" onclick="filterVoucherType('Contra', this)">
                                Contra <span class="badge bg-info text-dark ms-1">{{ $allVouchers->where('type', 'Contra')->count() }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-dark voucher-filter-btn px-2" onclick="filterVoucherType('Journal', this)">
                                Journal <span class="badge bg-dark ms-1">{{ $allVouchers->where('type', 'Journal')->count() }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Vouchers Table -->
                <div class="table-responsive rounded border" style="max-height: 520px;">
                    <table class="table table-hover table-striped align-middle mb-0" id="allVouchersTable" style="font-size: 13px;">
                        <thead class="table-light sticky-top shadow-sm">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 110px;">Date</th>
                                <th style="width: 120px;">Type</th>
                                <th style="width: 140px;">Voucher No.</th>
                                <th>Party / Ledger</th>
                                <th class="text-end" style="width: 140px;">Amount (₹)</th>
                                <th class="text-center" style="width: 90px;">Status</th>
                                <th class="text-end" style="width: 100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allVouchers as $idx => $v)
                            @php
                                $route = '#';
                                $typeSlug = strtolower(str_replace(' ', '-', $v->type));
                                if (\Illuminate\Support\Facades\Route::has($typeSlug . '-vouchers.show')) {
                                    $route = route($typeSlug . '-vouchers.show', $v->id);
                                } elseif (\Illuminate\Support\Facades\Route::has($typeSlug . 's.show')) {
                                    $route = route($typeSlug . 's.show', $v->id);
                                } elseif ($v->type === 'Debit Note' && \Illuminate\Support\Facades\Route::has('debit-notes.show')) {
                                    $route = route('debit-notes.show', $v->id);
                                } elseif ($v->type === 'Credit Note' && \Illuminate\Support\Facades\Route::has('credit-notes.show')) {
                                    $route = route('credit-notes.show', $v->id);
                                }

                                $partyName = '-';
                                if ($v->entries && $v->entries->count() > 0) {
                                    $partyName = $v->entries->first()->ledger->name ?? '-';
                                }
                                $amount = $v->entries ? $v->entries->where('type', 'Dr')->sum('amount') : 0;
                            @endphp
                            <tr class="voucher-row" data-type="{{ $v->type }}" data-search="{{ strtolower($v->voucher_number . ' ' . $v->type . ' ' . $partyName . ' ' . number_format($amount, 2) . ' ' . ($v->narration ?? '')) }}">
                                <td>{{ $idx + 1 }}</td>
                                <td class="text-nowrap">{{ \Carbon\Carbon::parse($v->date)->format('d-M-Y') }}</td>
                                <td>
                                    @if($v->type === 'Sales')
                                        <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 px-2 py-1">Sales</span>
                                    @elseif($v->type === 'Purchase')
                                        <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 px-2 py-1">Purchase</span>
                                    @elseif($v->type === 'Payment')
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning border-opacity-25 px-2 py-1">Payment</span>
                                    @elseif($v->type === 'Receipt')
                                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1">Receipt</span>
                                    @elseif($v->type === 'Contra')
                                        <span class="badge bg-info-subtle text-info border border-info border-opacity-25 px-2 py-1">Contra</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary border-opacity-25 px-2 py-1">{{ $v->type }}</span>
                                    @endif
                                </td>
                                <td class="fw-bold">
                                    @if($route !== '#')
                                        <a href="{{ $route }}" class="text-decoration-none text-primary" title="View {{ $v->voucher_number }}">{{ $v->voucher_number }}</a>
                                    @else
                                        {{ $v->voucher_number }}
                                    @endif
                                </td>
                                <td class="text-truncate party-cell" style="max-width: 250px;" title="{{ $partyName }}">
                                    {{ $partyName }}
                                </td>
                                <td class="text-end fw-bold text-dark text-nowrap">
                                    ₹{{ number_format($amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success">{{ $v->status }}</span>
                                </td>
                                <td class="text-end text-nowrap">
                                    @if($route !== '#')
                                        <a href="{{ $route }}" class="btn btn-sm btn-outline-primary py-1 px-2 d-inline-flex align-items-center" title="View Details">
                                            <i class="ph ph-eye me-1"></i> View
                                        </a>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr id="emptyVouchersRow">
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="ph ph-files fs-2 d-block text-secondary mb-2"></i>
                                    No vouchers recorded for this financial year.
                                </td>
                            </tr>
                            @endforelse
                            <tr id="noResultsVouchersRow" style="display: none;">
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="ph ph-magnifying-glass fs-2 d-block text-secondary mb-2"></i>
                                    No matching vouchers found for this filter or search query.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small d-none d-md-inline">Need comprehensive register or printouts?</span>
                    <a href="{{ route('day-book.index', ['financial_year_id' => $selectedFyId]) }}" class="btn btn-primary btn-sm d-inline-flex align-items-center shadow-sm">
                        <i class="ph ph-book-open me-1"></i> Full Day Book Register <i class="ph ph-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let currentVoucherTypeFilter = 'ALL';

function filterVoucherType(type, btn) {
    currentVoucherTypeFilter = type;
    document.querySelectorAll('#voucherTypeFilterGroup .voucher-filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    filterVouchersTable();
}

function filterVouchersTable() {
    let search = (document.getElementById('voucherSearchInput').value || '').toLowerCase().trim();
    let rows = document.querySelectorAll('#allVouchersTable tbody tr.voucher-row');
    let visibleCount = 0;

    rows.forEach(row => {
        let type = row.getAttribute('data-type');
        let searchData = row.getAttribute('data-search') || '';
        
        let typeMatches = (currentVoucherTypeFilter === 'ALL' || type === currentVoucherTypeFilter);
        let searchMatches = !search || searchData.includes(search);

        if (typeMatches && searchMatches) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    let noResultsRow = document.getElementById('noResultsVouchersRow');
    if (noResultsRow) {
        if (visibleCount === 0 && rows.length > 0) {
            noResultsRow.style.display = '';
        } else {
            noResultsRow.style.display = 'none';
        }
    }
}
function filterArTable() {
    let input = document.getElementById('arSearchInput');
    let filter = input.value.toLowerCase();
    let rows = document.querySelectorAll('#arTable tbody tr');
    rows.forEach(row => {
        let nameEl = row.querySelector('.ar-party-name');
        if (nameEl) {
            let text = nameEl.textContent || nameEl.innerText;
            row.style.display = text.toLowerCase().includes(filter) ? '' : 'none';
        }
    });
}

function filterApTable() {
    let input = document.getElementById('apSearchInput');
    let filter = input.value.toLowerCase();
    let rows = document.querySelectorAll('#apTable tbody tr');
    rows.forEach(row => {
        let nameEl = row.querySelector('.ap-party-name');
        if (nameEl) {
            let text = nameEl.textContent || nameEl.innerText;
            row.style.display = text.toLowerCase().includes(filter) ? '' : 'none';
        }
    });
}

document.addEventListener("DOMContentLoaded", function() {
    // Pie Chart
    var ctxPie = document.getElementById('cashBankChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: ['Cash in Hand', 'Bank Accounts', 'Unreconciled Bank'],
            datasets: [{
                data: [{{ $cashBalance }}, {{ $bankBalance }}, {{ $unreconciledAmt }}],
                backgroundColor: ['#10b981', '#3b82f6', '#ef4444'],
                borderWidth: 0,
                cutout: '65%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true }
            }
        }
    });

    // Bar Chart
    var ctxBar = document.getElementById('incomeExpenseChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: {!! json_encode($months) !!},
            datasets: [
                {
                    label: 'Income',
                    data: {!! json_encode($incomeData) !!},
                    backgroundColor: '#10b981',
                    barPercentage: 0.6,
                    categoryPercentage: 0.4
                },
                {
                    label: 'Expense',
                    data: {!! json_encode($expenseData) !!},
                    backgroundColor: '#ef4444',
                    barPercentage: 0.6,
                    categoryPercentage: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [2, 2], drawBorder: false },
                    ticks: {
                        callback: function(value) {
                            if(value >= 100000) return (value / 100000) + 'L';
                            if(value >= 1000) return (value / 1000) + 'k';
                            return value;
                        },
                        font: { size: 11 }
                    }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { font: { size: 11 } }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>
@endsection
