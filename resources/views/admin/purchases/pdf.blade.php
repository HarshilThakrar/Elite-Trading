<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order {{ $purchase->po_number }}</title>
    <style>
        @page { margin: 15px 15px 40px 15px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #000; margin: 0; padding: 0; }
        #footer { position: fixed; bottom: -30px; left: 0px; right: 0px; height: 20px; text-align: center; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 0px; page-break-inside: auto; }
        tr { page-break-inside: auto; page-break-after: auto; }
        td, th { border: 1px solid #000; padding: 4px; vertical-align: top; word-wrap: break-word; }
        
        .no-border { border: none !important; }
        .no-border-bottom { border-bottom: none !important; }
        .no-border-top { border-top: none !important; }
        .no-border-left { border-left: none !important; }
        .no-border-right { border-right: none !important; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .title { font-size: 16px; font-weight: bold; text-align: center; border: none; padding: 10px; }
        
        /* Layout */
        .wrapper { border: 1px solid #000; width: 100%; }
        
        /* Specific widths for Items grid */
        .col-si { width: 4%; }
        .col-desc { width: 40%; }
        .col-hsn { width: 10%; }
        .col-qty { width: 8%; }
        .col-rate { width: 10%; }
        .col-per { width: 6%; }
        .col-disc { width: 7%; }
        .col-amt { width: 15%; text-align: right; }
        
        /* Remove inner borders for standard fields */
        .grid-header td { border: 1px solid #000; }
        
        .item-row td { border-top: none; border-bottom: none; height: 16px; padding-top: 2px; padding-bottom: 2px; }
        .empty-row td { border-top: none; border-bottom: none; height: 20px; }
        
        .summary-row td { border-top: 1px solid #000; border-bottom: 1px solid #000; font-weight: bold; }
        
        .footer-grid td { border: 1px solid #000; }
        
        .small-text { font-size: 9px; }
    </style>
</head>
<body>

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
        $words = array(0 => '', 1 => 'One', 2 => 'Two',
            3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
            7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
            13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
            19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
            40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
            70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety');
        $digits = array('', 'Hundred','Thousand','Lakh', 'Crore');
        while( $i < $digits_length ) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += $divider == 10 ? 1 : 2;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str [] = ($number < 21) ? $words[$number].' '. $digits[$counter]. $plural.' '.$hundred:$words[floor($number / 10) * 10].' '.$words[$number % 10]. ' '.$digits[$counter].$plural.' '.$hundred;
            } else $str[] = null;
        }
        $Rupees = implode('', array_reverse($str));
        $paise = ($decimal > 0) ? " and " . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
        return ($Rupees ? $Rupees . 'Rupees ' : '') . $paise . ' Only';
    }
?>

<div id="footer">
    This is a Computer Generated Purchase Order
</div>

<div class="title">Purchase Order</div>

<table class="grid-header">
    <tr>
        <td rowspan="3" style="width: 50%;">
            <div style="font-size: 13px; font-weight: bold;">Demo ERP System</div>
            <div class="small-text mt-1">
                Head Office: 123 Business Avenue<br>
                Industrial Estate, Gujarat 390001<br>
                GSTIN/UIN: 24AAAAA0000A1Z5<br>
                State Name: Gujarat, Code: 24<br>
                Email: support@demoerp.local
            </div>
        </td>
        <td style="width: 25%;">
            <span class="small-text">PO No.</span><br>
            <strong style="font-size: 11px;">{{ $purchase->po_number }}</strong>
        </td>
        <td style="width: 25%;">
            <span class="small-text">Dated</span><br>
            <strong style="font-size: 11px;">{{ \Carbon\Carbon::parse($purchase->po_date)->format('d-M-y') }}</strong>
        </td>
    </tr>
    <tr>
        <td>
            <span class="small-text">Supplier Ref.</span><br>
            <strong>N/A</strong>
        </td>
        <td>
            <span class="small-text">Other Reference(s)</span><br>
            <strong>N/A</strong>
        </td>
    </tr>
    <tr>
        <td>
            <span class="small-text">Dispatch through</span><br>
            <strong>To be advised</strong>
        </td>
        <td>
            <span class="small-text">Destination</span><br>
            <strong>Warehouse</strong>
        </td>
    </tr>
    <tr>
        <td rowspan="3">
            <span class="small-text">Supplier (To)</span><br>
            <strong style="font-size: 12px;">{{ optional($purchase->vendor)->company_name ?? 'N/A' }}</strong><br>
            <span class="small-text">
                {{ optional($purchase->vendor)->contact_person }}<br>
                {{ optional($purchase->vendor)->mobile }}<br>
                GSTIN/UIN : {{ optional($purchase->vendor)->gst_no ?? 'Unregistered' }}<br>
            </span>
        </td>
        <td colspan="2">
            <span class="small-text">Terms of Delivery</span><br>
            <span class="small-text">Standard Delivery</span>
        </td>
    </tr>
</table>

<table style="border-top: none;">
    <thead>
        <tr>
            <th style="width: 4%;">Sl<br>No.</th>
            <th style="width: 46%;">Description of Goods</th>
            <th style="width: 10%;">HSN/SAC</th>
            <th style="width: 10%;">Quantity</th>
            <th style="width: 10%;">Rate</th>
            <th style="width: 5%;">per</th>
            <th style="width: 15%;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @php
            $count = 1;
            $total_qty = 0;
            $total_amount = 0;
        @endphp

        @foreach($purchase->items as $item)
            @php
                $qty = $item->quantity;
                $rate = $item->unit_price;
                $amt = $item->total_price;
                
                $total_qty += $qty;
                $total_amount += $amt;
                $hsn = optional($item->product)->hsn_code ?? '8505';
            @endphp
            <tr class="item-row">
                <td class="text-center">{{ $count++ }}</td>
                <td>
                    <strong style="font-size: 10px;">{{ optional($item->product)->item_name ?? 'Unknown Item' }}</strong><br>
                    <span class="small-text">Part: {{ optional($item->product)->part_code }}</span>
                </td>
                <td class="text-center">{{ $hsn }}</td>
                <td class="text-right fw-bold">{{ $qty }} {{ optional($item->product)->uom ?? 'Nos' }}</td>
                <td class="text-right">{{ number_format($rate, 2) }}</td>
                <td class="text-center">{{ optional($item->product)->uom ?? 'Nos' }}</td>
                <td class="text-right fw-bold">{{ number_format($amt, 2) }}</td>
            </tr>
        @endforeach
        
        <tr class="item-row" style="height: 200px;">
            <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
        </tr>
        
        <tr class="summary-row">
            <td></td>
            <td class="text-right">Total</td>
            <td></td>
            <td class="text-right fw-bold">{{ $total_qty }} Nos</td>
            <td></td>
            <td></td>
            <td class="text-right fw-bold">₹ {{ number_format($total_amount, 2) }}</td>
        </tr>
    </tbody>
</table>

<table style="border-top: none;">
    <tr>
        <td colspan="6">
            Amount Chargeable (in words) : <strong>INR {{ getIndianCurrency($total_amount) }}</strong>
        </td>
    </tr>
    <tr>
        <td colspan="3" style="width: 50%; padding: 10px; border-top: 1px solid #000;">
            Company's PAN &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <strong style="font-size: 13px;">BLZPM7890R</strong><br><br>
            <u>Declaration</u><br>
            <span class="small-text">1) We declare that this order shows the actual items/services required. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2) Terms and conditions as per our standard PO agreement.</span>
        </td>
        <td colspan="3" style="width: 50%; padding: 0; border-top: 1px solid #000;">
            <table class="no-border" style="width: 100%;">
                <tr>
                    <td class="no-border" colspan="2" style="padding-left: 10px;">
                        <strong>Company's Bank Details</strong><br>
                        Bank Name &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <strong>ICICI BANK</strong><br>
                        A/c No. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <strong>777705461505</strong><br>
                        Branch & IFS Code : <strong>Waghodia & ICIC0003412</strong>
                    </td>
                </tr>
                <tr>
                    <td class="no-border" style="border-top: 1px solid #000 !important; text-align: right; padding: 10px;">
                        <span style="font-weight: bold;">for DEMO ERP SYSTEM</span><br><br><br><br>
                        Authorised Signatory
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
