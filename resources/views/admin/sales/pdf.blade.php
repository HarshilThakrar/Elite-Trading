<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Tax Invoice {{ $sale->invoice_number }}</title>
    <style>
        @page {
            margin: 25px 25px 40px 25px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1f2937;
            font-size: 10px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: #6b7280;
        }

        .primary {
            color: #4f46e5;
        }

        /* Top Accent */
        .top-accent {
            height: 8px;
            background-color: #4f46e5;
            margin: -25px -25px 15px -25px;
        }

        /* Header */
        .brand-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #1f2937;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .meta-grid {
            font-size: 10px;
        }

        .meta-grid td {
            padding: 4px 0;
        }

        .meta-label {
            color: #6b7280;
            font-weight: bold;
            padding-right: 15px;
        }

        .meta-value {
            font-weight: bold;
            color: #1f2937;
        }

        /* Billing Section */
        .billing-section {
            background-color: #f9fafb;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .billing-section td {
            padding: 10px;
            vertical-align: top;
            width: 50%;
        }

        .billing-title {
            font-size: 9px;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .bold-name {
            font-weight: bold;
            font-size: 11px;
            color: #4f46e5;
            margin-bottom: 4px;
        }

        /* Additional Details */
        .details-wrapper {
            margin-bottom: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .details-wrapper td {
            padding: 6px 10px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
            width: 25%;
        }

        .detail-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: bold;
            display: block;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 10px;
            color: #1f2937;
            font-weight: bold;
        }

        /* Table */
        .table-wrapper {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .items-table th {
            background-color: #f9fafb;
            font-size: 9px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            padding: 6px 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table td {
            padding: 6px 10px;
            font-size: 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .items-table tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        .item-title {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .item-desc {
            font-size: 8px;
            color: #6b7280;
        }

        /* Summary */
        .summary-container {
            width: 100%;
            margin-bottom: 15px;
        }

        .summary-table {
            width: 250px;
            float: right;
        }

        .summary-row td {
            padding: 4px 0;
            border-bottom: 1px solid #e5e7eb;
            color: #6b7280;
        }

        .summary-row .val {
            text-align: right;
            color: #1f2937;
            font-weight: bold;
        }

        .summary-row.total td {
            font-size: 14px;
            font-weight: bold;
            color: #4f46e5;
            border-bottom: none;
            padding-top: 15px;
        }

        /* Footer */
        .footer {
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
            clear: both;
        }

        .footer td {
            vertical-align: top;
        }

        .footer h4 {
            font-size: 9px;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: bold;
            margin-bottom: 8px;
            margin-top: 0;
        }

        .footer p {
            font-size: 9px;
            color: #6b7280;
            line-height: 1.6;
            margin: 0;
        }

        .footer .payment-info p {
            color: #1f2937;
        }

        .footer .payment-info strong {
            color: #6b7280;
            margin-right: 8px;
        }

        .thank-you {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            color: #1f2937;
        }

        .amount-words {
            font-size: 9px;
            margin-top: 15px;
            font-style: italic;
            color: #6b7280;
        }
    </style>
</head>

<body>

    <div class="thank-you">
        Thank you for your business!
    </div>

    <?php
$cgst = $sale->total_amount * 0.09;
$sgst = $sale->total_amount * 0.09;
$total_with_tax = $sale->total_amount + $cgst + $sgst;

function getIndianCurrency(float $number)
{
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        0 => '',
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
        4 => 'Four',
        5 => 'Five',
        6 => 'Six',
        7 => 'Seven',
        8 => 'Eight',
        9 => 'Nine',
        10 => 'Ten',
        11 => 'Eleven',
        12 => 'Twelve',
        13 => 'Thirteen',
        14 => 'Fourteen',
        15 => 'Fifteen',
        16 => 'Sixteen',
        17 => 'Seventeen',
        18 => 'Eighteen',
        19 => 'Nineteen',
        20 => 'Twenty',
        30 => 'Thirty',
        40 => 'Forty',
        50 => 'Fifty',
        60 => 'Sixty',
        70 => 'Seventy',
        80 => 'Eighty',
        90 => 'Ninety'
    );
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    while ($i < $digits_length) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += $divider == 10 ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str[] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
        } else
            $str[] = null;
    }
    $Rupees = implode('', array_reverse($str));
    $paise = ($decimal > 0) ? " and " . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
    return ($Rupees ? $Rupees . 'Rupees ' : '') . $paise . ' Only';
}
?>

    <div style="margin: -25px -25px 15px -25px; height: 8px;">
        <table style="width: 100%; height: 8px; border-collapse: collapse;">
            <tr>
                <td style="background-color: #4f46e5; width: 10%; height: 8px;"></td>
                <td style="background-color: #6046da; width: 10%; height: 8px;"></td>
                <td style="background-color: #7246d0; width: 10%; height: 8px;"></td>
                <td style="background-color: #8347c5; width: 10%; height: 8px;"></td>
                <td style="background-color: #9547bb; width: 10%; height: 8px;"></td>
                <td style="background-color: #a647b0; width: 10%; height: 8px;"></td>
                <td style="background-color: #b847a6; width: 10%; height: 8px;"></td>
                <td style="background-color: #c9489b; width: 10%; height: 8px;"></td>
                <td style="background-color: #db4891; width: 10%; height: 8px;"></td>
                <td style="background-color: #ec4899; width: 10%; height: 8px;"></td>
            </tr>
        </table>
    </div>

    <!-- Header -->
    <table style="margin-bottom: 15px;">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('elite.png'))) }}"
                    alt="Demo ERP Logo" style="height: 50px; object-fit: contain; margin-bottom: 10px;">
                <div style="font-size: 9px; color: #6b7280; line-height: 1.5;">
                    Ground Floor, GF-25, Earth Icon, Nr. Khodiyar Nagar<br>
                    Cross Roads, New VIP Road, Vadodara - 390021<br>
                    GSTIN: 24BLZPM7890R1ZL | +91 9876543210
                </div>
            </td>
            <td style="width: 40%; vertical-align: top; text-align: right;">
                <div class="invoice-title">Invoice</div>
                <div style="text-align: right; font-size: 10px; line-height: 1.8;">
                    @if(isset($is_einvoice) && $is_einvoice)
                        <div style="padding-bottom: 10px;">
                            <img src="data:image/svg+xml;base64,{{ base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::size(50)->generate('Invoice: ' . $sale->invoice_number . ' | Date: ' . $sale->sale_date . ' | Amount: ' . $sale->total_amount)) }}"
                                alt="QR Code"><br>
                            <span style="font-size: 7px; font-weight: bold;">e-Invoice</span>
                        </div>
                    @endif
                    <div><span class="meta-label" style="padding-right: 5px;">Invoice No:</span> <span class="meta-value">{{ $sale->invoice_number }}</span></div>
                    <div><span class="meta-label" style="padding-right: 5px;">Date:</span> <span class="meta-value">{{ \Carbon\Carbon::parse($sale->sale_date)->format('M d, Y') }}</span></div>
                    <div><span class="meta-label" style="padding-right: 5px;">Due Date:</span> <span class="meta-value">{{ \Carbon\Carbon::parse($sale->sale_date)->addDays(30)->format('M d, Y') }}</span></div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Billing Info -->
    <table class="billing-section" style="width: 100%;">
        <tr>
            <td style="border-right: 1px solid #e5e7eb;">
                <div class="billing-title">Billed To</div>
                <div class="bold-name">{{ $sale->customer->company_name ?? $sale->customer->customer_name }}</div>
                <div style="font-size: 10px; line-height: 1.6;">
                    {{ $sale->customer->address }}<br>
                    {{ $sale->customer->city }}, {{ $sale->customer->state }} {{ $sale->customer->pincode }}<br>
                    GSTIN: <strong>{{ $sale->customer->gst_no ?? 'URP' }}</strong><br>
                    {{ $sale->customer->email ?? '' }}
                </div>
            </td>
            <td style="padding-left: 15px;">
                <div class="billing-title">Shipped To</div>
                <div class="bold-name">{{ $sale->customer->company_name ?? $sale->customer->customer_name }}</div>
                <div style="font-size: 10px; line-height: 1.6;">
                    {{ $sale->customer->address }}<br>
                    {{ $sale->customer->city }}, {{ $sale->customer->state }} {{ $sale->customer->pincode }}<br>
                    GSTIN: <strong>{{ $sale->customer->gst_no ?? 'URP' }}</strong><br>
                    Contact: {{ $sale->customer->phone ?? 'N/A' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Additional Details -->
    <table class="details-wrapper" style="width: 100%;">
        <tr>
            <td>
                <span class="detail-label">E-way Bill No.</span>
                <span class="detail-value">{!! $sale->eway_bill_no ?? 'N/A' !!}</span>
            </td>
            <td>
                <span class="detail-label">E-way Bill Date</span>
                <span
                    class="detail-value">{!! $sale->eway_bill_date ? \Carbon\Carbon::parse($sale->eway_bill_date)->format('d-m-Y') : 'N/A' !!}</span>
            </td>
            <td>
                <span class="detail-label">Buyer's Order No.</span>
                <span class="detail-value">{!! $sale->buyer_order_no ?? 'N/A' !!}</span>
            </td>
            <td>
                <span class="detail-label">Buyer's Order Date</span>
                <span
                    class="detail-value">{!! $sale->buyer_order_date ? \Carbon\Carbon::parse($sale->buyer_order_date)->format('d-m-Y') : 'N/A' !!}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="detail-label">Dispatched through</span>
                <span class="detail-value">{!! $sale->dispatched_through ?? 'N/A' !!}</span>
            </td>
            <td>
                <span class="detail-label">Destination</span>
                <span class="detail-value">{!! $sale->destination ?? 'N/A' !!}</span>
            </td>
            <td>
                <span class="detail-label">Delivery Note</span>
                <span class="detail-value">{!! $sale->delivery_note ?? 'N/A' !!}</span>
            </td>
            <td>
                <span class="detail-label">Terms of Payment</span>
                <span class="detail-value">{!! $sale->mode_of_payment ?? 'N/A' !!}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="detail-label">Reference No. & Date</span>
                <span class="detail-value">{!! $sale->reference_no_date ?? 'N/A' !!}</span>
            </td>
            <td>
                <span class="detail-label">Other References</span>
                <span class="detail-value">{!! $sale->other_references ?? 'N/A' !!}</span>
            </td>
            <td colspan="2">
                <span class="detail-label">Terms of Delivery</span>
                <span class="detail-value">{!! $sale->terms_of_delivery ?? 'N/A' !!}</span>
            </td>
        </tr>
    </table>

    <!-- Table -->
    <div class="table-wrapper">
        <table class="items-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="45%">Item Description</th>
                    <th width="15%" class="text-center">Qty</th>
                    <th width="15%" class="text-right">Rate</th>
                    <th width="20%" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="item-title">
                                {{ $item->product->item_name ?? $item->product->product_name ?? 'Product' }}
                            </div>
                            <div class="item-desc">
                                HSN: {{ $item->product->hsn_code ?? '85362090' }}
                                @if(isset($item->product->group))
                                    | Grp: {{ $item->product->group->name }}
                                @endif
                            </div>
                        </td>
                        <td class="text-center">{{ number_format($item->quantity, 0) }} NOS</td>
                        <td class="text-right">₹{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">₹{{ number_format($item->total_price, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Summary -->
    <div class="summary-container">
        <table class="summary-table">
            <tr class="summary-row">
                <td>Subtotal</td>
                <td class="val">₹{{ number_format($sale->total_amount, 2) }}</td>
            </tr>
            <tr class="summary-row">
                <td>CGST (9%)</td>
                <td class="val">₹{{ number_format($cgst, 2) }}</td>
            </tr>
            <tr class="summary-row">
                <td>SGST (9%)</td>
                <td class="val">₹{{ number_format($sgst, 2) }}</td>
            </tr>
            <tr class="summary-row total">
                <td>Total Amount</td>
                <td class="val">₹{{ number_format($total_with_tax, 2) }}</td>
            </tr>
        </table>
        <div style="clear: both;"></div>
        <div class="amount-words text-right">
            Amount Chargeable (in words): <strong>INR {{ getIndianCurrency($total_with_tax) }}</strong>
        </div>
    </div>

    <!-- Footer -->
    <table class="footer" style="width: 100%;">
        <tr>
            <td style="width: 60%;">
                <div style="padding-right: 20px;">
                    <h4>Terms & Conditions</h4>
                    <p>
                        1. Payment is due within 30 days from the invoice date.<br>
                        2. Interest @ 24% will be charged if payment is not made within the due date.<br>
                        3. Goods once sold will not be taken back without proper authorization.<br>
                        4. We declare that this invoice shows the actual price of the goods described and that all
                        particulars are true and correct.
                    </p>
                </div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="payment-info">
                    <h4>Payment Details</h4>
                    <p>
                        <strong>Bank:</strong> ICICI BANK<br>
                        <strong>A/C No:</strong> 777705461505<br>
                        <strong>IFSC:</strong> ICIC0003412<br>
                        <strong>Branch:</strong> Waghodia
                    </p>
                </div>
            </td>
        </tr>
    </table>

    <div style="text-align: right; margin-top: 15px; font-size: 9px;">
        <span style="font-weight: bold;">for DEMO ERP</span><br><br>
        Authorised Signatory
    </div>



</body>

</html>