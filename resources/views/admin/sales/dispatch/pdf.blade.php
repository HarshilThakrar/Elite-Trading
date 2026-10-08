<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Tax Invoice {{ $dispatch->dispatch_number }}</title>
    <style>
        @page {
            margin: 115px 25px 40px 25px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5px;
            color: #000;
            margin: 0;
            padding: 0;
            background-color: transparent;
        }

        #footer {
            position: fixed;
            bottom: -40px;
            left: 0px;
            right: 0px;
            height: 20px;
            text-align: center;
            font-size: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0px;
            page-break-inside: auto;
        }

        tr {
            page-break-inside: auto;
            page-break-after: auto;
        }

        td,
        th {
            border: 1px solid #000;
            padding: 2px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .no-border {
            border: none !important;
        }

        .no-border-bottom {
            border-bottom: none !important;
        }

        .no-border-top {
            border-top: none !important;
        }

        .no-border-left {
            border-left: none !important;
        }

        .no-border-right {
            border-right: none !important;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .fw-bold {
            font-weight: bold;
        }

        .title {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            border: none;
            padding: 5px;
        }

        /* Layout */
        .wrapper {
            border: 1px solid #000;
            width: 100%;
        }

        /* Specific widths for Items grid */
        .col-si {
            width: 4%;
        }

        .col-desc {
            width: 40%;
        }

        .col-hsn {
            width: 10%;
        }

        .col-qty {
            width: 8%;
        }

        .col-rate {
            width: 10%;
        }

        .col-per {
            width: 6%;
        }

        .col-disc {
            width: 7%;
        }

        .col-amt {
            width: 15%;
            text-align: right;
        }

        /* Remove inner borders for standard fields */
        .grid-header td {
            border: 1px solid #000;
        }

        .item-row td {
            border-top: none;
            border-bottom: none;
            height: 16px;
            padding-top: 2px;
            padding-bottom: 2px;
        }

        .empty-row td {
            border-top: none;
            border-bottom: none;
            height: 5px;
        }

        .summary-row td {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-weight: bold;
        }

        .footer-grid td {
            border: 1px solid #000;
        }

        .small-text {
            font-size: 7.5px;
        }
    </style>
</head>

<body>

    <?php
$total_amount = 0;
foreach($dispatch->items as $item) {
    $total_amount += ($item->dispatched_qty * $item->saleItem->unit_price);
}
$cgst = $total_amount * 0.09;
$sgst = $total_amount * 0.09;
$total_with_tax = $total_amount + $cgst + $sgst;

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

    <div id="footer">
        This is a Computer Generated Invoice
    </div>

    <table class="grid-header">
        <tr>
            <td rowspan="3" style="width: 50%; padding: 0;">
                <table class="no-border" style="width: 100%; margin: 0;">
                    <tr>
                        <td class="no-border" style="width: 40%; vertical-align: top; text-align: left; padding: 4px;">
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('elite.png'))) }}"
                                alt="Demo Logo" style="max-height: 80px; max-width: 100%;">
                        </td>
                        <td class="no-border" style="width: 60%; vertical-align: top; text-align: right; padding: 4px;">
                            GF-25, EARTH ICON,<br>
                            NR. KHODIYAR NAGAR CROSS ROADS,<br>
                            NEW VIP ROAD,<br>
                            VADODARA-390019<br>
                            GSTIN/UIN: <span class="fw-bold">24BLZPM7890R1ZL</span><br>
                            State Name : Gujarat, Code : 24<br>
                            E-Mail : info@demo.com<br>
                            PAN : <span class="fw-bold">BLZPM7890R</span>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 25%; text-align: center;">
                @if(isset($is_einvoice) && $is_einvoice)
                    <div style="margin-bottom: 5px;">
                        <img src="data:image/svg+xml;base64,{{ base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::size(60)->generate('Invoice: ' . $sale->invoice_number . ' | Date: ' . $sale->sale_date . ' | Amount: ' . $sale->total_amount)) }}" alt="QR Code"><br>
                        <strong style="font-size: 8px;">e-Invoice</strong>
                    </div>
                @endif
                Invoice No.<br>
                <strong>{{ $dispatch->dispatch_number }}</strong>
            </td>
            <td style="width: 25%; text-align: center;">
                Dated<br>
                <strong>{{ \Carbon\Carbon::parse($sale->sale_date)->format('d-M-y') }}</strong>
            </td>
        </tr>
        <tr>
            <td>
                Delivery Note<br>
                <strong>{!! $sale->delivery_note ?? '&nbsp;' !!}</strong>
            </td>
            <td>
                Mode/Terms of Payment<br>
                <strong>{!! $sale->mode_of_payment ?? '&nbsp;' !!}</strong>
            </td>
        </tr>
        <tr>
            <td>
                Reference No. & Date.<br>
                <strong>{!! $sale->reference_no_date ?? '&nbsp;' !!}</strong>
            </td>
            <td>
                Other References<br>
                <strong>{!! $sale->other_references ?? '&nbsp;' !!}</strong>
            </td>
        </tr>
        <tr>
            <td rowspan="4">
                Consignee (Ship to)<br>
                <strong>{{ $sale->customer->company_name ?? $sale->customer->customer_name }}</strong><br>
                {{ $sale->customer->address }}<br>
                {{ $sale->customer->city }}, {{ $sale->customer->state }} {{ $sale->customer->pincode }}<br>
                GSTIN/UIN: <strong>{{ $sale->customer->gst_no ?? 'URP' }}</strong><br>
                State Name : {{ $sale->customer->state ?? 'Gujarat' }}, Code : 24
            </td>
            <td>
                Buyer's Order No.<br>
                <strong>{!! $sale->buyer_order_no ?? '&nbsp;' !!}</strong>
            </td>
            <td>
                Dated<br>
                <strong>{!! $sale->buyer_order_date ? \Carbon\Carbon::parse($sale->buyer_order_date)->format('d-M-y') : '&nbsp;' !!}</strong>
            </td>
        </tr>
        <tr>
            <td>
                E-way Bill No.<br>
                <strong>{!! $sale->eway_bill_no ?? '&nbsp;' !!}</strong>
            </td>
            <td>
                E-way Bill Date<br>
                <strong>{!! $sale->eway_bill_date ? \Carbon\Carbon::parse($sale->eway_bill_date)->format('d-M-y') : '&nbsp;' !!}</strong>
            </td>
        </tr>
        <tr>
            <td>
                Dispatched through<br>
                <strong>{!! $sale->dispatched_through ?? '&nbsp;' !!}</strong>
            </td>
            <td>
                Destination<br>
                <strong>{!! $sale->destination ?? '&nbsp;' !!}</strong>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                Terms of Delivery<br>
                <strong>{!! $sale->terms_of_delivery ?? '&nbsp;' !!}</strong>
            </td>
        </tr>
        <tr>
            <td>
                Buyer (Bill to)<br>
                <strong>{{ $sale->customer->company_name ?? $sale->customer->customer_name }}</strong><br>
                {{ $sale->customer->address }}<br>
                {{ $sale->customer->city }}, {{ $sale->customer->state }} {{ $sale->customer->pincode }}<br>
                GSTIN/UIN: <strong>{{ $sale->customer->gst_no ?? 'URP' }}</strong><br>
                State Name : {{ $sale->customer->state ?? 'Gujarat' }}, Code : 24
            </td>
            <td colspan="2">
                &nbsp;
            </td>
        </tr>
    </table>

    <table style="border-top: none;">
        <thead>
            <tr>
                <th class="col-si">SI<br>No.</th>
                <th class="col-desc">Description of Goods</th>
                <th class="col-hsn">HSN/SAC</th>
                <th class="col-qty">Quantity</th>
                <th class="col-rate">Rate</th>
                <th class="col-per">per</th>
                <th class="col-disc">Disc. %</th>
                <th class="col-amt">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQty = 0; @endphp
            @foreach($dispatch->items as $index => $item)
                @php 
                    $qty = $item->dispatched_qty;
                    $totalQty += $qty; 
                    $unitPrice = $item->saleItem->unit_price;
                    $totalPrice = $qty * $unitPrice;
                @endphp
                <tr class="item-row">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->product->item_name ?? $item->product->product_name ?? 'Product' }}</strong>
                        @if(isset($item->product->group))
                            <br><small style="font-size: 7px; color: #555;">Grp: {{ $item->product->group->name }}</small>
                        @endif
                        @if(isset($item->product->subgroup))
                            <small style="font-size: 7px; color: #555;"> | Subgrp: {{ $item->product->subgroup->name }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->product->hsn_code ?? '85362090' }}</td>
                    <td class="text-center fw-bold">{{ number_format($qty, 0) }} NOS</td>
                    <td class="text-right">{{ number_format($unitPrice, 2) }}</td>
                    <td class="text-center">NOS</td>
                    <td class="text-center">66 %</td>
                    <td class="text-right fw-bold">{{ number_format($totalPrice, 2) }}</td>
                </tr>
            @endforeach

            <tr class="item-row">
                <td></td>
                <td>&nbsp;</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>

            <tr class="item-row">
                <td></td>
                <td class="text-right fw-bold" style="padding-right: 15px;"><i>CGST</i></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right fw-bold">{{ number_format($cgst, 2) }}</td>
            </tr>
            <tr class="item-row">
                <td></td>
                <td class="text-right fw-bold" style="padding-right: 15px;"><i>SGST</i></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right fw-bold">{{ number_format($sgst, 2) }}</td>
            </tr>

            <tr class="item-row">
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>

            <tr class="summary-row">
                <td colspan="2" class="text-right">Total</td>
                <td></td>
                <td class="text-center">{{ number_format($totalQty, 0) }} NOS</td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right">₹ {{ number_format($total_with_tax, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="footer-grid" style="border-top: none;">
        <tr>
            <td colspan="4">
                Amount Chargeable (in words)<br>
                <strong>INR {{ getIndianCurrency($total_with_tax) }}</strong><span style="float: right;"><i>E. &
                        O.E</i></span>
            </td>
        </tr>
        <tr>
            <td rowspan="2" class="text-center fw-bold" style="width: 30%;">HSN/SAC</td>
            <td rowspan="2" class="text-center fw-bold" style="width: 20%;">Taxable<br>Value</td>
            <td colspan="2" class="text-center fw-bold" style="width: 30%;">CGST</td>
            <td colspan="2" class="text-center fw-bold" style="width: 20%;">Total<br>Tax Amount</td>
        </tr>
        <tr>
            <td class="text-center fw-bold">Rate</td>
            <td class="text-center fw-bold">Amount</td>
            <td class="text-center fw-bold">Rate</td>
            <td class="text-center fw-bold">Amount</td>
        </tr>
        <tr>
            <td>85362090</td>
            <td class="text-right">{{ number_format($total_amount, 2) }}</td>
            <td class="text-center">9%</td>
            <td class="text-right">{{ number_format($cgst, 2) }}</td>
            <td class="text-center">9%</td>
            <td class="text-right">{{ number_format($cgst + $sgst, 2) }}</td>
        </tr>
        <tr class="summary-row">
            <td class="text-right">Total</td>
            <td class="text-right">{{ number_format($total_amount, 2) }}</td>
            <td class="text-center"></td>
            <td class="text-right">{{ number_format($cgst, 2) }}</td>
            <td class="text-center"></td>
            <td class="text-right">{{ number_format($cgst + $sgst, 2) }}</td>
        </tr>
        <tr>
            <td colspan="6">
                Tax Amount (in words) : <strong>INR {{ getIndianCurrency($cgst + $sgst) }}</strong>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="width: 50%; padding: 5px; border-top: 1px solid #000;">
                <u>Declaration</u><br>
                <span class="small-text">1) We declare that this invoice shows the actual price of the goods described
                    and that all particulars are true and correct. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2) Interest @
                    24% shall be charged, if not paid within stipulated payment terms.</span>
            </td>
            <td colspan="3" style="width: 50%; padding: 0; border-top: 1px solid #000;">
                <table class="no-border" style="width: 100%;">
                    <tr>
                        <td class="no-border" colspan="2" style="padding-left: 10px;">
                            <strong>Company's Bank Details</strong><br>
                            Bank Name &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <strong>ICICI
                                BANK</strong><br>
                            A/c No.
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:
                            <strong>777705461505</strong><br>
                            Branch & IFS Code : <strong>Waghodia & ICIC0003412</strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="no-border"
                            style="border-top: 1px solid #000 !important; text-align: right; padding: 5px;">
                            <span style="font-weight: bold;">for DEMO ERP - (from F.Y. 2024)</span><br><br><br>
                            Authorised Signatory
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>

</html>