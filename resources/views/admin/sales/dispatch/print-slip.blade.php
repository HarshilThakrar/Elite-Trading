<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispatch Slip - {{ $dispatch->dispatch_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 14px;
        }

        .text-center {
            text-align: center;
        }

        .fw-bold {
            font-weight: bold;
        }

        .mb-2 {
            margin-bottom: 10px;
        }

        .mb-4 {
            margin-bottom: 20px;
        }

        .w-100 {
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
        }

        .header-box {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="header-box text-center">
        <h2 class="fw-bold mb-2">Demo Com.</h2>
        <p class="mb-2">123 Business Park, Metro City</p>
        <h3 class="fw-bold">DISPATCH SLIP</h3>
    </div>

    <table style="border: none; margin-top: 0; margin-bottom: 20px; width: 100%;">
        <tr>
            <td style="border: none; width: 50%; vertical-align: top;">
                <strong>To:</strong><br>
                {{ $dispatch->sale->customer->company_name }}<br>
                {{ $dispatch->sale->customer->email }}<br>
                {{ $dispatch->sale->customer->mobile }}
            </td>
            <td style="border: none; width: 50%; text-align: right; vertical-align: top;">
                <strong>Slip No:</strong> {{ $dispatch->dispatch_number }}<br>
                <strong>Date:</strong> {{ \Carbon\Carbon::parse($dispatch->dispatch_date)->format('d M Y') }}<br>
                <strong>Order Ref:</strong> {{ $dispatch->sale->invoice_number }}
            </td>
        </tr>
    </table>

    <table class="w-100">
        <thead>
            <tr>
                <th style="width: 10%; text-align: center;">#</th>
                <th style="width: 60%;">Item Description</th>
                <th style="width: 30%; text-align: center;">Dispatched Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dispatch->items as $index => $item)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->product->part_code }}</strong><br>
                        {{ $item->product->item_name }}
                    </td>
                    <td style="text-align: center;">
                        {{ $item->dispatched_qty }} {{ $item->product->unit }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 50px; display: flex; justify-content: space-between;">
        <div style="text-align: center; width: 30%;">
            <hr style="border-top: 1px solid #000;">
            Prepared By
        </div>
        <div style="text-align: center; width: 30%;">
            <hr style="border-top: 1px solid #000;">
            Receiver's Signature
        </div>
    </div>

    <div class="text-center no-print" style="margin-top: 30px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print Again</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer;">Close</button>
    </div>

</body>

</html>