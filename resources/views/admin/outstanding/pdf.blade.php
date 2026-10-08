<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Outstanding {{ ucfirst($type) }} Analysis</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; padding: 0; font-size: 16px; }
        .header p { margin: 5px 0 0 0; color: #666; }
        
        .summary-box { margin-bottom: 15px; width: 100%; border-collapse: collapse; }
        .summary-box td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        .summary-box .title { font-weight: bold; background: #f5f5f5; font-size: 9px; text-transform: uppercase; }
        .summary-box .val { font-size: 12px; font-weight: bold; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        table.data-table th { background-color: #f8f9fa; font-weight: bold; text-transform: uppercase; font-size: 9px; }
        table.data-table .text-right { text-align: right; }
        table.data-table .text-center { text-align: center; }
        table.data-table .party-row { background-color: #f1f1f1; font-weight: bold; }
        
        .footer { position: fixed; bottom: -10px; left: 0px; right: 0px; height: 20px; font-size: 8px; text-align: center; color: #999; }
    </style>
</head>
<body>

    <div class="header">
        <h2>OUTSTANDING {{ strtoupper($type) }} ANALYSIS</h2>
        <p>As of {{ \Carbon\Carbon::parse($asOfDate)->format('d-M-Y') }} | Financial Year: {{ $financialYear->name }}</p>
    </div>

    <table class="summary-box">
        <tr>
            <td class="title">Total Outstanding</td>
            <td class="title">Not Due</td>
            <td class="title">0-30 Days</td>
            <td class="title">31-60 Days</td>
            <td class="title">61-90 Days</td>
            <td class="title">> 90 Days</td>
        </tr>
        <tr>
            <td class="val">{{ number_format($report['summary']['total_outstanding'], 2) }}</td>
            <td class="val">{{ number_format($report['summary']['aging']['not_due'], 2) }}</td>
            <td class="val">{{ number_format($report['summary']['aging']['0_30'], 2) }}</td>
            <td class="val">{{ number_format($report['summary']['aging']['31_60'], 2) }}</td>
            <td class="val">{{ number_format($report['summary']['aging']['61_90'], 2) }}</td>
            <td class="val" style="color: red;">
                {{ number_format($report['summary']['aging']['91_180'] + $report['summary']['aging']['181_365'] + $report['summary']['aging']['above_365'], 2) }}
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>Party / Invoice Ref</th>
                <th width="10%">Date</th>
                <th width="10%">Due Date</th>
                <th class="text-right" width="12%">Amount</th>
                <th class="text-right" width="12%">Paid</th>
                <th class="text-right" width="12%">Outstanding</th>
                <th class="text-center" width="8%">Days</th>
                <th class="text-center" width="12%">Bucket</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['data'] as $party)
                <tr class="party-row">
                    <td colspan="5">{{ $party['ledger']->name }}</td>
                    <td class="text-right">{{ number_format($party['total_outstanding'], 2) }}</td>
                    <td colspan="2"></td>
                </tr>
                @foreach($party['invoices'] as $inv)
                    <tr>
                        <td style="padding-left: 20px;">
                            {{ $inv['is_opening'] ? $inv['voucher_number'] : $inv['voucher_number'] }}
                        </td>
                        <td>{{ $inv['date']->format('d-m-Y') }}</td>
                        <td>{{ $inv['due_date']->format('d-m-Y') }}</td>
                        <td class="text-right">{{ $inv['amount'] > 0 ? number_format($inv['amount'], 2) : '-' }}</td>
                        <td class="text-right">{{ $inv['paid'] > 0 ? number_format($inv['paid'], 2) : '-' }}</td>
                        <td class="text-right" style="font-weight: bold;">
                            {{ number_format($inv['outstanding'], 2) }}
                        </td>
                        <td class="text-center">{{ $inv['days'] > 0 ? (int)$inv['days'] . ' days' : 'Not Due' }}</td>
                        <td class="text-center">
                            @php
                                $bucketLabel = match($inv['bucket'] ?? 'not_due') {
                                    'not_due' => 'Not Due',
                                    '0_30' => '0 - 30 Days',
                                    '31_60' => '31 - 60 Days',
                                    '61_90' => '61 - 90 Days',
                                    '91_180' => '91 - 180 Days',
                                    '181_365' => '181 - 365 Days',
                                    'above_365' => '> 365 Days',
                                    default => str_replace('_', ' ', ucwords($inv['bucket'] ?? ''))
                                };
                            @endphp
                            {{ $bucketLabel }}
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #e9ecef; font-weight: bold;">
                <td colspan="5" class="text-right">GRAND TOTAL</td>
                <td class="text-right">{{ number_format($report['summary']['total_outstanding'], 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Generated by Demo ERP System | Print Date: {{ date('d-m-Y H:i') }}
    </div>

</body>
</html>
