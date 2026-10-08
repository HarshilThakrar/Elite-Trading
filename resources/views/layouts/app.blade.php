<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Demo ERP')</title>
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- Bootstrap 5 CSS (kept for compatibility with existing views) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- Custom CSS (Uxerflow Theme) -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ time() }}">
    <style>
        /* Fixes for Bootstrap + Custom CSS conflicts */
        a { text-decoration: none; }
        .app-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
            background-color: var(--bg-main);
        }
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            background-color: var(--bg-surface);
        }
        .page-content {
            padding: 24px 32px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        /* Keep sidebar menu items consistent */
        .menu-list {
            padding-left: 0;
            margin-bottom: 0;
        }
        .menu-item {
            color: var(--text-secondary);
        }
        .menu-item:hover, .menu-item.active {
            color: var(--text-primary);
        }
        /* Toastr text color override */
        #toast-container > div, 
        #toast-container > div .toast-message, 
        #toast-container > div .toast-title {
            color: #ffffff !important;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay"></div>

        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <div class="logo-icon">E.</div>
                    <div class="logo-text">
                        <span class="company-name">Demo ERP</span>
                        <span class="plan-name">ERP System</span>
                    </div>
                </div>
                <button class="collapse-btn"><i class="ph ph-caret-double-left"></i></button>
            </div>

            <div class="search-container">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" placeholder="Search">
            </div>

            <div class="menu-section">
                <h3 class="menu-title">MAIN MENU</h3>
                <ul class="menu-list">
                    <li>
                        <a href="{{ route('dashboard') }}" class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="ph ph-squares-four"></i> <span class="menu-text">Dashboard</span>
                            @if(request()->routeIs('dashboard'))<div class="active-indicator"></div>@endif
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard2') }}" class="menu-item {{ request()->routeIs('dashboard2') ? 'active' : '' }}">
                            <i class="ph ph-squares-four"></i> <span class="menu-text">Dashboard - 2</span>
                            @if(request()->routeIs('dashboard2'))<div class="active-indicator"></div>@endif
                        </a>
                    </li>
                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('view customers')))
                    <li>
                        <a href="{{ route('customers.index') }}" class="menu-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                    
                            <i class="ph ph-users"></i> <span class="menu-text">Customer Mgmt</span>
                            @if(request()->routeIs('customers.*'))<div class="active-indicator"></div>@endif
                        </a>
                    </li>
                    @endif
                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('view customers')))
                    <li>
                        <a href="{{ route('customer-overview.index') }}" class="menu-item {{ request()->routeIs('customer-overview.*') ? 'active' : '' }}">
                            <i class="ph ph-chart-line-up"></i> <span class="menu-text">Customer Overview</span>
                            @if(request()->routeIs('customer-overview.*'))<div class="active-indicator"></div>@endif
                        </a>
                    </li>
                    @endif
                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('view sales')))
                    <li>
                        <a href="{{ route('ledgers.index') }}" class="menu-item {{ request()->routeIs('ledgers.*') ? 'active' : '' }}">
                            <i class="ph ph-list-numbers"></i> <span class="menu-text">Ledgers</span>
                            @if(request()->routeIs('ledgers.*'))<div class="active-indicator"></div>@endif
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('opening-balances.index') }}" class="menu-item {{ request()->routeIs('opening-balances.*') ? 'active' : '' }}">
                            <i class="ph ph-scales"></i> <span class="menu-text">Opening Balances</span>
                            @if(request()->routeIs('opening-balances.*'))<div class="active-indicator"></div>@endif
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('sales.index') }}" class="menu-item {{ request()->routeIs('sales.*') ? 'active' : '' }}">
                            <i class="ph ph-receipt"></i> <span class="menu-text">Sales Mgmt</span>
                            @if(request()->routeIs('sales.*'))<div class="active-indicator"></div>@endif
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('sales-vouchers.index') }}" class="menu-item {{ request()->routeIs('sales-vouchers.*') ? 'active' : '' }}">
                            <i class="ph ph-file-invoice"></i> <span class="menu-text">Sales Vouchers</span>
                            @if(request()->routeIs('sales-vouchers.*'))<div class="active-indicator"></div>@endif
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('purchase-vouchers.index') }}" class="menu-item {{ request()->routeIs('purchase-vouchers.*') ? 'active' : '' }}">
                            <i class="ph ph-file-invoice"></i> <span class="menu-text">Purchase Vouchers</span>
                            @if(request()->routeIs('purchase-vouchers.*'))<div class="active-indicator"></div>@endif
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('debit-notes.index') }}" class="menu-item {{ request()->routeIs('debit-notes.*') ? 'active' : '' }}">
                            <i class="ph ph-arrow-u-down-left"></i> <span class="menu-text">Debit Notes</span>
                            @if(request()->routeIs('debit-notes.*'))<div class="active-indicator"></div>@endif
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('credit-notes.index') }}" class="menu-item {{ request()->routeIs('credit-notes.*') ? 'active' : '' }}">
                            <i class="ph ph-arrow-u-down-right"></i> <span class="menu-text">Credit Notes</span>
                            @if(request()->routeIs('credit-notes.*'))<div class="active-indicator"></div>@endif
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('bank-reconciliation.index') }}" class="menu-item {{ request()->routeIs('bank-reconciliation.*') ? 'active' : '' }}">
                            <i class="ph ph-bank"></i> <span class="menu-text">Bank Reconciliation</span>
                            @if(request()->routeIs('bank-reconciliation.*'))<div class="active-indicator"></div>@endif
                        </a>
                    </li>
                    @endif
                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('view quotations')))
                    <li>
                        <a href="{{ route('quotations.index') }}" class="menu-item {{ request()->routeIs('quotations.index') || request()->routeIs('quotations.create') || request()->routeIs('quotations.edit') || request()->routeIs('quotations.show') ? 'active' : '' }}">
                            <i class="ph ph-file-text"></i> <span class="menu-text">Quotation Mgmt</span>
                            @if(request()->routeIs('quotations.index') || request()->routeIs('quotations.create') || request()->routeIs('quotations.edit') || request()->routeIs('quotations.show'))<div class="active-indicator"></div>@endif
                        </a>
                    </li>
                    @endif
                    @if(auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Admin']))
                    <li>
                        <a href="{{ route('quotations.approvals') }}" class="menu-item {{ request()->routeIs('quotations.approvals') ? 'active' : '' }}">
                            <i class="ph ph-check-circle"></i> <span class="menu-text">Pending Approvals</span>
                            @php
                                $pendingCount = \App\Models\Quotation::where('status', 'Pending Approval')->count();
                            @endphp
                            @if($pendingCount > 0)
                                <span class="badge bg-danger ms-auto rounded-pill text-white">{{ $pendingCount }}</span>
                            @endif
                            @if(request()->routeIs('quotations.approvals'))<div class="active-indicator"></div>@endif
                        </a>
                    </li>
                    @endif

                </ul>
            </div>

            @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->hasAnyPermission(['view inventory', 'view inventory ledger', 'view smart reorder', 'view dead stock', 'view abc analysis', 'view customer consumption', 'view order frequency', 'View Profit', 'view purchases', 'view vendors'])))
            <div class="menu-section">
                <h3 class="menu-title">INVENTORY & PROCUREMENT</h3>
                <ul class="menu-list">
                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('view inventory')))
                    <li>
                        <a href="{{ route('products.index') }}" class="menu-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
                            <i class="ph ph-box-cubic"></i> <span class="menu-text">Inventory Mgmt</span>
                            @if(request()->routeIs('products.*'))<div class="active-indicator"></div>@endif
                        </a>
                    </li>

                    @endif

                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('view smart reorder')))
                    <li>
                        <a href="{{ route('reports.smartReorder') }}" class="menu-item {{ request()->routeIs('reports.smartReorder') ? 'active' : '' }}">
                            <i class="ph ph-cpu"></i> <span class="menu-text">Smart Reorder</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('view dead stock')))
                    <li>
                        <a href="{{ route('reports.deadStock') }}" class="menu-item {{ request()->routeIs('reports.deadStock') ? 'active' : '' }}">
                            <i class="ph ph-skull"></i> <span class="menu-text">Dead Stock</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('view abc analysis')))
                    <li>
                        <a href="{{ route('reports.abcAnalysis') }}" class="menu-item {{ request()->routeIs('reports.abcAnalysis') ? 'active' : '' }}">
                            <i class="ph ph-bar-chart"></i> <span class="menu-text">ABC Analysis</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('view customer consumption')))
                    <li>
                        <a href="{{ route('reports.customerConsumption') }}" class="menu-item {{ request()->routeIs('reports.customerConsumption') ? 'active' : '' }}">
                            <i class="ph ph-chart-line-up"></i> <span class="menu-text">Customer Consumption</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('view order frequency')))
                    <li>
                        <a href="{{ route('reports.orderFrequency') }}" class="menu-item {{ request()->routeIs('reports.orderFrequency') ? 'active' : '' }}">
                            <i class="ph ph-calendar-check"></i> <span class="menu-text">Order Frequency</span>
                        </a>
                    </li>
                    @endif
                    @php
                        $canViewProfit = auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('View Profit'));
                    @endphp
                    @if($canViewProfit)
                    <li>
                        <a href="{{ route('reports.customerProfitability') }}" class="menu-item {{ request()->routeIs('reports.customerProfitability') ? 'active' : '' }}">
                            <i class="ph ph-currency-inr"></i> <span class="menu-text">Customer Profitability</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reports.topCustomers') }}" class="menu-item {{ request()->routeIs('reports.topCustomers') ? 'active' : '' }}">
                            <i class="ph ph-trophy"></i> <span class="menu-text">Top Customers</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('view purchases')))
                    <li>
                        <a href="{{ route('reports.vendorAnalysis') }}" class="menu-item {{ request()->routeIs('reports.vendorAnalysis') ? 'active' : '' }}">
                            <i class="ph ph-truck"></i> <span class="menu-text">Vendor Analysis</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('view purchases')))
                    <li>
                        <a href="{{ route('purchases.index') }}" class="menu-item {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                            <i class="ph ph-shopping-cart"></i> <span class="menu-text">Purchase Mgmt</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('view vendors')))
                    <li>
                        <a href="{{ route('vendors.index') }}" class="menu-item {{ request()->routeIs('vendors.*') ? 'active' : '' }}">
                            <i class="ph ph-truck"></i> <span class="menu-text">Vendor Mgmt</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
            @endif





            @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->hasAnyPermission(['view analytics', 'view financial', 'manage users', 'view audit logs', 'view exports'])))
            <div class="menu-section">
                <h3 class="menu-title">MANAGEMENT & ADMIN</h3>
                <ul class="menu-list">
                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('view financial')))
                    <li>
                        <a href="{{ route('financial.index') }}" class="menu-item {{ request()->routeIs('financial.*') ? 'active' : '' }}">
                            <i class="ph ph-currency-inr"></i> <span class="menu-text">Financial Overview</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('manage users')))
                    <li>
                        <a href="{{ route('chart-of-accounts.index') }}" class="menu-item {{ request()->routeIs('chart-of-accounts.*') ? 'active' : '' }}">
                            <i class="ph ph-tree-structure"></i> <span class="menu-text">Chart of Accounts</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('ledgers.index') }}" class="menu-item {{ request()->routeIs('ledgers.*') ? 'active' : '' }}">
                            <i class="ph ph-book-bookmark"></i> <span class="menu-text">Ledger Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('financial-years.index') }}" class="menu-item {{ request()->routeIs('financial-years.*') ? 'active' : '' }}">
                            <i class="ph ph-calendar-blank"></i> <span class="menu-text">Financial Year Mgmt</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('accounting-settings.index') }}" class="menu-item {{ request()->routeIs('accounting-settings.*') ? 'active' : '' }}">
                            <i class="ph ph-gear"></i> <span class="menu-text">Accounting Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('journal-vouchers.index') }}" class="menu-item {{ request()->routeIs('journal-vouchers.*') ? 'active' : '' }}">
                            <i class="ph ph-book-open"></i> <span class="menu-text">Journal Vouchers</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('cash-book.index') }}" class="menu-item {{ request()->routeIs('cash-book.*') ? 'active' : '' }}">
                            <i class="ph ph-book"></i> <span class="menu-text">Cash Book</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('bank-book.index') }}" class="menu-item {{ request()->routeIs('bank-book.*') ? 'active' : '' }}">
                            <i class="ph ph-book-bookmark"></i> <span class="menu-text">Bank Book</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('trial-balance.index') }}" class="menu-item {{ request()->routeIs('trial-balance.*') ? 'active' : '' }}">
                            <i class="ph ph-scales"></i> <span class="menu-text">Trial Balance</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('profit-loss.index') }}" class="menu-item {{ request()->routeIs('profit-loss.*') ? 'active' : '' }}">
                            <i class="ph ph-chart-line-up"></i> <span class="menu-text">Profit & Loss</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('balance-sheet.index') }}" class="menu-item {{ request()->routeIs('balance-sheet.*') ? 'active' : '' }}">
                            <i class="ph ph-buildings"></i> <span class="menu-text">Balance Sheet</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('day-book.index') }}" class="menu-item {{ request()->routeIs('day-book.*') ? 'active' : '' }}">
                            <i class="ph ph-calendar-check"></i> <span class="menu-text">Day Book</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('outstanding.index') }}" class="menu-item {{ request()->routeIs('outstanding.*') ? 'active' : '' }}">
                            <i class="ph ph-magnifying-glass-plus"></i> <span class="menu-text">Outstanding Analysis</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('payment-vouchers.index') }}" class="menu-item {{ request()->routeIs('payment-vouchers.*') ? 'active' : '' }}">
                            <i class="ph ph-receipt"></i> <span class="menu-text">Payment Vouchers</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('receipt-vouchers.index') }}" class="menu-item {{ request()->routeIs('receipt-vouchers.*') ? 'active' : '' }}">
                            <i class="ph ph-download-simple"></i> <span class="menu-text">Receipt Vouchers</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('contra-vouchers.index') }}" class="menu-item {{ request()->routeIs('contra-vouchers.*') ? 'active' : '' }}">
                            <i class="ph ph-arrows-left-right"></i> <span class="menu-text">Contra Vouchers</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('account-groups.index') }}" class="menu-item {{ request()->routeIs('account-groups.*') ? 'active' : '' }}">
                            <i class="ph ph-tree-structure"></i> <span class="menu-text">Ledger Groups</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('users.index') }}" class="menu-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <i class="ph ph-user-gear"></i> <span class="menu-text">User Role Mgmt</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('view audit logs')))
                    <li>
                        <a href="{{ route('audit-logs.index') }}" class="menu-item {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                            <i class="ph ph-file-search"></i> <span class="menu-text">Audit Logs</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('view exports')))
                    <li>
                        <a href="{{ route('export-sharing.index') }}" class="menu-item {{ request()->routeIs('export-sharing.*') ? 'active' : '' }}">
                            <i class="ph ph-export"></i> <span class="menu-text">Export & Sharing</span>
                        </a>
                    </li>
                    @endif
                    <li class="nav-item">
                        <a href="#mailboxSubmenu" data-bs-toggle="collapse" class="menu-item {{ request()->routeIs('mailbox.*') ? 'active' : '' }}" aria-expanded="{{ request()->routeIs('mailbox.*') ? 'true' : 'false' }}">
                            <i class="ph ph-envelope-simple"></i> <span class="menu-text">Mailbox</span>
                            <i class="ph ph-caret-right ms-auto collapse-arrow"></i>
                        </a>
                        <ul class="collapse list-unstyled {{ request()->routeIs('mailbox.*') ? 'show' : '' }}" id="mailboxSubmenu" style="padding-left: 2.5rem; margin-top: 0.5rem;">
                            <li>
                                <a href="{{ route('mailbox.compose') }}" class="menu-item {{ request()->routeIs('mailbox.compose') ? 'active' : '' }}" style="font-size: 0.9em;">
                                    <i class="ph ph-pencil-simple"></i> Compose
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('mailbox.index', ['folder' => 'inbox']) }}" class="menu-item {{ request('folder') == 'inbox' || request()->routeIs('mailbox.show') ? 'active' : '' }}" style="font-size: 0.9em;">
                                    <i class="ph ph-tray"></i> Inbox
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('mailbox.index', ['folder' => 'sent']) }}" class="menu-item {{ request('folder') == 'sent' ? 'active' : '' }}" style="font-size: 0.9em;">
                                    <i class="ph ph-paper-plane-tilt"></i> Sent
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('mailbox.index', ['folder' => 'trash']) }}" class="menu-item {{ request('folder') == 'trash' ? 'active' : '' }}" style="font-size: 0.9em;">
                                    <i class="ph ph-trash"></i> Trash
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            @endif


            <div class="sidebar-footer">
                <ul class="menu-list">
                    <li>
                        <a href="{{ route('notifications.index') }}" class="menu-item">
                            <i class="ph ph-bell"></i> <span class="menu-text">Notifications</span>
                            @if(isset($notificationCount) && $notificationCount > 0)
                                <span class="badge">{{ $notificationCount }}</span>
                            @endif
                        </a>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" class="m-0 p-0 d-block">
                            @csrf
                            <button type="submit" class="menu-item w-100 text-start border-0 bg-transparent" style="font-family: inherit;">
                                <i class="ph ph-sign-out"></i> <span class="menu-text">Logout</span>
                            </button>
                        </form>
                    </li>
                </ul>
                
                <div class="upgrade-card mt-3">
                    <div class="upgrade-icon"><i class="ph-fill ph-user-circle"></i></div>
                    <div class="upgrade-text">{{ auth()->user()->name ?? 'Admin User' }}</div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <div class="navigation-controls">
                    <button id="mobile-menu-btn">
                        <i class="ph ph-list"></i>
                    </button>
                    <span class="fw-bold" style="color: var(--primary-color); font-size: 28px;">@yield('header_title', 'Dashboard')</span>
                </div>
                
                <div class="url-bar d-none d-md-flex">
                    <i class="ph ph-buildings"></i>
                    <span>Demo ERP System</span>
                </div>
                
                <div class="topbar-actions">
                    <button class="icon-btn position-relative" onclick="window.location.href='{{ route('notifications.index') }}'">
                        <i class="ph ph-bell"></i>
                        @if(isset($notificationCount) && $notificationCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                            <span class="visually-hidden">New alerts</span>
                        </span>
                        @endif
                    </button>
                    <div class="avatar-group ms-2">
                        <div class="avatar bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="font-size: 14px;">
                            {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                        </div>
                    </div>
                </div>
            </header>

            <div class="page-content">


                @yield('content')
            </div>
        </main>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            if($('.select2').length) {
                $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });
            }
        });
    </script>

    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "5000",
        };
        @if(session('success'))
            toastr.success("{!! session('success') !!}");
        @endif
        @if(session('error'))
            toastr.error("{!! session('error') !!}");
        @endif
        @if(session('success'))
            toastr.success("{!! session('success') !!}");
        @endif
        @if(session('error'))
            toastr.error("{!! session('error') !!}");
        @endif
        @if(session('warning'))
            toastr.warning("{!! session('warning') !!}");
        @endif
        @if($errors->any())
            @foreach($errors->all() as $error)
                toastr.error("{!! $error !!}");
            @endforeach
        @endif
    </script>

    <!-- Custom Script (Uxerflow Theme) -->
    <script src="{{ asset('js/script.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                // Restore scroll position
                const scrollPos = sessionStorage.getItem('sidebarScrollPos');
                if (scrollPos) {
                    sidebar.scrollTop = scrollPos;
                }

                // Save scroll position before leaving the page
                window.addEventListener('beforeunload', function() {
                    sessionStorage.setItem('sidebarScrollPos', sidebar.scrollTop);
                });
                
                // Sidebar Search Functionality
                const searchInput = document.querySelector('.search-container input');
                if (searchInput) {
                    searchInput.addEventListener('input', function(e) {
                        const searchTerm = e.target.value.toLowerCase();
                        const menuSections = document.querySelectorAll('.menu-section');
                        
                        menuSections.forEach(section => {
                            let hasVisibleItems = false;
                            const items = section.querySelectorAll('.menu-list > li');
                            
                            items.forEach(item => {
                                const text = item.textContent.toLowerCase();
                                if (text.includes(searchTerm)) {
                                    item.style.display = '';
                                    hasVisibleItems = true;
                                } else {
                                    item.style.display = 'none';
                                }
                            });
                            
                            // Hide entire section if no items match
                            section.style.display = hasVisibleItems ? '' : 'none';
                        });
                    });
                }
            }
        });

        // Global Tab to first input
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Tab' && !e.shiftKey) {
                if (document.activeElement === document.body || document.activeElement === document.documentElement) {
                    let firstInput = $('.page-content').find('input:not([type="hidden"]):visible, select:visible, textarea:visible, button.btn-primary').not('[readonly], [disabled]').first();
                    if (firstInput.length) {
                        e.preventDefault();
                        if (firstInput.hasClass('select2-hidden-accessible')) {
                            firstInput.select2('open');
                        } else {
                            firstInput.focus();
                        }
                    }
                }
            }
        });
    </script>
    @stack('scripts')
    @include('partials.chat')
</body>
</html>
