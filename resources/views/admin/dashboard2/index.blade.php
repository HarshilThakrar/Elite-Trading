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
                    <div class="section-title">Outstanding Summary</div>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-light-success" style="border-radius: 8px;">
                                <div style="font-size: 12px; font-weight: 500;" class="mb-1 text-dark">Accounts Receivable (₹)</div>
                                <div class="fs-4 fw-bold text-dark mb-1">{{ number_format($arBalance, 2) }}</div>
                                <div style="font-size: 11px;" class="text-muted mb-3">From {{ $arCount }} Customers</div>
                                <a href="#" class="text-decoration-none" style="font-size: 12px;">View Details</a>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light-danger" style="border-radius: 8px;">
                                <div style="font-size: 12px; font-weight: 500;" class="mb-1 text-dark">Accounts Payable (₹)</div>
                                <div class="fs-4 fw-bold text-dark mb-1">{{ number_format($apBalance, 2) }}</div>
                                <div style="font-size: 11px;" class="text-muted mb-3">To {{ $apCount }} Vendors</div>
                                <a href="#" class="text-decoration-none" style="font-size: 12px;">View Details</a>
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
                    <div class="section-title">Recent Vouchers</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless" style="font-size: 13px;">
                            <thead class="border-bottom text-muted">
                                <tr>
                                    <th class="fw-normal px-0">Date</th>
                                    <th class="fw-normal">Type</th>
                                    <th class="fw-normal">Voucher No.</th>
                                    <th class="fw-normal">Party/Ledger</th>
                                    <th class="text-end fw-normal">Amount (₹)</th>
                                    <th class="text-end fw-normal">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentVouchers as $v)
                                <tr>
                                    <td class="px-0 py-2">{{ \Carbon\Carbon::parse($v->date)->format('d-M-Y') }}</td>
                                    <td class="py-2">{{ $v->type }} Voucher</td>
                                    <td class="py-2">{{ $v->voucher_number }}</td>
                                    <td class="py-2">
                                        @if($v->entries->count() > 0)
                                            {{ $v->entries->first()->ledger->name }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end py-2">{{ number_format($v->entries->where('type','Dr')->sum('amount'), 2) }}</td>
                                    <td class="text-end py-2 text-success">{{ $v->status }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <a href="#" class="text-decoration-none mt-2 d-inline-block" style="font-size: 13px;">View All Vouchers</a>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
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
