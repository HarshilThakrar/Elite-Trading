<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Voucher {{ $sales_voucher->voucher_number }}</title>
    <style>
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            font-size: 13px; 
            color: #333; 
            line-height: 1.5; 
            margin: 0;
            padding: 20px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 40px; 
            border-bottom: 3px solid #4f46e5; 
            padding-bottom: 20px; 
        }
        .header h2 { 
            margin: 0 0 10px 0; 
            color: #4f46e5; 
            text-transform: uppercase; 
            letter-spacing: 3px; 
            font-size: 28px;
        }
        .header p {
            margin: 0;
            color: #666;
            font-size: 16px;
        }
        .details-container {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }
        .details-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .details-box {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            margin-right: 10px;
            border: 1px solid #e2e8f0;
        }
        .details-col:last-child .details-box {
            margin-right: 0;
            margin-left: 10px;
        }
        .details-table { 
            width: 100%; 
        }
        .details-table td { 
            padding: 6px 0; 
        }
        .details-table .label { 
            font-weight: bold; 
            color: #64748b; 
            width: 120px;
            text-transform: uppercase;
            font-size: 12px;
        }
        .details-table .value {
            color: #0f172a;
            font-weight: 500;
        }
        
        .main-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 40px; 
        }
        .main-table th, .main-table td { 
            padding: 12px 15px; 
            text-align: left; 
        }
        .main-table thead th { 
            background-color: #4f46e5; 
            color: white;
            text-transform: uppercase; 
            font-size: 13px; 
            letter-spacing: 1px;
            border: none;
        }
        .main-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }
        .main-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right { 
            text-align: right !important; 
        }
        .total-row { 
            font-weight: bold; 
            background-color: #f1f5f9; 
            font-size: 15px;
        }
        .total-row td {
            border-top: 2px solid #cbd5e1;
            padding-top: 15px;
            padding-bottom: 15px;
        }
        
        .narration-box {
            background-color: #f8fafc; 
            padding: 20px; 
            border-left: 5px solid #4f46e5;
            border-radius: 4px;
            margin-bottom: 40px;
        }
        .narration-box strong {
            color: #4f46e5;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
            display: block;
            margin-bottom: 8px;
        }
        
        .footer { 
            margin-top: 60px; 
            border-top: 1px solid #e2e8f0; 
            padding-top: 20px; 
            text-align: center; 
            color: #94a3b8; 
            font-size: 12px; 
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>Sales Voucher</h2>
        <p>Voucher No: <strong>{{ $sales_voucher->voucher_number }}</strong></p>
    </div>

    <div class="details-container">
        <div class="details-col">
            <div class="details-box">
                <table class="details-table">
                    <tr>
                        <td class="label">Date:</td>
                        <td class="value">{{ \Carbon\Carbon::parse($sales_voucher->date)->format('F d, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Status:</td>
                        <td class="value">{{ $sales_voucher->status }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="details-col">
            <div class="details-box">
                <table class="details-table">
                    <tr>
                        <td class="label">Ref Invoice:</td>
                        <td class="value">{{ $sales_voucher->reference ? $sales_voucher->reference->invoice_number : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Customer:</td>
                        <td class="value">
                            @php
                                $custName = 'N/A';
                                if ($sales_voucher->reference) {
                                    if ($sales_voucher->reference_type === 'App\Models\Invoice' && $sales_voucher->reference->sale?->customer) {
                                        $custName = $sales_voucher->reference->sale->customer->company_name ?? $sales_voucher->reference->sale->customer->customer_name;
                                    } elseif ($sales_voucher->reference_type === 'App\Models\Sale' && $sales_voucher->reference->customer) {
                                        $custName = $sales_voucher->reference->customer->company_name ?? $sales_voucher->reference->customer->customer_name;
                                    }
                                }
                            @endphp
                            {{ $custName }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th width="30%">Ledger Account</th>
                <th width="40%">Narration</th>
                <th width="15%" class="text-right">Debit (Rs.)</th>
                <th width="15%" class="text-right">Credit (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalDr = 0;
                $totalCr = 0;
            @endphp
            @foreach($sales_voucher->entries as $entry)
            <tr>
                <td style="font-weight: 600;">{{ $entry->ledger?->name ?? 'Ledger' }}</td>
                <td style="color: #64748b;">{{ $entry->narration }}</td>
                <td class="text-right" style="color: #16a34a; font-weight: 600;">
                    @if($entry->type == 'Dr')
                        @php $totalDr += $entry->amount; @endphp
                        {{ number_format($entry->amount, 2) }}
                    @endif
                </td>
                <td class="text-right" style="color: #dc2626; font-weight: 600;">
                    @if($entry->type == 'Cr')
                        @php $totalCr += $entry->amount; @endphp
                        {{ number_format($entry->amount, 2) }}
                    @endif
                </td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" class="text-right text-uppercase" style="color: #64748b; letter-spacing: 1px;">Total Amount</td>
                <td class="text-right" style="color: #16a34a; white-space: nowrap;">Rs.&nbsp;{{ number_format($totalDr, 2) }}</td>
                <td class="text-right" style="color: #dc2626; white-space: nowrap;">Rs.&nbsp;{{ number_format($totalCr, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if($sales_voucher->narration)
    <div class="narration-box">
        <strong>Voucher Narration</strong>
        {{ $sales_voucher->narration }}
    </div>
    @endif

    <div class="footer">
        <p>This is a computer generated document. No signature is required.</p>
    </div>

</body>
</html>
