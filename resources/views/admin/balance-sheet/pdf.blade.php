<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance Sheet</title>
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
        <h2>BALANCE SHEET</h2>
        <p><strong>Financial Year:</strong> {{ $financialYear->name }}</p>
        <p><strong>As On:</strong> {{ \Carbon\Carbon::parse($asOnDate)->format('d-M-Y') }}</p>
    </div>

    @php
        $pl = $report['current_profit'];
        $diff = $report['difference'];
    @endphp

    @if(!$report['is_balanced'])
        <div style="background-color: #f8d7da; color: #842029; padding: 10px; margin-bottom: 10px; border: 1px solid #f5c2c7; text-align: center; font-weight: bold;">
            BALANCE SHEET MISMATCH: Out of balance by {{ number_format(abs($diff), 2) }} {{ $diff > 0 ? 'Assets > Liabilities' : 'Liabilities > Assets' }}.
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th class="text-left" style="width: 50%;">Liabilities & Equity</th>
                <th class="text-left" style="width: 50%;">Assets</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <!-- Liabilities Side -->
                <td style="padding: 0; border: 0;">
                    <table style="width: 100%; margin: 0; border: none;">
                        @if($filters['view_mode'] == 'detailed')
                            @foreach($report['liability_rows'] as $row)
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

                            @foreach($report['grouped_liabilities']['tree'] as $groupId => $node)
                                @php $renderGroup($node, $groupId, 0, $report['grouped_liabilities']['allGroups']); @endphp
                            @endforeach
                        @endif
                        
                        <tr>
                            <td class="font-weight-bold text-left" style="border: none; padding-top: 10px; color: #0d6efd;">CURRENT YEAR {{ $pl >= 0 ? 'PROFIT' : 'LOSS' }} (P&L)</td>
                            <td class="text-right font-weight-bold" style="border: none; padding-top: 10px; color: #0d6efd;">{{ number_format($pl, 2) }}</td>
                        </tr>
                    </table>
                </td>

                <!-- Assets Side -->
                <td style="padding: 0; border: 0; border-left: 1px solid #ddd;">
                    <table style="width: 100%; margin: 0; border: none;">
                        @if($filters['view_mode'] == 'detailed')
                            @foreach($report['asset_rows'] as $row)
                            <tr>
                                <td style="border: none; border-bottom: 1px solid #eee;">
                                    <strong>{{ $row['ledger']->name }}</strong><br>
                                    <span style="font-size: 9px; color: #666;">{{ $row['ledger']->accountGroup->name ?? 'Unknown Group' }}</span>
                                </td>
                                <td class="text-right font-weight-bold" style="border: none; border-bottom: 1px solid #eee; width: 30%;">{{ number_format($row['amount'], 2) }}</td>
                            </tr>
                            @endforeach
                        @else
                            @foreach($report['grouped_assets']['tree'] as $groupId => $node)
                                @php $renderGroup($node, $groupId, 0, $report['grouped_assets']['allGroups']); @endphp
                            @endforeach
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
                            <td class="text-left" style="border: none;">TOTAL LIABILITIES</td>
                            <td class="text-right" style="border: none;">{{ number_format($report['total_liabilities'], 2) }}</td>
                        </tr>
                    </table>
                </td>
                <td style="padding: 0; border: 0; border-left: 1px solid #ddd;">
                    <table style="width: 100%; margin: 0; border: none;">
                        <tr>
                            <td class="text-left" style="border: none;">TOTAL ASSETS</td>
                            <td class="text-right" style="border: none;">{{ number_format($report['total_assets'], 2) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
