<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/password/reset', function () {
    return view('auth.passwords.email');
})->name('password.request')->middleware('guest');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard-2', [\App\Http\Controllers\Admin\Dashboard2Controller::class, 'index'])->name('dashboard2');
    Route::get('/notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
    
    // Reports
    Route::get('/reports/monthly-sales', [\App\Http\Controllers\Admin\ReportController::class, 'monthlySales'])->name('reports.monthlySales');
    Route::get('/reports/monthly-purchases', [\App\Http\Controllers\Admin\ReportController::class, 'monthlyPurchases'])->name('reports.monthlyPurchases');
    Route::get('/reports/sales', [\App\Http\Controllers\Admin\ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/purchases', [\App\Http\Controllers\Admin\ReportController::class, 'purchases'])->name('reports.purchases');
    Route::get('/reports/fast-moving', [\App\Http\Controllers\Admin\ReportController::class, 'fastMoving'])->name('reports.fastMoving');
    Route::get('/reports/dead-stock', [\App\Http\Controllers\Admin\ReportController::class, 'deadStock'])->name('reports.deadStock');
    Route::get('/reports/profitability', [\App\Http\Controllers\Admin\ReportController::class, 'profitability'])->name('reports.profitability');
    Route::get('/reports/smart-reorder', [\App\Http\Controllers\Admin\ReportController::class, 'smartReorder'])->name('reports.smartReorder');
    Route::get('customers/import/sample', [\App\Http\Controllers\Admin\CustomerController::class, 'downloadSample'])->name('customers.import.sample');
    Route::post('customers/import', [\App\Http\Controllers\Admin\CustomerController::class, 'import'])->name('customers.import');
    Route::post('customers/{customer}/toggle-status', [\App\Http\Controllers\Admin\CustomerController::class, 'toggleStatus'])->name('customers.toggleStatus');
    Route::post('customers/check-name', [\App\Http\Controllers\Admin\CustomerController::class, 'checkName'])->name('customers.checkName');
    Route::resource('customers', \App\Http\Controllers\Admin\CustomerController::class);
    Route::get('/customer-overview', [\App\Http\Controllers\Admin\CustomerOverviewController::class, 'index'])->name('customer-overview.index');
    
    
    Route::get('products/{product}/average-purchase-rate', [\App\Http\Controllers\Admin\ProductController::class, 'getAveragePurchaseRate'])->name('products.averagePurchaseRate');
    Route::get('products/{product}/purchase-history', [\App\Http\Controllers\Admin\ProductController::class, 'getPurchaseHistory'])->name('products.purchaseHistory');
    Route::get('vendors/import/sample', [\App\Http\Controllers\Admin\VendorController::class, 'downloadSample'])->name('vendors.import.sample');
    Route::post('vendors/import', [\App\Http\Controllers\Admin\VendorController::class, 'import'])->name('vendors.import');
    Route::resource('vendors', \App\Http\Controllers\Admin\VendorController::class);
    
    Route::resource('product-groups', \App\Http\Controllers\Admin\ProductGroupController::class);
    Route::resource('product-subgroups', \App\Http\Controllers\Admin\ProductSubgroupController::class);
    Route::get('get-product-subgroups/{groupId}', function($groupId) {
        return response()->json(\App\Models\ProductSubgroup::where('product_group_id', $groupId)->get());
    });

    Route::get('products/import/sample', [\App\Http\Controllers\Admin\ProductController::class, 'downloadSample'])->name('products.import.sample');
    Route::post('products/import', [\App\Http\Controllers\Admin\ProductController::class, 'import'])->name('products.import');
    Route::post('products/truncate', [\App\Http\Controllers\Admin\ProductController::class, 'truncate'])->name('products.truncate');
    Route::post('products/{product}/toggle-status', [\App\Http\Controllers\Admin\ProductController::class, 'toggleStatus'])->name('products.toggleStatus');
    Route::resource('products', \App\Http\Controllers\Admin\ProductController::class);
    Route::get('inventory/ledger', [\App\Http\Controllers\Admin\InventoryLedgerController::class, 'index'])->name('inventory.ledger.index');
    
    // Chart of Accounts
    Route::get('/chart-of-accounts', [App\Http\Controllers\Admin\ChartOfAccountsController::class, 'index'])->name('chart-of-accounts.index');

    // Ledgers & Chart of Accounts
    Route::resource('account-groups', \App\Http\Controllers\Admin\AccountGroupController::class);
    Route::resource('ledgers', \App\Http\Controllers\Admin\LedgerController::class);
    Route::resource('opening-balances', \App\Http\Controllers\Admin\OpeningBalanceController::class);
    
    // Cash Book
    Route::get('cash-book', [\App\Http\Controllers\Admin\CashBookController::class, 'index'])->name('cash-book.index');
    Route::get('cash-book/export', [\App\Http\Controllers\Admin\CashBookController::class, 'export'])->name('cash-book.export');
    Route::get('cash-book/pdf', [\App\Http\Controllers\Admin\CashBookController::class, 'pdf'])->name('cash-book.pdf');

    
    // Banking & Accounting
    Route::get('api/bank-details/{ifsc}', [\App\Http\Controllers\Admin\BankAccountController::class, 'fetchIfsc'])->name('bank-accounts.fetchIfsc');
    Route::get('banking-feeds', [\App\Http\Controllers\Admin\BankAccountController::class, 'feeds'])->name('bank-accounts.feeds');
    Route::get('bank-accounts/{bankAccount}/history', [\App\Http\Controllers\Admin\BankAccountController::class, 'history'])->name('bank-accounts.history');
    Route::post('bank-accounts/{bankAccount}/toggle-status', [\App\Http\Controllers\Admin\BankAccountController::class, 'toggleStatus'])->name('bank-accounts.toggleStatus');
    Route::resource('bank-accounts', \App\Http\Controllers\Admin\BankAccountController::class);
    Route::resource('contra-vouchers', \App\Http\Controllers\Admin\ContraVoucherController::class);
    
    Route::get('bank-reconciliation', [\App\Http\Controllers\Admin\BankReconciliationController::class, 'index'])->name('bank-reconciliation.index');
    Route::post('bank-reconciliation', [\App\Http\Controllers\Admin\BankReconciliationController::class, 'reconcile'])->name('bank-reconciliation.reconcile');
    
    Route::resource('expenses', \App\Http\Controllers\Admin\ExpenseController::class);
    Route::get('/banking', [\App\Http\Controllers\Admin\ExpenseController::class, 'banking'])->name('accounting.banking');
    
    Route::get('/gst-report', [\App\Http\Controllers\Admin\ComplianceController::class, 'gstReport'])->name('compliance.gstReport');
    Route::get('/gstr-1', [\App\Http\Controllers\Admin\ComplianceController::class, 'gstr1'])->name('compliance.gstr1');
    Route::get('/gstr-3b', [\App\Http\Controllers\Admin\ComplianceController::class, 'gstr3b'])->name('compliance.gstr3b');
    Route::get('/e-invoice', [\App\Http\Controllers\Admin\ComplianceController::class, 'eInvoice'])->name('compliance.eInvoice');
    Route::get('/e-way-bill', [\App\Http\Controllers\Admin\ComplianceController::class, 'eWayBill'])->name('compliance.eWayBill');
    
    // Purchases
    Route::resource('purchases', \App\Http\Controllers\Admin\PurchaseController::class)->except(['edit', 'update', 'destroy']);
    Route::post('purchases/{purchase}/status', [\App\Http\Controllers\Admin\PurchaseController::class, 'updateStatus'])->name('purchases.updateStatus');
    Route::get('purchases/{purchase}/pdf', [\App\Http\Controllers\Admin\PurchaseController::class, 'generatePdf'])->name('purchases.pdf');
    Route::post('purchases/{purchase}/reorder', [\App\Http\Controllers\Admin\PurchaseController::class, 'reorder'])->name('purchases.reorder');
    Route::get('purchases/{purchase}/grn/create', [\App\Http\Controllers\Admin\GrnController::class, 'create'])->name('purchases.grn.create');
    Route::post('purchases/{purchase}/grn', [\App\Http\Controllers\Admin\GrnController::class, 'store'])->name('purchases.grn.store');
    Route::get('grn', [\App\Http\Controllers\Admin\GrnController::class, 'index'])->name('grn.index');
    Route::get('grn/{grn}', [\App\Http\Controllers\Admin\GrnController::class, 'show'])->name('grn.show');

    // Reports
    Route::get('reports/top-customers', [\App\Http\Controllers\Admin\ReportController::class, 'topCustomers'])->name('reports.topCustomers');
    Route::get('reports/smart-reorder', [\App\Http\Controllers\Admin\ReportController::class, 'smartReorder'])->name('reports.smartReorder');
    Route::get('reports/dead-stock', [\App\Http\Controllers\Admin\ReportController::class, 'deadStock'])->name('reports.deadStock');
    Route::get('reports/abc-analysis', [\App\Http\Controllers\Admin\ReportController::class, 'abcAnalysis'])->name('reports.abcAnalysis');
    Route::get('reports/customer-consumption', [\App\Http\Controllers\Admin\ReportController::class, 'customerConsumption'])->name('reports.customerConsumption');
    Route::get('reports/order-frequency', [\App\Http\Controllers\Admin\ReportController::class, 'orderFrequency'])->name('reports.orderFrequency');
    Route::get('reports/customer-profitability', [\App\Http\Controllers\Admin\ReportController::class, 'customerProfitability'])->name('reports.customerProfitability');
    Route::get('reports/vendor-analysis', [\App\Http\Controllers\Admin\ReportController::class, 'vendorAnalysis'])->name('reports.vendorAnalysis');

    // Sales
    Route::resource('sales', \App\Http\Controllers\Admin\SaleController::class)->except(['edit', 'update']);
    Route::post('sales/{sale}/update-status', [\App\Http\Controllers\Admin\SaleController::class, 'updateStatus'])->name('sales.updateStatus');
    Route::get('sales/{sale}/pdf', [\App\Http\Controllers\Admin\SaleController::class, 'generatePdf'])->name('sales.pdf');
    Route::get('sales/{sale}/dispatch/create', [\App\Http\Controllers\Admin\DispatchController::class, 'create'])->name('sales.dispatch.create');
    Route::post('sales/{sale}/dispatch', [\App\Http\Controllers\Admin\DispatchController::class, 'store'])->name('sales.dispatch.store');
    Route::get('dispatch/{dispatch}', [\App\Http\Controllers\Admin\DispatchController::class, 'show'])->name('dispatch.show');
    Route::get('dispatch/{dispatch}/print-slip', [\App\Http\Controllers\Admin\DispatchController::class, 'printSlip'])->name('dispatch.printSlip');
    Route::get('dispatch/{dispatch}/pdf', [\App\Http\Controllers\Admin\DispatchController::class, 'generatePdf'])->name('dispatch.pdf');

    // Invoices
    Route::get('invoices/select-sale', [\App\Http\Controllers\Admin\InvoiceController::class, 'selectSale'])->name('invoices.selectSale');
    Route::get('invoices/{invoice}', [\App\Http\Controllers\Admin\InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('invoices/{invoice}/pdf', [\App\Http\Controllers\Admin\InvoiceController::class, 'generatePdf'])->name('invoices.pdf');

    // Sales Vouchers
    Route::resource('sales-vouchers', \App\Http\Controllers\Admin\SalesVoucherController::class)->only(['index', 'show']);
    Route::get('sales-vouchers/{sales_voucher}/pdf', [\App\Http\Controllers\Admin\SalesVoucherController::class, 'generatePdf'])->name('sales-vouchers.pdf');

    // Phase 5 Extended Modules
    Route::get('quotations/approvals', [\App\Http\Controllers\Admin\QuotationController::class, 'approvals'])->name('quotations.approvals');
    Route::post('quotations/{quotation}/approve', [\App\Http\Controllers\Admin\QuotationController::class, 'approve'])->name('quotations.approve');
    Route::post('quotations/{quotation}/update-customer-status', [\App\Http\Controllers\Admin\QuotationController::class, 'updateCustomerStatus'])->name('quotations.updateCustomerStatus');
    Route::post('quotations/{quotation}/convert-to-sale', [\App\Http\Controllers\Admin\QuotationController::class, 'convertToSale'])->name('quotations.convertToSale');
    Route::resource('quotations', \App\Http\Controllers\Admin\QuotationController::class);
    Route::get('quotations/{quotation}/pdf', [\App\Http\Controllers\Admin\QuotationController::class, 'generatePdf'])->name('quotations.pdf');
    Route::get('quotations/{quotation}/export/{type}', [\App\Http\Controllers\Admin\QuotationController::class, 'export'])->name('quotations.export');
    Route::post('quotations/{quotation}/status', [\App\Http\Controllers\Admin\QuotationController::class, 'changeStatus'])->name('quotations.status');
    Route::resource('purchase-plans', \App\Http\Controllers\Admin\PurchasePlanController::class);
    Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class);
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::get('financial-overview', [\App\Http\Controllers\Admin\FinancialController::class, 'index'])->name('financial.index');
    Route::get('audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
    
    // Financial Year Management
    Route::post('financial-years/{financial_year}/activate', [\App\Http\Controllers\Admin\FinancialYearController::class, 'activate'])->name('financial-years.activate');
    Route::post('financial-years/{financial_year}/close', [\App\Http\Controllers\Admin\FinancialYearController::class, 'close'])->name('financial-years.close');
    Route::post('financial-years/{financial_year}/reopen', [\App\Http\Controllers\Admin\FinancialYearController::class, 'reopen'])->name('financial-years.reopen');
    Route::resource('financial-years', \App\Http\Controllers\Admin\FinancialYearController::class);
    
    // Accounting Settings
    Route::get('accounting-settings', [\App\Http\Controllers\Admin\AccountingSettingsController::class, 'index'])->name('accounting-settings.index');
    Route::put('accounting-settings', [\App\Http\Controllers\Admin\AccountingSettingsController::class, 'update'])->name('accounting-settings.update');
    
    // Journal Vouchers
    Route::post('journal-vouchers/{journal_voucher}/cancel', [\App\Http\Controllers\Admin\JournalVoucherController::class, 'cancel'])->name('journal-vouchers.cancel');
    Route::resource('journal-vouchers', \App\Http\Controllers\Admin\JournalVoucherController::class);

    // Payment Vouchers
    Route::post('payment-vouchers/{payment_voucher}/cancel', [\App\Http\Controllers\Admin\PaymentVoucherController::class, 'cancel'])->name('payment-vouchers.cancel');
    Route::resource('payment-vouchers', \App\Http\Controllers\Admin\PaymentVoucherController::class);

    // Receipt Vouchers
    Route::post('receipt-vouchers/{receipt_voucher}/post', [\App\Http\Controllers\Admin\ReceiptVoucherController::class, 'post'])->name('receipt-vouchers.post');
    Route::post('receipt-vouchers/{receipt_voucher}/cancel', [\App\Http\Controllers\Admin\ReceiptVoucherController::class, 'cancel'])->name('receipt-vouchers.cancel');
    Route::resource('receipt-vouchers', \App\Http\Controllers\Admin\ReceiptVoucherController::class);

    // Contra Vouchers
    Route::post('contra-vouchers/{contra_voucher}/post', [\App\Http\Controllers\Admin\ContraVoucherController::class, 'post'])->name('contra-vouchers.post');
    Route::post('contra-vouchers/{contra_voucher}/cancel', [\App\Http\Controllers\Admin\ContraVoucherController::class, 'cancel'])->name('contra-vouchers.cancel');
    Route::resource('contra-vouchers', \App\Http\Controllers\Admin\ContraVoucherController::class);
    
    // Purchase Vouchers
    Route::get('purchase-vouchers/select-po', [\App\Http\Controllers\Admin\PurchaseVoucherController::class, 'selectPo'])->name('purchase-vouchers.select-po');
    Route::get('purchase-vouchers/create/{purchase}', [\App\Http\Controllers\Admin\PurchaseVoucherController::class, 'create'])->name('purchase-vouchers.create');
    Route::post('purchase-vouchers/store/{purchase}', [\App\Http\Controllers\Admin\PurchaseVoucherController::class, 'store'])->name('purchase-vouchers.store');
    Route::post('purchase-vouchers/{purchase_voucher}/cancel', [\App\Http\Controllers\Admin\PurchaseVoucherController::class, 'cancel'])->name('purchase-vouchers.cancel');
    Route::get('purchase-vouchers/{purchase_voucher}/pdf', [\App\Http\Controllers\Admin\PurchaseVoucherController::class, 'generatePdf'])->name('purchase-vouchers.pdf');
    Route::resource('purchase-vouchers', \App\Http\Controllers\Admin\PurchaseVoucherController::class)->except(['create', 'store', 'edit', 'update']);

    // Debit Notes (Purchase Returns)
    Route::get('debit-notes/select', [\App\Http\Controllers\Admin\DebitNoteController::class, 'selectVendorAndPurchaseVoucher'])->name('debit-notes.select');
    Route::get('debit-notes/create/{purchaseVoucher}', [\App\Http\Controllers\Admin\DebitNoteController::class, 'create'])->name('debit-notes.create');
    Route::post('debit-notes/store/{purchaseVoucher}', [\App\Http\Controllers\Admin\DebitNoteController::class, 'store'])->name('debit-notes.store');
    Route::post('debit-notes/{debit_note}/cancel', [\App\Http\Controllers\Admin\DebitNoteController::class, 'cancel'])->name('debit-notes.cancel');
    Route::get('debit-notes/{debit_note}/pdf', [\App\Http\Controllers\Admin\DebitNoteController::class, 'generatePdf'])->name('debit-notes.pdf');
    Route::resource('debit-notes', \App\Http\Controllers\Admin\DebitNoteController::class)->except(['create', 'store', 'edit', 'update']);

    // Credit Notes (Sales Returns)
    Route::get('credit-notes/select', [\App\Http\Controllers\Admin\CreditNoteController::class, 'selectCustomerAndSalesVoucher'])->name('credit-notes.select');
    Route::get('credit-notes/create/{salesVoucher}', [\App\Http\Controllers\Admin\CreditNoteController::class, 'create'])->name('credit-notes.create');
    Route::post('credit-notes/store/{salesVoucher}', [\App\Http\Controllers\Admin\CreditNoteController::class, 'store'])->name('credit-notes.store');
    Route::post('credit-notes/{credit_note}/cancel', [\App\Http\Controllers\Admin\CreditNoteController::class, 'cancel'])->name('credit-notes.cancel');
    Route::get('credit-notes/{credit_note}/pdf', [\App\Http\Controllers\Admin\CreditNoteController::class, 'generatePdf'])->name('credit-notes.pdf');
    Route::resource('credit-notes', \App\Http\Controllers\Admin\CreditNoteController::class)->except(['create', 'store', 'edit', 'update']);

    // Bank Reconciliation
    Route::get('bank-reconciliation', [\App\Http\Controllers\Admin\BankReconciliationController::class, 'index'])->name('bank-reconciliation.index');
    Route::get('bank-reconciliation/{ledgerId}/reconcile', [\App\Http\Controllers\Admin\BankReconciliationController::class, 'reconcile'])->name('bank-reconciliation.reconcile');
    Route::post('bank-reconciliation/{ledgerId}/auto-match', [\App\Http\Controllers\Admin\BankReconciliationController::class, 'autoMatch'])->name('bank-reconciliation.autoMatch');
    Route::post('bank-reconciliation/{ledgerId}/match', [\App\Http\Controllers\Admin\BankReconciliationController::class, 'match'])->name('bank-reconciliation.match');
    Route::post('bank-reconciliation/{ledgerId}/unmatch/{statementId}', [\App\Http\Controllers\Admin\BankReconciliationController::class, 'unmatch'])->name('bank-reconciliation.unmatch');
    Route::post('bank-reconciliation/{ledgerId}/ignore/{statementId}', [\App\Http\Controllers\Admin\BankReconciliationController::class, 'ignore'])->name('bank-reconciliation.ignore');
    
    // Bank Statement Import
    Route::match(['get', 'post'], 'bank-reconciliation/{ledgerId}/import', [\App\Http\Controllers\Admin\BankReconciliationController::class, 'import'])->name('bank-reconciliation.import');
    Route::post('bank-reconciliation/{ledgerId}/import/process', [\App\Http\Controllers\Admin\BankReconciliationController::class, 'processImport'])->name('bank-reconciliation.processImport');

    // Bank Book
    Route::get('bank-book', [\App\Http\Controllers\Admin\BankBookController::class, 'index'])->name('bank-book.index');
    Route::get('bank-book/export', [\App\Http\Controllers\Admin\BankBookController::class, 'export'])->name('bank-book.export');
    Route::get('bank-book/pdf', [\App\Http\Controllers\Admin\BankBookController::class, 'pdf'])->name('bank-book.pdf');

    // Trial Balance
    Route::get('trial-balance', [\App\Http\Controllers\Admin\TrialBalanceController::class, 'index'])->name('trial-balance.index');
    Route::get('trial-balance/export', [\App\Http\Controllers\Admin\TrialBalanceController::class, 'export'])->name('trial-balance.export');
    Route::get('trial-balance/pdf', [\App\Http\Controllers\Admin\TrialBalanceController::class, 'pdf'])->name('trial-balance.pdf');

    // Profit & Loss
    Route::get('profit-loss', [\App\Http\Controllers\Admin\ProfitLossController::class, 'index'])->name('profit-loss.index');
    Route::get('profit-loss/export', [\App\Http\Controllers\Admin\ProfitLossController::class, 'export'])->name('profit-loss.export');
    Route::get('profit-loss/pdf', [\App\Http\Controllers\Admin\ProfitLossController::class, 'pdf'])->name('profit-loss.pdf');

    // Balance Sheet
    Route::get('balance-sheet', [\App\Http\Controllers\Admin\BalanceSheetController::class, 'index'])->name('balance-sheet.index');
    Route::get('balance-sheet/export', [\App\Http\Controllers\Admin\BalanceSheetController::class, 'export'])->name('balance-sheet.export');
    Route::get('balance-sheet/pdf', [\App\Http\Controllers\Admin\BalanceSheetController::class, 'pdf'])->name('balance-sheet.pdf');

    // Day Book
    Route::get('day-book', [\App\Http\Controllers\Admin\DayBookController::class, 'index'])->name('day-book.index');
    Route::get('day-book/export', [\App\Http\Controllers\Admin\DayBookController::class, 'export'])->name('day-book.export');
    Route::get('day-book/pdf', [\App\Http\Controllers\Admin\DayBookController::class, 'pdf'])->name('day-book.pdf');

    // Outstanding Analysis
    Route::get('outstanding', [\App\Http\Controllers\Admin\OutstandingController::class, 'index'])->name('outstanding.index');
    Route::get('outstanding/export', [\App\Http\Controllers\Admin\OutstandingController::class, 'export'])->name('outstanding.export');
    Route::get('outstanding/pdf', [\App\Http\Controllers\Admin\OutstandingController::class, 'pdf'])->name('outstanding.pdf');

    Route::get('export-sharing', [\App\Http\Controllers\Admin\ExportController::class, 'index'])->name('export-sharing.index');
    Route::get('export-sharing/sales', [\App\Http\Controllers\Admin\ExportController::class, 'exportSales'])->name('export.sales');
    Route::get('export-sharing/customers', [\App\Http\Controllers\Admin\ExportController::class, 'exportCustomers'])->name('export.customers');
    Route::get('export-sharing/products', [\App\Http\Controllers\Admin\ExportController::class, 'exportProducts'])->name('export.products');
    Route::get('export-sharing/purchases', [\App\Http\Controllers\Admin\ExportController::class, 'exportPurchases'])->name('export.purchases');

    Route::resource('amc', \App\Http\Controllers\Admin\AmcController::class);
    Route::resource('support-tickets', \App\Http\Controllers\Admin\SupportTicketController::class);

    // Mailbox routes
    Route::get('/mailbox', [\App\Http\Controllers\Admin\MailboxController::class, 'index'])->name('mailbox.index');
    Route::get('/mailbox/compose', [\App\Http\Controllers\Admin\MailboxController::class, 'compose'])->name('mailbox.compose');
    Route::post('/mailbox/send', [\App\Http\Controllers\Admin\MailboxController::class, 'send'])->name('mailbox.send');
    Route::get('/mailbox/{id}', [\App\Http\Controllers\Admin\MailboxController::class, 'show'])->name('mailbox.show');
    Route::delete('/mailbox/{id}', [\App\Http\Controllers\Admin\MailboxController::class, 'destroy'])->name('mailbox.destroy');

    // Chat routes
    Route::get('/chat/messages', [\App\Http\Controllers\ChatController::class, 'index'])->name('chat.messages');
    Route::post('/chat/send', [\App\Http\Controllers\ChatController::class, 'store'])->name('chat.send');
    // Returns
    Route::resource('sales-returns', \App\Http\Controllers\Admin\SalesReturnController::class)->only(['index', 'create', 'store']);
    Route::resource('purchase-returns', \App\Http\Controllers\Admin\PurchaseReturnController::class)->only(['index', 'create', 'store']);
});
