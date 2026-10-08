<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bank Book - {{ $ledgerName }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
        }
        .company-sub {
            font-size: 9px;
            color: #64748b;
            margin: 2px 0 0 0;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #2563eb;
            margin: 0;
            text-align: right;
        }
        .report-meta {
            font-size: 9px;
            color: #475569;
            margin: 2px 0 0 0;
            text-align: right;
        }
        .summary-box {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .summary-box td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: center;
            background-color: #f8fafc;
            width: 25%;
        }
        .summary-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            display: block;
            margin-bottom: 2px;
        }
        .summary-value {
            font-size: 11px;
            font-weight: bold;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-weight-bold { font-weight: bold; }
        .text-success { color: #16a34a; }
        .text-danger { color: #dc2626; }
        .text-muted { color: #64748b; }
        .small { font-size: 8px; }
        .nowrap { white-space: nowrap; }
        .signature-table {
            width: 100%;
            margin-top: 35px;
            border-collapse: collapse;
        }
        .signature-table td {
            border: none;
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }
        .signature-line {
            border-top: 1px solid #94a3b8;
            padding-top: 5px;
            font-weight: bold;
            font-size: 9px;
        }
        tr {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="company-name">DEMO ERP SYSTEM</div>
                <div class="company-sub">Accounting & Financial Records System</div>
                <div style="margin-top: 4px; font-size: 9px; color: #334155;">
                    <strong>Bank Account:</strong> {{ $ledgerName }}
                </div>
            </td>
            <td style="width: 45%;">
                <div class="report-title">BANK BOOK REGISTER</div>
                <div class="report-meta">
                    <strong>Period:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}
                </div>
                <div class="report-meta">
                    <strong>FY:</strong> {{ $financialYear->name ?? '-' }} | <strong>Generated:</strong> {{ now()->format('d-M-Y h:i A') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Summary Box -->
    <table class="summary-box">
        <tr>
            <td>
                <span class="summary-label">Opening Balance</span>
                <span class="summary-value {{ $openingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                    {{ number_format($openingBalance['amount'], 2) }} {{ $openingBalance['type'] }}
                </span>
            </td>
            <td>
                <span class="summary-label">Total Receipts (Dr)</span>
                <span class="summary-value text-success">
                    {{ number_format($summary['receipts'], 2) }}
                </span>
            </td>
            <td>
                <span class="summary-label">Total Payments (Cr)</span>
                <span class="summary-value text-danger">
                    {{ number_format($summary['payments'], 2) }}
                </span>
            </td>
            <td>
                <span class="summary-label">Closing Balance</span>
                <span class="summary-value {{ $closingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                    {{ number_format($closingBalance['amount'], 2) }} {{ $closingBalance['type'] }}
                </span>
            </td>
        </tr>
    </table>

    <!-- Transactions Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-left" style="width: 65px;">Date</th>
                <th class="text-left" style="width: 75px;">Vch No.</th>
                <th class="text-left" style="width: 55px;">Type</th>
                @if($ledgerId === 'all')
                <th class="text-left" style="width: 100px;">Bank Ledger</th>
                @endif
                <th class="text-left">Particulars / Narration</th>
                <th class="text-left" style="width: 65px;">Reference</th>
                <th class="text-right" style="width: 75px;">Receipt (Dr)</th>
                <th class="text-right" style="width: 75px;">Payment (Cr)</th>
                <th class="text-right" style="width: 85px;">Balance</th>
                <th class="text-center" style="width: 55px;">Status</th>
            </tr>
        </thead>
        <tbody>
            <!-- Opening Balance Row -->
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td class="nowrap">{{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</td>
                <td>OB</td>
                <td>Opening</td>
                @if($ledgerId === 'all')
                <td>-</td>
                @endif
                <td>Opening Balance b/f</td>
                <td>-</td>
                <td class="text-right text-success">{{ $openingBalance['type'] == 'Dr' ? number_format($openingBalance['amount'], 2) : '-' }}</td>
                <td class="text-right text-danger">{{ $openingBalance['type'] == 'Cr' ? number_format($openingBalance['amount'], 2) : '-' }}</td>
                <td class="text-right {{ $openingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                    {{ number_format($openingBalance['amount'], 2) }} {{ $openingBalance['type'] }}
                </td>
                <td class="text-center">-</td>
            </tr>

            <!-- Transactions -->
            @forelse($transactions as $t)
                <tr>
                    <td class="nowrap">{{ $t->voucher->date->format('d-m-Y') }}</td>
                    <td class="font-weight-bold">{{ $t->voucher->voucher_number }}</td>
                    <td>{{ $t->voucher->type }}</td>
                    @if($ledgerId === 'all')
                    <td>{{ $t->ledger->name ?? '-' }}</td>
                    @endif
                    <td>
                        {{ $t->particulars }}
                        @if($t->narration || $t->voucher->narration)
                            <br><span class="text-muted small"><em>{{ $t->narration ?: $t->voucher->narration }}</em></span>
                        @endif
                    </td>
                    <td>{{ $t->voucher->reference_id ?? '-' }}</td>
                    <td class="text-right text-success">
                        {{ $t->type == 'Dr' ? number_format($t->amount, 2) : '-' }}
                    </td>
                    <td class="text-right text-danger">
                        {{ $t->type == 'Cr' ? number_format($t->amount, 2) : '-' }}
                    </td>
                    <td class="text-right font-weight-bold {{ $t->running_balance_type == 'Dr' ? 'text-success' : 'text-danger' }}">
                        {{ number_format($t->running_balance, 2) }} {{ $t->running_balance_type }}
                    </td>
                    <td class="text-center">
                        {{ $t->recon_status == 'Reconciled' ? 'Reconciled' : ($t->recon_status == 'Ignored' ? 'Ignored' : 'Unrec.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $ledgerId === 'all' ? 10 : 9 }}" class="text-center text-muted" style="padding: 20px;">
                        No transactions found in this period.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="{{ $ledgerId === 'all' ? 6 : 5 }}" class="text-right">Period Totals:</td>
                <td class="text-right text-success">{{ number_format($summary['receipts'], 2) }}</td>
                <td class="text-right text-danger">{{ number_format($summary['payments'], 2) }}</td>
                <td class="text-right {{ $closingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                    {{ number_format($closingBalance['amount'], 2) }} {{ $closingBalance['type'] }}
                </td>
                <td></td>
            </tr>
            <tr style="background-color: #e2e8f0; font-weight: bold;">
                <td colspan="{{ $ledgerId === 'all' ? 6 : 5 }}" class="text-right">Closing Balance b/d:</td>
                <td colspan="4" class="text-center {{ $closingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                    {{ number_format($closingBalance['amount'], 2) }} {{ $closingBalance['type'] }}
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Signature Block -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-line">Prepared By</div>
                <div class="small text-muted">Accountant / Cashier</div>
            </td>
            <td>
                <div class="signature-line">Verified By</div>
                <div class="small text-muted">Internal Auditor</div>
            </td>
            <td>
                <div class="signature-line">Authorized Signatory</div>
                <div class="small text-muted">Finance Head / Manager</div>
            </td>
        </tr>
    </table>
</body>
</html>
