<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order {{ $purchase->po_number }}</title>
    <style>
        @page { margin: 15px 15px 40px 15px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #000; margin: 0; padding: 0; }
        #footer { position: fixed; bottom: -30px; left: 0px; right: 0px; height: 20px; text-align: center; font-size: 9px; color: #555; }
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
        .col-qty { width: 10%; }
        .col-rate { width: 12%; }
        .col-per { width: 6%; }
        .col-amt { width: 18%; text-align: right; }
        
        .grid-header td { border: 1px solid #000; }
        
        .item-row td { border-top: none; border-bottom: none; height: 16px; padding-top: 2px; padding-bottom: 2px; }
        .empty-row td { border-top: none; border-bottom: none; height: 20px; }
        
        .summary-row td { border-top: 1px solid #000; border-bottom: 1px solid #000; font-weight: bold; }
        
        .small-text { font-size: 9px; }
    </style>
</head>
<body>

<?php
    $totalAmount = (float)($purchase->total_amount ?? 0);
    $cgst = round($totalAmount * 0.09, 2);
    $sgst = round($totalAmount * 0.09, 2);
    $total_with_tax = $totalAmount + $cgst + $sgst;
    
    if (!function_exists('getIndianCurrency')) {
        function getIndianCurrency(float $number)
        {
            $decimal = round($number - ($no = floor($number)), 2) * 100;
            $hundred = null;
            $digits_length = strlen((string)$no);
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
    }
?>

<div id="footer">
    This is a Computer Generated Purchase Order
</div>

<div class="title">PURCHASE ORDER</div>

<table class="grid-header">
    <tr>
        <td rowspan="3" style="width: 50%;">
            <div style="font-size: 13px; font-weight: bold;">ELITE TRADING AND SERVICES</div>
            <div class="small-text mt-1">
                GF-25, Earth Icon, Nr. Khodiyar Nagar Cross Roads,<br>
                New VIP Road, Vadodara - 390019, Gujarat<br>
                GSTIN/UIN: 24AAAAA0000A1Z5<br>
                State Name: Gujarat, Code: 24<br>
                Email: vt.elitetrading@gmail.com
            </div>
        </td>
        <td style="width: 25%;">
            <span class="small-text">PO No.</span><br>
            <strong style="font-size: 11px;">{{ $purchase->po_number }}</strong>
        </td>
        <td style="width: 25%;">
            <span class="small-text">Dated</span><br>
            <strong style="font-size: 11px;">{{ \Carbon\Carbon::parse($purchase->po_date)->format('d-M-Y') }}</strong>
        </td>
    </tr>
    <tr>
        <td>
            <span class="small-text">Status</span><br>
            <strong>{{ $purchase->status ?? 'Approved' }}</strong>
        </td>
        <td>
            <span class="small-text">Mode of Terms</span><br>
            <strong>Standard Terms</strong>
        </td>
    </tr>
    <tr>
        <td>
            <span class="small-text">Dispatch Through</span><br>
            <strong>Road / Surface</strong>
        </td>
        <td>
            <span class="small-text">Destination</span><br>
            <strong>Vadodara Warehouse</strong>
        </td>
    </tr>
    <tr>
        <td rowspan="2">
            <span class="small-text">Supplier / Vendor:</span><br>
            <strong style="font-size: 12px;">{{ optional($purchase->vendor)->company_name ?? 'N/A' }}</strong>
            @if(optional($purchase->vendor)->vendor_code)
                <span>({{ $purchase->vendor->vendor_code }})</span>
            @endif
            <br>
            <span class="small-text">
                {{ optional($purchase->vendor)->contact_person }}<br>
                {{ optional($purchase->vendor)->mobile }}<br>
                GSTIN/UIN : {{ optional($purchase->vendor)->gst_no ?? 'Unregistered' }}<br>
            </span>
        </td>
        <td colspan="2">
            <span class="small-text">Notes / Narration</span><br>
            <span class="small-text">{{ $purchase->notes ?? 'Standard Purchase Order' }}</span>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <span class="small-text">Terms of Delivery</span><br>
            <span class="small-text">Door Delivery / Ex-Works</span>
        </td>
    </tr>
</table>

<table style="border-top: none;">
    <thead>
        <tr>
            <th style="width: 5%;">Sl No.</th>
            <th style="width: 45%;">Description of Goods / Services</th>
            <th style="width: 10%;">HSN/SAC</th>
            <th style="width: 10%;">Quantity</th>
            <th style="width: 12%;">Rate (₹)</th>
            <th style="width: 6%;">Per</th>
            <th style="width: 12%;">Amount (₹)</th>
        </tr>
    </thead>
    <tbody>
        @php
            $count = 1;
            $total_qty = 0;
            $items_total = 0;
        @endphp

        @forelse($purchase->items as $item)
            @php
                $qty = (float)($item->quantity ?? 1);
                $rate = (float)($item->unit_price ?? 0);
                $amt = (float)($item->total_price ?? ($qty * $rate));
                
                $total_qty += $qty;
                $items_total += $amt;
                $hsn = optional($item->product)->hsn_code ?? '8536';
                $unit = optional($item->product)->unit ?? 'NOS';
            @endphp
            <tr class="item-row">
                <td class="text-center">{{ $count++ }}</td>
                <td>
                    <strong style="font-size: 10px;">{{ optional($item->product)->item_name ?? 'General Purchase Item' }}</strong>
                    @if(optional($item->product)->part_code)
                        <br><span class="small-text">Part Code: {{ $item->product->part_code }}</span>
                    @endif
                </td>
                <td class="text-center">{{ $hsn }}</td>
                <td class="text-right fw-bold">{{ number_format($qty, 2) }} {{ $unit }}</td>
                <td class="text-right">{{ number_format($rate, 2) }}</td>
                <td class="text-center">{{ $unit }}</td>
                <td class="text-right fw-bold">{{ number_format($amt, 2) }}</td>
            </tr>
        @empty
            <tr class="item-row">
                <td class="text-center">1</td>
                <td><strong>General Purchase / Services</strong></td>
                <td class="text-center">9983</td>
                <td class="text-right fw-bold">1.00 NOS</td>
                <td class="text-right">{{ number_format($totalAmount, 2) }}</td>
                <td class="text-center">NOS</td>
                <td class="text-right fw-bold">{{ number_format($totalAmount, 2) }}</td>
            </tr>
            @php
                $total_qty = 1;
                $items_total = $totalAmount;
            @endphp
        @endforelse
        
        <tr class="summary-row">
            <td></td>
            <td class="text-right">Total</td>
            <td></td>
            <td class="text-right fw-bold">{{ number_format($total_qty, 2) }} NOS</td>
            <td></td>
            <td></td>
            <td class="text-right fw-bold">₹ {{ number_format($items_total > 0 ? $items_total : $totalAmount, 2) }}</td>
        </tr>
    </tbody>
</table>

<table style="border-top: none;">
    <tr>
        <td colspan="6">
            Amount Chargeable (in words) : <strong>INR {{ getIndianCurrency($items_total > 0 ? $items_total : $totalAmount) }}</strong>
        </td>
    </tr>
    <tr>
        <td colspan="3" style="width: 50%; padding: 10px; border-top: 1px solid #000;">
            Company's PAN &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <strong style="font-size: 12px;">AAACE1234F</strong><br><br>
            <u>Declaration:</u><br>
            <span class="small-text">1) We declare that this Purchase Order shows the actual items/services ordered.<br>2) Delivery and invoicing terms as agreed in company policy.</span>
        </td>
        <td colspan="3" style="width: 50%; padding: 0; border-top: 1px solid #000;">
            <table class="no-border" style="width: 100%;">
                <tr>
                    <td class="no-border" colspan="2" style="padding-left: 10px; padding-top: 8px;">
                        <strong>Bank Details:</strong><br>
                        Bank Name &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <strong>HDFC BANK</strong><br>
                        A/c No. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <strong>50200012345678</strong><br>
                        Branch & IFSC Code : <strong>Vadodara & HDFC0000123</strong>
                    </td>
                </tr>
                <tr>
                    <td class="no-border" style="border-top: 1px solid #000 !important; text-align: right; padding: 10px;">
                        <span style="font-weight: bold;">for ELITE TRADING AND SERVICES</span><br><br><br>
                        Authorised Signatory
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
