<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trial Balance</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-weight-bold { font-weight: bold; }
        .text-success { color: #198754; }
        .text-danger { color: #dc3545; }
        .text-muted { color: #6c757d; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #dee2e6; padding: 4px; }
        th { background-color: #f8f9fa; }
        .header { margin-bottom: 15px; }
        .header h2 { margin: 0; padding: 0; }
        .header p { margin: 2px 0; color: #555; }
        .bg-light { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="header text-center">
        <h2>TRIAL BALANCE</h2>
        <p><strong>Financial Year:</strong> {{ $financialYear->name }}</p>
        <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}</p>
    </div>

    @php
        $gt = $report['grand_totals'];
        $diff = $gt['difference'];
    @endphp

    @if(abs($diff) > 0.01)
        <div style="background-color: #f8d7da; color: #842029; padding: 10px; margin-bottom: 10px; border: 1px solid #f5c2c7; text-align: center; font-weight: bold;">
            TRIAL BALANCE IMBALANCE: The Trial Balance is not balanced. There is a difference of {{ number_format(abs($diff), 2) }} {{ $diff > 0 ? 'Dr' : 'Cr' }}.
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th rowspan="2" class="text-left">Particulars</th>
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
            @if($filters['view_mode'] == 'detailed')
                @foreach($report['rows'] as $row)
                    <tr>
                        <td class="text-left">
                            <strong>{{ $row['ledger']->name }}</strong><br>
                            <span style="font-size: 8px; color: #666;">{{ $row['ledger']->accountGroup->name ?? 'Unknown Group' }}</span>
                        </td>
                        <td class="text-right">{{ $row['opening_dr'] > 0 ? number_format($row['opening_dr'], 2) : '-' }}</td>
                        <td class="text-right">{{ $row['opening_cr'] > 0 ? number_format($row['opening_cr'], 2) : '-' }}</td>
                        <td class="text-right">{{ $row['period_dr'] > 0 ? number_format($row['period_dr'], 2) : '-' }}</td>
                        <td class="text-right">{{ $row['period_cr'] > 0 ? number_format($row['period_cr'], 2) : '-' }}</td>
                        <td class="text-right font-weight-bold">{{ $row['closing_dr'] > 0 ? number_format($row['closing_dr'], 2) : '-' }}</td>
                        <td class="text-right font-weight-bold">{{ $row['closing_cr'] > 0 ? number_format($row['closing_cr'], 2) : '-' }}</td>
                    </tr>
                @endforeach
            @else
                @php
                    $renderGroup = function($node, $groupId, $level) use (&$renderGroup, $report) {
                        $group = $report['grouped']['allGroups']->get($groupId);
                        $padding = $level * 15;
                        
                        echo '<tr class="bg-light">';
                        echo '<td class="font-weight-bold text-left" style="padding-left: ' . (4 + $padding) . 'px;">' . strtoupper($group->name ?? 'Unknown') . '</td>';
                        echo '<td class="text-right font-weight-bold">' . ($node['opening_dr'] > 0 ? number_format($node['opening_dr'], 2) : '-') . '</td>';
                        echo '<td class="text-right font-weight-bold">' . ($node['opening_cr'] > 0 ? number_format($node['opening_cr'], 2) : '-') . '</td>';
                        echo '<td class="text-right font-weight-bold">' . ($node['period_dr'] > 0 ? number_format($node['period_dr'], 2) : '-') . '</td>';
                        echo '<td class="text-right font-weight-bold">' . ($node['period_cr'] > 0 ? number_format($node['period_cr'], 2) : '-') . '</td>';
                        echo '<td class="text-right font-weight-bold">' . ($node['closing_dr'] > 0 ? number_format($node['closing_dr'], 2) : '-') . '</td>';
                        echo '<td class="text-right font-weight-bold">' . ($node['closing_cr'] > 0 ? number_format($node['closing_cr'], 2) : '-') . '</td>';
                        echo '</tr>';

                        foreach ($node['ledgers'] as $row) {
                            echo '<tr>';
                            echo '<td class="text-left" style="padding-left: ' . (15 + $padding) . 'px;">' . $row['ledger']->name . '</td>';
                            echo '<td class="text-right">' . ($row['opening_dr'] > 0 ? number_format($row['opening_dr'], 2) : '-') . '</td>';
                            echo '<td class="text-right">' . ($row['opening_cr'] > 0 ? number_format($row['opening_cr'], 2) : '-') . '</td>';
                            echo '<td class="text-right">' . ($row['period_dr'] > 0 ? number_format($row['period_dr'], 2) : '-') . '</td>';
                            echo '<td class="text-right">' . ($row['period_cr'] > 0 ? number_format($row['period_cr'], 2) : '-') . '</td>';
                            echo '<td class="text-right">' . ($row['closing_dr'] > 0 ? number_format($row['closing_dr'], 2) : '-') . '</td>';
                            echo '<td class="text-right">' . ($row['closing_cr'] > 0 ? number_format($row['closing_cr'], 2) : '-') . '</td>';
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
            <tr class="font-weight-bold" style="background-color: #343a40; color: #fff;">
                <td class="text-right">GRAND TOTAL:</td>
                <td class="text-right">{{ number_format($gt['opening_dr'], 2) }}</td>
                <td class="text-right">{{ number_format($gt['opening_cr'], 2) }}</td>
                <td class="text-right">{{ number_format($gt['period_dr'], 2) }}</td>
                <td class="text-right">{{ number_format($gt['period_cr'], 2) }}</td>
                <td class="text-right">{{ number_format($gt['closing_dr'], 2) }}</td>
                <td class="text-right">{{ number_format($gt['closing_cr'], 2) }}</td>
            </tr>
            @if(abs($diff) > 0.01)
            <tr style="background-color: #dc3545; color: #fff; font-weight: bold;">
                <td class="text-right">DIFFERENCE:</td>
                <td colspan="4" class="text-center">Out of balance by {{ number_format(abs($diff), 2) }}</td>
                <td class="text-right">{{ $diff > 0 ? number_format($diff, 2) : '-' }}</td>
                <td class="text-right">{{ $diff < 0 ? number_format(abs($diff), 2) : '-' }}</td>
            </tr>
            @endif
        </tfoot>
    </table>
</body>
</html>
