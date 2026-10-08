<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <!-- Add html2canvas if needed for image export -->
    @if(isset($forImage) && $forImage)
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <style>
        body { margin: 0; padding: 20px; font-family: 'Arial', sans-serif; background: #fff; }
        .export-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .export-table th, .export-table td { border: 1px solid #000; padding: 4px; text-align: center; }
        .export-table th { font-weight: bold; }
        .text-red { color: #c00000; font-weight: bold; }
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
    </style>
    @else
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 10px; }
        .export-table { width: 100%; border-collapse: collapse; }
        .export-table th, .export-table td { border: 1px solid #000; padding: 4px; text-align: center; }
        .export-table th { font-weight: bold; }
        .text-red { color: #c00000; font-weight: bold; }
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
    </style>
    @endif
</head>
<body>
    <div id="export-container">
        <table class="export-table">
            <thead>
                <tr>
                    <th colspan="12" class="text-red" style="font-size: 16px; padding: 10px;">QUOTATION</th>
                </tr>
                <tr>
                    <td colspan="2" class="text-red text-left">DEMO ERP SYSTEM :</td>
                    <td colspan="8" class="text-left fw-bold">GF 25, EARTH ICON, NR. KHODIYAR NAGAR CHAR RASTA, NEW VIP ROAD, VADODARA-390019</td>
                    <td colspan="2" class="text-red text-right">DATE :{{ \Carbon\Carbon::parse($quotation->quotation_date)->format('d-m-Y') }}</td>
                </tr>
                <tr>
                    <td colspan="10"></td>
                    <td colspan="2" class="text-red text-right">QUOTE NO. :{{ $quotation->quotation_number }}</td>
                </tr>
                <tr>
                    <th>SR.NO.</th>
                    <th>DESCRIPTION</th>
                    <th>PART CODE</th>
                    <th>QTY</th>
                    <th>LP (MANUALLY CHANGE)</th>
                    @php
                        $canViewProfit = auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('View Profit'));
                        $canViewPurDisc = auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Admin']);
                    @endphp
                    @if($canViewPurDisc)
                    <th>PURCHASE DISCOUNT</th>
                    @endif
                    @if($canViewProfit)
                    <th>PUR RATE</th>
                    @endif
                    <th>CUSTOMER DISCOUNT</th>
                    <th>NETRATE</th>
                    @if($canViewProfit)
                    <th>NET PROFIT Rs.</th>
                    <th>NET PROFIT %</th>
                    @endif
                    <th>AVAILABILITY</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $totalProfitPctSum = 0;
                    $itemCount = count($quotation->items);
                @endphp
                @foreach($quotation->items as $index => $item)
                @php
                    $totalProfitPctSum += $item->profit_percentage;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">{{ $item->product->item_name ?? $item->product->product_name }}</td>
                    <td>{{ $item->product->part_code }}</td>
                    <td>{{ number_format($item->quantity, 0) }}</td>
                    <td>{{ number_format($item->list_price, 2) }}</td>
                    @if($canViewPurDisc)
                    <td>{{ number_format($item->purchase_discount, 2) }}%</td>
                    @endif
                    @if($canViewProfit)
                    <td>{{ number_format($item->purchase_rate, 2) }}</td>
                    @endif
                    <td>{{ number_format($item->customer_discount, 2) }}%</td>
                    <td>{{ number_format($item->customer_rate, 2) }}</td>
                    @if($canViewProfit)
                    <td>{{ number_format($item->profit_amount, 2) }}</td>
                    <td>{{ number_format($item->profit_percentage, 2) }}</td>
                    @endif
                    <td>EX-STOCK</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="2" class="text-right fw-bold">FREIGHT</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    @if($canViewProfit)
                    <td></td>
                    <td></td>
                    @endif
                    <td></td>
                    <td class="text-center">{{ number_format($quotation->freight_charges, 2) }}</td>
                    @if($canViewProfit)
                    <td></td>
                    <td></td>
                    @endif
                    <td></td>
                </tr>
                <tr>
                    <td colspan="2" class="text-right fw-bold">TOTAL</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    @if($canViewProfit)
                    <td></td>
                    <td></td>
                    @endif
                    <td></td>
                    <td class="text-center fw-bold">{{ number_format($quotation->grand_total, 2) }}</td>
                    @if($canViewProfit)
                    <td class="text-right fw-bold">AVERAGE PROFIT %</td>
                    <td class="text-center">{{ $itemCount > 0 ? number_format($totalProfitPctSum / $itemCount, 2) : '0.00' }}</td>
                    @else
                    <td></td>
                    <td></td>
                    @endif
                    <td></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="12" class="text-left">
                        NOTE :<br>
                        1. GST EXTRA.
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if(isset($forImage) && $forImage)
    <script>
        window.onload = function() {
            html2canvas(document.getElementById("export-container"), {
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
