<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trial Balance - {{ $financialYear->name ?? '' }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9.5px;
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
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 9px;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-weight-bold { font-weight: bold; }
        .text-success { color: #16a34a; }
        .text-danger { color: #dc2626; }
        .text-muted { color: #64748b; }
        .bg-light { background-color: #f8fafc; }
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
                    <strong>Financial Year:</strong> {{ $financialYear->name ?? '-' }} | <strong>Mode:</strong> {{ ucfirst($filters['view_mode'] ?? 'detailed') }}
                </div>
            </td>
            <td style="width: 45%;">
                <div class="report-title">TRIAL BALANCE</div>
                <div class="report-meta">
                    <strong>Period:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}
                </div>
                <div class="report-meta">
                    <strong>Generated:</strong> {{ now()->format('d-M-Y h:i A') }}
                </div>
            </td>
        </tr>
    </table>

    @php
        $gt = $report['grand_totals'];
        $diff = $gt['difference'];
    @endphp

    @if(abs($diff) > 0.01)
        <div style="background-color: #fee2e2; color: #991b1b; padding: 6px 10px; margin-bottom: 8px; border: 1px solid #f87171; text-align: center; font-weight: bold; font-size: 9px;">
            TRIAL BALANCE IMBALANCE: The Trial Balance is not balanced. Difference: {{ number_format(abs($diff), 2) }} {{ $diff > 0 ? 'Dr' : 'Cr' }}.
        </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" class="text-left" style="width: 28%;">Particulars</th>
                <th colspan="2" class="text-center">Opening Balance</th>
                <th colspan="2" class="text-center">Transactions</th>
                <th colspan="2" class="text-center">Closing Balance</th>
            </tr>
            <tr>
                <th class="text-right" style="width: 12%;">Debit</th>
                <th class="text-right" style="width: 12%;">Credit</th>
                <th class="text-right" style="width: 12%;">Debit</th>
                <th class="text-right" style="width: 12%;">Credit</th>
                <th class="text-right" style="width: 12%;">Debit</th>
                <th class="text-right" style="width: 12%;">Credit</th>
            </tr>
        </thead>
        <tbody>
            @if(($filters['view_mode'] ?? 'detailed') == 'detailed')
                @foreach($report['rows'] as $row)
                    <tr>
                        <td class="text-left">
                            <strong>{{ $row['ledger']->name }}</strong>
                            <div style="font-size: 8px; color: #64748b;">{{ $row['ledger']->accountGroup->name ?? 'Unknown Group' }}</div>
                        </td>
                        <td class="text-right {{ $row['opening_dr'] > 0 ? 'text-success' : 'text-muted' }}">{{ $row['opening_dr'] > 0 ? number_format($row['opening_dr'], 2) : '-' }}</td>
                        <td class="text-right {{ $row['opening_cr'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $row['opening_cr'] > 0 ? number_format($row['opening_cr'], 2) : '-' }}</td>
                        <td class="text-right {{ $row['period_dr'] > 0 ? 'text-success' : 'text-muted' }}">{{ $row['period_dr'] > 0 ? number_format($row['period_dr'], 2) : '-' }}</td>
                        <td class="text-right {{ $row['period_cr'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $row['period_cr'] > 0 ? number_format($row['period_cr'], 2) : '-' }}</td>
                        <td class="text-right font-weight-bold {{ $row['closing_dr'] > 0 ? 'text-success' : 'text-muted' }}">{{ $row['closing_dr'] > 0 ? number_format($row['closing_dr'], 2) : '-' }}</td>
                        <td class="text-right font-weight-bold {{ $row['closing_cr'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $row['closing_cr'] > 0 ? number_format($row['closing_cr'], 2) : '-' }}</td>
                    </tr>
                @endforeach
            @else
                @php
                    $renderGroup = function($node, $groupId, $level) use (&$renderGroup, $report) {
                        if ($level > 8) return;
                        $group = $report['grouped']['allGroups']->get($groupId);
                        $padding = $level * 12;
                        
                        echo '<tr class="bg-light font-weight-bold">';
                        echo '<td class="text-left" style="padding-left: ' . (4 + $padding) . 'px;">' . strtoupper($group->name ?? 'Unknown Group') . '</td>';
                        echo '<td class="text-right text-success">' . ($node['opening_dr'] > 0 ? number_format($node['opening_dr'], 2) : '-') . '</td>';
                        echo '<td class="text-right text-danger">' . ($node['opening_cr'] > 0 ? number_format($node['opening_cr'], 2) : '-') . '</td>';
                        echo '<td class="text-right text-success">' . ($node['period_dr'] > 0 ? number_format($node['period_dr'], 2) : '-') . '</td>';
                        echo '<td class="text-right text-danger">' . ($node['period_cr'] > 0 ? number_format($node['period_cr'], 2) : '-') . '</td>';
                        echo '<td class="text-right text-success">' . ($node['closing_dr'] > 0 ? number_format($node['closing_dr'], 2) : '-') . '</td>';
                        echo '<td class="text-right text-danger">' . ($node['closing_cr'] > 0 ? number_format($node['closing_cr'], 2) : '-') . '</td>';
                        echo '</tr>';

                        foreach ($node['ledgers'] as $row) {
                            echo '<tr>';
                            echo '<td class="text-left" style="padding-left: ' . (12 + $padding) . 'px;">' . $row['ledger']->name . '</td>';
                            echo '<td class="text-right ' . ($row['opening_dr'] > 0 ? 'text-success' : 'text-muted') . '">' . ($row['opening_dr'] > 0 ? number_format($row['opening_dr'], 2) : '-') . '</td>';
                            echo '<td class="text-right ' . ($row['opening_cr'] > 0 ? 'text-danger' : 'text-muted') . '">' . ($row['opening_cr'] > 0 ? number_format($row['opening_cr'], 2) : '-') . '</td>';
                            echo '<td class="text-right ' . ($row['period_dr'] > 0 ? 'text-success' : 'text-muted') . '">' . ($row['period_dr'] > 0 ? number_format($row['period_dr'], 2) : '-') . '</td>';
                            echo '<td class="text-right ' . ($row['period_cr'] > 0 ? 'text-danger' : 'text-muted') . '">' . ($row['period_cr'] > 0 ? number_format($row['period_cr'], 2) : '-') . '</td>';
                            echo '<td class="text-right ' . ($row['closing_dr'] > 0 ? 'text-success font-weight-bold' : 'text-muted') . '">' . ($row['closing_dr'] > 0 ? number_format($row['closing_dr'], 2) : '-') . '</td>';
                            echo '<td class="text-right ' . ($row['closing_cr'] > 0 ? 'text-danger font-weight-bold' : 'text-muted') . '">' . ($row['closing_cr'] > 0 ? number_format($row['closing_cr'], 2) : '-') . '</td>';
                            echo '</tr>';
                        }

                        foreach ($node['children'] as $childId => $childNode) {
                            $renderGroup($childNode, $childId, $level + 1);
                        }
                    };
                @endphp

                @foreach($report['grouped']['tree'] as $groupId => $node)
                    @php $renderGroup($node, $groupId, 0); @endphp
                @endforeach
            @endif
        </tbody>
        <tfoot>
            <tr class="font-weight-bold" style="background-color: #1e293b; color: #fff;">
                <td class="text-right">GRAND TOTAL:</td>
                <td class="text-right">{{ number_format($gt['opening_dr'], 2) }}</td>
                <td class="text-right">{{ number_format($gt['opening_cr'], 2) }}</td>
                <td class="text-right">{{ number_format($gt['period_dr'], 2) }}</td>
                <td class="text-right">{{ number_format($gt['period_cr'], 2) }}</td>
                <td class="text-right">{{ number_format($gt['closing_dr'], 2) }}</td>
                <td class="text-right">{{ number_format($gt['closing_cr'], 2) }}</td>
            </tr>
            @if(abs($diff) > 0.01)
            <tr style="background-color: #dc2626; color: #fff; font-weight: bold;">
                <td class="text-right">DIFFERENCE:</td>
                <td colspan="4" class="text-center">Out of balance by {{ number_format(abs($diff), 2) }}</td>
                <td class="text-right">{{ $diff > 0 ? number_format($diff, 2) : '-' }}</td>
                <td class="text-right">{{ $diff < 0 ? number_format(abs($diff), 2) : '-' }}</td>
            </tr>
            @endif
        </tfoot>
    </table>

    <!-- Signature Block -->
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-line">Prepared By</div>
                <div class="small text-muted">Accountant</div>
            </td>
            <td>
                <div class="signature-line">Verified By</div>
                <div class="small text-muted">Internal Auditor</div>
            </td>
            <td>
                <div class="signature-line">Authorized Signatory</div>
                <div class="small text-muted">Finance Head / Director</div>
            </td>
        </tr>
    </table>
</body>
</html>
