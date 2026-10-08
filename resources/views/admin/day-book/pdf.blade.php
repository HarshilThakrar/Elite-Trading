<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Day Book</title>
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
        <h2>DAY BOOK</h2>
        <p><strong>Financial Year:</strong> {{ $financialYear->name }}</p>
        <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}</p>
        
        @if(!empty($filters['voucher_type']) || !empty($filters['ledger_id']))
            <p>
                @if(!empty($filters['voucher_type']))
                    <strong>Type:</strong> {{ $filters['voucher_type'] }} &nbsp;
                @endif
                @if(!empty($filters['ledger_id']))
                    <strong>Ledger ID:</strong> {{ $filters['ledger_id'] }}
                @endif
            </p>
        @endif
    </div>

    @if(!$report['is_balanced'])
        <div style="background-color: #f8d7da; color: #842029; padding: 10px; margin-bottom: 10px; border: 1px solid #f5c2c7; text-align: center; font-weight: bold;">
            ACCOUNTING IMBALANCE DETECTED: Total Debit ({{ number_format($report['total_dr'], 2) }}) does not match Total Credit ({{ number_format($report['total_cr'], 2) }}).
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th class="text-left" style="width: 12%;">Date</th>
                <th class="text-left" style="width: 40%;">Particulars</th>
                <th class="text-left" style="width: 15%;">Vch Type</th>
                <th class="text-left" style="width: 15%;">Vch No.</th>
                <th class="text-right" style="width: 9%;">Debit</th>
                <th class="text-right" style="width: 9%;">Credit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['vouchers'] as $voucher)
                <!-- Voucher Header -->
                <tr style="background-color: #e9ecef;">
                    <td class="font-weight-bold">{{ $voucher->date->format('d-M-Y') }}</td>
                    <td colspan="3" class="font-weight-bold">{{ $voucher->voucher_number }}</td>
                    <td></td>
                    <td></td>
                </tr>
                
                <!-- Journal Entries -->
                @foreach($voucher->entries as $entry)
                    <tr>
                        <td></td>
                        <td style="{{ $entry->type === 'Cr' ? 'padding-left: 20px;' : 'font-weight: bold;' }}">
                            {{ $entry->type === 'Cr' ? 'To ' : 'By ' }} {{ $entry->ledger->name }}
                        </td>
                        <td>{{ $voucher->type }}</td>
                        <td></td>
                        <td class="text-right font-weight-bold">{{ $entry->type === 'Dr' ? number_format($entry->amount, 2) : '' }}</td>
                        <td class="text-right font-weight-bold">{{ $entry->type === 'Cr' ? number_format($entry->amount, 2) : '' }}</td>
                    </tr>
                @endforeach

                <!-- Narration -->
                @if($voucher->narration)
                    <tr>
                        <td></td>
                        <td colspan="5" style="color: #6c757d; font-style: italic;">
                            Narration: {{ $voucher->narration }}
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr class="font-weight-bold" style="background-color: #343a40; color: #fff;">
                <td colspan="4" class="text-right">TOTAL</td>
                <td class="text-right">{{ number_format($report['total_dr'], 2) }}</td>
                <td class="text-right">{{ number_format($report['total_cr'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
