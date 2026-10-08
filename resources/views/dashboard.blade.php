@extends('layouts.app')

@section('title', 'Dashboard - Demo ERP')
@section('header_title', 'Business Overview')

@section('content')
    <div class="row g-4 mb-4">
        <!-- Outstanding Payables -->
        <div class="col-md-3">
            <a href="{{ route('outstanding.index', ['type' => 'payables']) }}" class="text-decoration-none">
                <div class="card card-custom h-100 border-0 dashboard-card-payables shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-uppercase fw-bold text-danger mb-0 opacity-75">Total Payable</h6>
                            <div class="icon-circle bg-danger bg-opacity-10 text-danger">
                                <i class="ph-fill ph-arrow-circle-down fs-4"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">₹{{ number_format($totalPayable, 2) }}</h3>
                        <div class="mt-2 small text-muted">Total amount in purchases</div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Outstanding Receivables -->
        <div class="col-md-3">
            <a href="{{ route('outstanding.index', ['type' => 'receivables']) }}" class="text-decoration-none">
                <div class="card card-custom h-100 border-0 dashboard-card-receivables shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-uppercase fw-bold text-success mb-0 opacity-75">Total Receivable</h6>
                            <div class="icon-circle bg-success bg-opacity-10 text-success">
                                <i class="ph-fill ph-arrow-circle-up fs-4"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">₹{{ number_format($totalReceivable, 2) }}</h3>
                        <div class="mt-2 small text-muted">Total amount in sales</div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Total Sales (Current Month) -->
        <div class="col-md-3">
            <a href="{{ route('reports.monthlySales') }}" class="text-decoration-none">
                <div class="card card-custom h-100 border-0 dashboard-card-sales shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-uppercase fw-bold text-primary mb-0 opacity-75">Monthly Sales</h6>
                            <div class="icon-circle bg-primary bg-opacity-10 text-primary">
                                <i class="ph-fill ph-trend-up fs-4"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">₹{{ number_format($totalSalesWithGST, 2) }}</h3>
                        <div class="mt-2 small text-muted">Sales for current month
                            <br>(₹{{ number_format($totalSalesWithoutGST, 2) }} without GST)</div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Total Purchases (Current Month) -->
        <div class="col-md-3">
            <a href="{{ route('reports.monthlyPurchases') }}" class="text-decoration-none">
                <div class="card card-custom h-100 border-0 dashboard-card-purchases shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-uppercase fw-bold text-info mb-0 opacity-75">Monthly Purchase</h6>
                            <div class="icon-circle bg-info bg-opacity-10 text-info">
                                <i class="ph-fill ph-shopping-cart fs-4"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">₹{{ number_format($totalPurchases, 2) }}</h3>
                        <div class="mt-2 small text-muted">Purchases for current month</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Pending Sales Orders -->
        <div class="col-md-6">
            <div class="card card-custom h-100 border-0 dashboard-card-pending-sales shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-uppercase fw-bold text-warning mb-0 opacity-75">Pending Sales Orders</h6>
                        <div class="icon-circle bg-warning bg-opacity-10 text-warning" style="color: #d97706 !important;">
                            <i class="ph-fill ph-clock-counter-clockwise fs-4"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-0">{{ $pendingSalesOrders }}</h3>
                    <div class="mt-2 small text-muted">To be invoiced / dispatched</div>
                </div>
            </div>
        </div>

        <!-- Total Stock -->
        <div class="col-md-6">
            <a href="{{ route('products.index') }}" class="text-decoration-none">
                <div class="card card-custom h-100 border-0 dashboard-card-stock shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-uppercase fw-bold text-secondary mb-0 opacity-75">Total Stock</h6>
                            <div class="icon-circle bg-secondary bg-opacity-10 text-secondary">
                                <i class="ph-fill ph-package fs-4"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">{{ number_format($totalStock) }}</h3>
                        <div class="mt-2 small text-muted">Total available inventory</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Chart: Monthly Sales (Current Year) -->
        <div class="col-lg-6">
            <div class="card card-custom border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold text-dark mb-0">Monthly Sales (Without GST - {{ date('Y') }})</h6>
                </div>
                <div class="card-body">
                    <div id="yearlySalesChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <!-- Chart: Sales vs Purchases -->
        <div class="col-lg-6">
            <div class="card card-custom border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold text-dark mb-0">Sales vs Purchases (Without GST - Last 6 Months)</h6>
                </div>
                <div class="card-body">
                    <div id="salesPurchaseChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <!-- Recent Purchase Orders -->
        <div class="col-lg-6">
            <div class="card card-custom border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold text-dark mb-0">Recent Purchase Orders</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>PO Number</th>
                                    <th>Vendor</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPurchaseOrders ?? [] as $po)
                                    @if(!$po || !is_object($po)) @continue @endif
                                    <tr>
                                        <td>{{ $po->po_number }}</td>
                                        <td>{{ optional($po->vendor)->company_name ?? 'N/A' }}</td>
                                        <td>{{ $po->po_date ? \Carbon\Carbon::parse($po->po_date)->format('d M, Y') : 'N/A' }}
                                        </td>
                                        <td>₹{{ number_format((float) $po->total_amount, 2) }}</td>
                                        <td>
                                            @php
                                                $textColor = 'text-secondary';
                                                if (in_array(strtolower($po->status ?? ''), ['completed', 'received', 'delivered'])) {
                                                    $textColor = 'text-success';
                                                } elseif (in_array(strtolower($po->status), ['approved'])) {
                                                    $textColor = 'text-primary';
                                                } elseif (in_array(strtolower($po->status), ['pending', 'ordered', 'draft'])) {
                                                    $textColor = 'text-warning';
                                                } elseif (in_array(strtolower($po->status), ['cancelled', 'rejected'])) {
                                                    $textColor = 'text-danger';
                                                }
                                            @endphp
                                            <span class="fw-bold {{ $textColor }}">
                                                {{ $po->status ?: 'Unknown' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-3 text-muted">No recent purchase orders found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Sales Orders -->
        <div class="col-lg-6">
            <div class="card card-custom border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">Recent Sales Orders</h6>
                    <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary fw-semibold shadow-sm">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Items Ordered</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSalesOrders ?? [] as $order)
                                    @if(!$order || !is_object($order)) @continue @endif
                                    <tr>
                                        <td>
                                            <span
                                                class="fw-semibold text-dark">{{ optional($order->customer)->company_name ?? 'N/A' }}</span><br>
                                            <small
                                                class="text-muted">{{ $order->sale_date ? \Carbon\Carbon::parse($order->sale_date)->format('d M, Y') : '' }}</small>
                                        </td>
                                        <td>
                                            <ul class="list-unstyled mb-0">
                                                @foreach($order->items as $item)
                                                    <li class="mb-1">
                                                        <span class="text-muted">•</span>
                                                        {{ optional($item->product)->item_name ?? 'Unknown Item' }}
                                                        <span
                                                            class="badge bg-light text-dark border ms-1">{{ (float) $item->quantity }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </td>
                                        <td class="fw-medium">₹{{ number_format((float) $order->total_amount, 2) }}</td>
                                        <td>
                                            @php
                                                $statusClass = 'bg-secondary text-white';
                                                if (in_array(strtolower($order->status ?? ''), ['approved', 'completed', 'delivered'])) {
                                                    $statusClass = 'bg-success text-white';
                                                } elseif (in_array(strtolower($order->status ?? ''), ['pending', 'draft'])) {
                                                    $statusClass = 'bg-warning text-dark';
                                                } elseif (in_array(strtolower($order->status ?? ''), ['cancelled', 'rejected'])) {
                                                    $statusClass = 'bg-danger text-white';
                                                }
                                            @endphp
                                            <span class="badge {{ $statusClass }}">
                                                {{ $order->status ?: 'Unknown' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No recent sales orders found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <!-- Recent Invoices -->
        <div class="col-12">
            <div class="card card-custom border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">Recent Invoices</h6>
                    <a href="{{ route('invoices.selectSale') }}" class="btn btn-sm btn-primary fw-semibold shadow-sm">
                        <i class="ph-fill ph-file-text me-1"></i> Create Invoice
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice No</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentInvoices ?? [] as $invoice)
                                    @if(!$invoice || !is_object($invoice)) @continue @endif
                                    <tr>
                                        <td>{{ $invoice->invoice_number }}</td>
                                        <td>{{ optional(optional($invoice->sale)->customer)->company_name ?? 'N/A' }}</td>
                                        <td>{{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d M, Y') : 'N/A' }}
                                        </td>
                                        <td>₹{{ number_format((float) $invoice->total_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted">No recent invoices found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Dashboard Premium Styling */
        .icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-custom {
            border-radius: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        }

        /* Subtle border tops for visual hierarchy */
        .dashboard-card-payables {
            border-top: 4px solid #ef4444 !important;
        }

        .dashboard-card-receivables {
            border-top: 4px solid #10b981 !important;
        }

        .dashboard-card-sales {
            border-top: 4px solid #3b82f6 !important;
        }

        .dashboard-card-purchases {
            border-top: 4px solid #06b6d4 !important;
        }

        .dashboard-card-pending-sales {
            border-top: 4px solid #f59e0b !important;
        }

        .dashboard-card-stock {
            border-top: 4px solid #6b7280 !important;
        }

        .dashboard-card-pending-purchase {
            border-top: 4px solid #8b5cf6 !important;
        }
    </style>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                // Sales vs Purchases Chart
                var optionsArea = {
                    series: [{
                        name: 'Sales (Without GST)',
                        data: @json($salesData)
                    }, {
                        name: 'Purchases (Without GST)',
                        data: @json($purchasesData)
                    }],
                    chart: {
                        type: 'area',
                        height: 300,
                        toolbar: {
                            show: false
                        },
                        fontFamily: 'Inter, sans-serif'
                    },
                    colors: ['#3b82f6', '#ef4444'],
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 2
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.3,
                            opacityTo: 0.05,
                            stops: [0, 90, 100]
                        }
                    },
                    xaxis: {
                        categories: @json($chartLabels),
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        }
                    },
                    yaxis: {
                        labels: {
                            formatter: function (value) {
                                if (value >= 100000) {
                                    return "₹" + (value / 100000).toFixed(1) + "L";
                                }
                                return "₹" + (value / 1000).toFixed(0) + "k";
                            }
                        }
                    },
                    grid: {
                        borderColor: '#f1f1f1',
                        strokeDashArray: 4,
                        yaxis: {
                            lines: {
                                show: true
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return "₹" + val.toLocaleString('en-IN')
                            }
                        }
                    }
                };

                var chartArea = new ApexCharts(document.querySelector("#salesPurchaseChart"), optionsArea);
                chartArea.render();

                // Yearly Sales Pie Chart
                var salesData = @json($yearlySalesData).map(function (val) { return parseFloat(val) || 0; });
                var salesLabels = @json($yearlyLabels);

                // Check if all values are 0
                var hasData = salesData.some(function (val) { return val > 0; });
                var chartSeries = hasData ? salesData : [1];
                var chartLabels = hasData ? salesLabels : ['No Data'];

                var optionsBar = {
                    series: chartSeries,
                    labels: chartLabels,
                    chart: {
                        type: 'pie',
                        height: 300,
                        fontFamily: 'Inter, sans-serif'
                    },
                    dataLabels: {
                        enabled: hasData,
                        formatter: function (val) {
                            return val.toFixed(1) + "%";
                        }
                    },
                    tooltip: {
                        enabled: hasData,
                        y: {
                            formatter: function (val) {
                                return "₹" + val.toLocaleString('en-IN');
                            }
                        }
                    },
                    legend: {
                        show: hasData,
                        position: 'right',
                        offsetY: 40
                    },
                    plotOptions: {
                        pie: {
                            expandOnClick: hasData
                        }
                    }
                };

                var chartBar = new ApexCharts(document.querySelector("#yearlySalesChart"), optionsBar);
                chartBar.render();
            });
        </script>
    @endpush
@endsection