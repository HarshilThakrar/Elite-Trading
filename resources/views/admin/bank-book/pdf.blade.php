<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bank Book</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-weight-bold { font-weight: bold; }
        .text-success { color: green; }
        .text-danger { color: red; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 5px; }
        th { background-color: #f8f9fa; }
        .header { margin-bottom: 15px; }
        .header h2 { margin: 0; padding: 0; }
        .header p { margin: 2px 0; color: #555; }
    </style>
</head>
<body>
    <div class="header text-center">
        <h2>BANK BOOK</h2>
        <p><strong>Ledger:</strong> {{ $ledgerName }}</p>
        <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-left">Date</th>
                <th class="text-left">Vch No.</th>
                <th class="text-left">Type</th>
                @if($ledgerId === 'all')
                <th class="text-left">Bank Ledger</th>
                @endif
                <th class="text-left">Particulars</th>
                <th class="text-right">Receipt (Dr)</th>
                <th class="text-right">Payment (Cr)</th>
                <th class="text-right">Balance</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            <tr class="font-weight-bold">
                <td>{{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</td>
                <td>OB</td>
                <td>Opening</td>
                @if($ledgerId === 'all')
                <td>-</td>
                @endif
                <td>Opening Balance</td>
                <td class="text-right text-success">{{ $openingBalance['type'] == 'Dr' ? number_format($openingBalance['amount'], 2) : '-' }}</td>
                <td class="text-right text-danger">{{ $openingBalance['type'] == 'Cr' ? number_format($openingBalance['amount'], 2) : '-' }}</td>
                <td class="text-right {{ $openingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                    {{ number_format($openingBalance['amount'], 2) }} {{ $openingBalance['type'] }}
                </td>
                <td class="text-center">-</td>
            </tr>

            @foreach($transactions as $t)
                <tr>
                    <td>{{ $t->voucher->date->format('d-m-Y') }}</td>
                    <td>{{ $t->voucher->voucher_number }}</td>
                    <td>{{ $t->voucher->type }}</td>
                    @if($ledgerId === 'all')
                    <td>{{ $t->ledger->name ?? '-' }}</td>
                    @endif
                    <td>
                        {{ $t->particulars }}
                        @if($t->narration || $t->voucher->narration)
                            <br><small style="color: #666;">{{ $t->narration ?: $t->voucher->narration }}</small>
                        @endif
                    </td>
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
                        {{ $t->recon_status == 'Reconciled' ? 'Y' : 'N' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="font-weight-bold" style="background-color: #f8f9fa;">
                <td colspan="{{ $ledgerId === 'all' ? 5 : 4 }}" class="text-right">Period Totals:</td>
                <td class="text-right text-success">{{ number_format($summary['receipts'], 2) }}</td>
                <td class="text-right text-danger">{{ number_format($summary['payments'], 2) }}</td>
                <td colspan="2"></td>
            </tr>
            <tr class="font-weight-bold" style="background-color: #e9ecef;">
                <td colspan="{{ $ledgerId === 'all' ? 5 : 4 }}" class="text-right">Closing Balance:</td>
                <td colspan="4" class="text-center {{ $closingBalance['type'] == 'Dr' ? 'text-success' : 'text-danger' }}">
                    {{ number_format($closingBalance['amount'], 2) }} {{ $closingBalance['type'] }}
                </td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
