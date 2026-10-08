<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
            color: #333333;
            margin: 0;
            padding: 0;
        }

        @if(isset($forImage) && $forImage)
            body {
                width: 800px;
                margin: 0 auto;
                background: white;
                padding: 40px;
            }

        @endif .header-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .quote-title {
            font-size: 36px;
            font-weight: bold;
            color: #0baabf;
            font-style: italic;
            text-align: right;
        }

        .divider {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .divider td.teal {
            background-color: #0baabf;
            height: 3px;
            width: 35%;
        }

        .divider td.dark {
            background-color: #2d2d2d;
            height: 3px;
            width: 65%;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .client-info h2 {
            font-size: 16px;
            color: #2d2d2d;
            text-transform: uppercase;
            margin: 0 0 5px 0;
        }

        .client-info .service {
            font-size: 11px;
            color: #777777;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .client-info p {
            font-size: 11px;
            color: #777777;
            line-height: 1.4;
            margin: 0;
            text-transform: uppercase;
        }

        .quote-meta {
            text-align: right;
            font-size: 11px;
            color: #777777;
            text-transform: uppercase;
        }

        .quote-meta .number {
            font-size: 16px;
            font-weight: bold;
            color: #777777;
            margin: 5px 0;
        }

        .thin-line {
            border-top: 1px solid #cccccc;
            margin-bottom: 20px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .data-table th,
        .data-table td {
            padding: 10px;
            font-size: 11px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .data-table th {
            background-color: #2d2d2d;
            color: #ffffff;
            text-transform: uppercase;
            font-weight: bold;
        }

        .data-table tbody tr:nth-child(odd) {
            background-color: #f2f2f2;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #ffffff;
        }

        .text-center {
            text-align: center !important;
        }

        .text-right {
            text-align: right !important;
        }

        .totals-row td {
            border-top: 2px solid #2d2d2d;
            font-weight: bold;
            padding-top: 15px;
        }

        .bottom-section {
            width: 100%;
            margin-bottom: 30px;
        }

        .notes-section,
        .terms-section {
            margin-bottom: 20px;
        }

        .notes-section h3,
        .terms-section h3 {
            font-size: 12px;
            font-weight: bold;
            color: #2d2d2d;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .notes-section p {
            font-size: 12px;
            margin: 0;
        }

        .terms-section p {
            font-size: 10px;
            color: #777777;
            margin: 0;
        }

        .contact-grid {
            width: 100%;
            margin-top: 20px;
        }

        .contact-grid td {
            width: 33.33%;
            font-size: 11px;
            vertical-align: top;
        }

        .contact-grid h4 {
            font-size: 12px;
            color: #2d2d2d;
            margin: 0 0 5px 0;
        }

        .bottom-shapes-table {
            width: 100%;
            border-collapse: collapse;
            height: 40px;
            position: fixed;
            bottom: 0;
            left: 0;
            margin: 0;
            padding: 0;
        }

        .bottom-shapes-table td {
            padding: 0;
        }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <!-- Uncomment below line if logo is available -->
                <!-- <img src="{{ public_path('elite-removebg-preview.png') }}" alt="Logo" style="height: 80px; object-fit: contain;"> -->
                <h1 style="color: #2d2d2d; margin: 0; font-size: 32px;">Demo ERP</h1>
            </td>
            <td style="width: 50%;" class="quote-title">
                QUOTE
            </td>
        </tr>
    </table>

    <table class="divider">
        <tr>
            <td class="teal"></td>
            <td class="dark"></td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td style="width: 50%; vertical-align: top;" class="client-info">
                <h2>{{ $quotation->customer->company_name ?? $quotation->customer->customer_name }}</h2>
                <div class="service">Supply Order</div>
                <p>
                    {{ $quotation->customer->address }}<br>
                    {{ $quotation->customer->city }}, {{ $quotation->customer->state }}
                    {{ $quotation->customer->pincode }}<br>
                    {{ $quotation->customer->email }}<br>
                    {{ $quotation->customer->phone }}
                </p>
            </td>
            <td style="width: 50%; vertical-align: top;" class="quote-meta">
                <div>QUOTE#</div>
                <div class="number">{{ $quotation->quotation_number }}</div>
                <div>{{ \Carbon\Carbon::parse($quotation->quotation_date)->format('F d, Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="thin-line"></div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="25%">DESCRIPTION</th>
                <th width="15%">PART CODE</th>
                <th width="8%" class="text-center">QTY</th>
                <th width="12%" class="text-center">LP</th>
                <th width="10%" class="text-center">DISCOUNT %</th>
                <th width="20%" class="text-right">TOTAL</th>
                <th width="10%" class="text-center">AVAILABILITY</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQty = 0; @endphp
            @foreach($quotation->items as $item)
                @php $totalQty += $item->quantity; @endphp
                <tr>
                    <td>{{ $item->product->item_name ?? $item->product->product_name }}</td>
                    <td>{{ $item->product->part_code }}</td>
                    <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                    <td class="text-center">Rs. {{ number_format($item->list_price, 2) }}</td>
                    <td class="text-center">{{ number_format($item->customer_discount, 2) }}%</td>
                    <td class="text-right">Rs.
                        {{ number_format($item->line_total ?? ($item->customer_rate * $item->quantity), 2) }}</td>
                    <td class="text-center">EX-STOCK</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            @if($quotation->freight_charges > 0)
                <tr>
                    <td colspan="5" class="text-right fw-bold">FREIGHT</td>
                    <td class="text-right fw-bold">Rs. {{ number_format($quotation->freight_charges, 2) }}</td>
                    <td></td>
                </tr>
            @endif
            <tr class="totals-row">
                <td colspan="2">TOTAL ({{ count($quotation->items) }} ITEMS)</td>
                <td class="text-center">{{ $totalQty }}</td>
                <td colspan="2"></td>
                <td class="text-right">Rs. {{ number_format($quotation->grand_total, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="notes-section">
        <h3>NOTES:</h3>
        <p>{!! nl2br(e($quotation->notes ?? 'Please find the enclosed quotation for the requested electrical supplies and setup. Prices are valid for 15 days from the date of this quotation.')) !!}
        </p>
    </div>

    <div class="thin-line"></div>

    <div class="terms-section">
        <h3>TERMS AND CONDITIONS</h3>
        <p>{!! nl2br(e($quotation->terms_and_conditions ?? "1. Payment is due within 14 days of the invoice date.\n2. Late payments may incur a fee of 2% per month.\n3. All prices are excluding GST unless stated otherwise.\n4. For questions about this quote, contact: hello@elitetrading.com")) !!}
        </p>
    </div>

    <table class="contact-grid">
        <tr>
            <td>
                <h4>Phone.</h4>
                <p>+91 9876543210</p>
            </td>
            <td>
                <h4>Email.</h4>
                <p>hello@demoerp.com</p>
            </td>
            <td>
                <h4>Address.</h4>
                <p>Earth Icon, Vadodara, Gujarat</p>
            </td>
        </tr>
    </table>

    <table class="bottom-shapes-table">
        <tr>
            <td style="background-color: #0baabf; width: 45%; height: 40px;"></td>
            <td style="width: 30px; height: 40px; vertical-align: top;">
                <div style="width: 0; height: 0; border-top: 40px solid #0baabf; border-right: 30px solid transparent;">
                </div>
            </td>
            <td style="width: 15px; height: 40px;"></td>
            <td style="width: 30px; height: 40px; vertical-align: bottom;">
                <div
                    style="width: 0; height: 0; border-bottom: 40px solid #2d2d2d; border-left: 30px solid transparent;">
                </div>
            </td>
            <td style="background-color: #2d2d2d; width: 45%; height: 40px;"></td>
        </tr>
    </table>

    @if(isset($forImage) && $forImage)
        <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
        <script>
            window.onload = function () {
                html2canvas(document.body, {
                    scale: 2 // For better resolution
                }).then(canvas => {
                    let link = document.createElement('a');
                    link.download = 'Quotation_{{ $quotation->quotation_number }}.png';
                    link.href = canvas.toDataURL("image/png");
                    link.click();

                    // Show a message or auto-close after a delay
                    document.body.innerHTML = '<h2 style="text-align:center; margin-top:50px;">Image Downloaded Successfully! You can close this tab.</h2>';
                    setTimeout(() => { window.close(); }, 2000);
                });
            };
        </script>
    @endif
</body>

</html>