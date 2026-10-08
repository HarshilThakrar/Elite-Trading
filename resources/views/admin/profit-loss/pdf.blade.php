<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profit & Loss</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-weight-bold { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 5px; vertical-align: top; }
        th { background-color: #f8f9fa; }
        .header { margin-bottom: 15px; }
        .header h2 { margin: 0; padding: 0; }
        .header p { margin: 2px 0; color: #555; }
        .bg-light { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="header text-center">
        <h2>PROFIT & LOSS ACCOUNT</h2>
        <p><strong>Financial Year:</strong> {{ $financialYear->name }}</p>
        <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}</p>
    </div>

    @php
        $net = $report['net_profit'];
    @endphp

    <table>
        <thead>
            <tr>
                <th class="text-left" style="width: 50%;">Particulars (Expenses)</th>
                <th class="text-left" style="width: 50%;">Particulars (Income)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 0; border: 0;">
                    <table style="width: 100%; margin: 0; border: none;">
                        @if($filters['view_mode'] == 'detailed')
                            @foreach($report['expense_rows'] as $row)
                            <tr>
                                <td style="border: none; border-bottom: 1px solid #eee;">
                                    <strong>{{ $row['ledger']->name }}</strong><br>
                                    <span style="font-size: 9px; color: #666;">{{ $row['ledger']->accountGroup->name ?? 'Unknown Group' }}</span>
                                </td>
                                <td class="text-right font-weight-bold" style="border: none; border-bottom: 1px solid #eee; width: 30%;">{{ number_format($row['amount'], 2) }}</td>
                            </tr>
                            @endforeach
                        @else
                            @php
                                $renderGroup = function($node, $groupId, $level, $allGroups) use (&$renderGroup) {
                                    $group = $allGroups->get($groupId);
                                    $padding = $level * 15;
                                    
                                    echo '<tr class="bg-light">';
                                    echo '<td class="font-weight-bold text-left" style="border: none; border-bottom: 1px solid #eee; padding-left: ' . (5 + $padding) . 'px;">' . strtoupper($group->name ?? 'Unknown') . '</td>';
                                    echo '<td class="text-right font-weight-bold" style="border: none; border-bottom: 1px solid #eee; width: 30%;">' . number_format($node['amount'], 2) . '</td>';
                                    echo '</tr>';

                                    foreach ($node['ledgers'] as $row) {
                                        echo '<tr>';
                                        echo '<td class="text-left" style="border: none; border-bottom: 1px solid #eee; padding-left: ' . (15 + $padding) . 'px;">' . $row['ledger']->name . '</td>';
                                        echo '<td class="text-right" style="border: none; border-bottom: 1px solid #eee; width: 30%;">' . number_format($row['amount'], 2) . '</td>';
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
                                <td class="font-weight-bold text-left" style="border: none; padding-top: 10px; color: #0d6efd;">NET PROFIT</td>
                                <td class="text-right font-weight-bold" style="border: none; padding-top: 10px; color: #0d6efd;">{{ number_format($net, 2) }}</td>
                            </tr>
                        @endif
                    </table>
                </td>

                <td style="padding: 0; border: 0; border-left: 1px solid #ddd;">
                    <table style="width: 100%; margin: 0; border: none;">
                        @if($filters['view_mode'] == 'detailed')
                            @foreach($report['income_rows'] as $row)
                            <tr>
                                <td style="border: none; border-bottom: 1px solid #eee;">
                                    <strong>{{ $row['ledger']->name }}</strong><br>
                                    <span style="font-size: 9px; color: #666;">{{ $row['ledger']->accountGroup->name ?? 'Unknown Group' }}</span>
                                </td>
                                <td class="text-right font-weight-bold" style="border: none; border-bottom: 1px solid #eee; width: 30%;">{{ number_format($row['amount'], 2) }}</td>
                            </tr>
                            @endforeach
                        @else
                            @foreach($report['grouped_income']['tree'] as $groupId => $node)
                                @php $renderGroup($node, $groupId, 0, $report['grouped_income']['allGroups']); @endphp
                            @endforeach
                        @endif

                        @if($net < 0)
                            <tr>
                                <td class="font-weight-bold text-left" style="border: none; padding-top: 10px; color: #dc3545;">NET LOSS</td>
                                <td class="text-right font-weight-bold" style="border: none; padding-top: 10px; color: #dc3545;">{{ number_format(abs($net), 2) }}</td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="font-weight-bold" style="background-color: #343a40; color: #fff;">
                <td style="padding: 0; border: 0;">
                    <table style="width: 100%; margin: 0; border: none;">
                        <tr>
                            <td class="text-left" style="border: none;">TOTAL</td>
                            <td class="text-right" style="border: none;">{{ number_format(max($report['total_expenses'], $report['total_income']), 2) }}</td>
                        </tr>
                    </table>
                </td>
                <td style="padding: 0; border: 0; border-left: 1px solid #ddd;">
                    <table style="width: 100%; margin: 0; border: none;">
                        <tr>
                            <td class="text-left" style="border: none;">TOTAL</td>
                            <td class="text-right" style="border: none;">{{ number_format(max($report['total_expenses'], $report['total_income']), 2) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
