<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Quotation</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
            color: #333333;
            margin: 0;
            padding: 0;
        }
        .header-table { width: 100%; margin-bottom: 10px; }
        .quote-title { font-size: 28px; font-weight: bold; color: #0baabf; font-style: italic; text-align: right; }
        .divider { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .divider td.teal { background-color: #0baabf; height: 3px; width: 35%; }
        .divider td.dark { background-color: #2d2d2d; height: 3px; width: 65%; }
        
        .info-table { width: 100%; margin-bottom: 15px; }
        .client-info h2 { font-size: 14px; color: #2d2d2d; text-transform: uppercase; margin: 0 0 5px 0; }
        .client-info p { font-size: 10px; color: #777777; line-height: 1.4; margin: 0; text-transform: uppercase; }
        
        .quote-meta { text-align: right; font-size: 10px; color: #777777; text-transform: uppercase; }
        .quote-meta .number { font-size: 14px; font-weight: bold; color: #777777; margin: 5px 0; }
        
        .thin-line { border-top: 1px solid #cccccc; margin-bottom: 15px; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th, .data-table td { padding: 6px 4px; font-size: 9px; text-align: center; border: 1px solid #e0e0e0; }
        .data-table th { background-color: #2d2d2d; color: #ffffff; text-transform: uppercase; font-weight: bold; border-color: #2d2d2d; }
        .data-table tbody tr:nth-child(odd) { background-color: #f2f2f2; }
        .data-table tbody tr:nth-child(even) { background-color: #ffffff; }
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
        
        .totals-row td { border-top: 2px solid #2d2d2d; font-weight: bold; padding-top: 10px; background-color: #ffffff; }
        
        .bottom-shapes-table { width: 100%; border-collapse: collapse; height: 40px; position: fixed; bottom: 0; left: 0; margin: 0; padding: 0; }
        .bottom-shapes-table td { padding: 0; }
        
        .admin-badge {
            background-color: #dc3545;
            color: white;
            padding: 3px 8px;
            font-size: 10px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <h1 style="color: #2d2d2d; margin: 0; font-size: 24px;">Demo ERP</h1>
            </td>
            <td style="width: 50%;" class="quote-title">
                <div class="admin-badge">INTERNAL / ADMIN</div><br>
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
                <p>
                    {{ $quotation->customer->address }}<br>
                    {{ $quotation->customer->city }}, {{ $quotation->customer->state }} {{ $quotation->customer->pincode }}<br>
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
                <th width="3%">SR</th>
                <th width="15%" class="text-left">DESCRIPTION</th>
                <th width="10%">PART CODE</th>
                <th width="5%">QTY</th>
                <th width="8%">LP</th>
                @php
                    $canViewPurDisc = auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Admin']);
                    $canViewProfit = auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('View Profit'));
                @endphp
                @if($canViewPurDisc)
                <th width="7%">PUR DISC</th>
                @endif
                @if($canViewProfit)
                <th width="8%">PUR RATE</th>
                @endif
                <th width="7%">CUST DISC</th>
                <th width="8%">NET RATE</th>
                @if($canViewProfit)
                <th width="8%">PROFIT ₹</th>
                <th width="6%">PROFIT %</th>
                @endif
                <th width="8%">TOTAL</th>
                <th width="7%">AVAILABILITY</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalQty = 0; 
                $totalProfitPctSum = 0;
                $itemCount = count($quotation->items);
            @endphp
            @foreach($quotation->items as $index => $item)
            @php 
                $totalQty += $item->quantity; 
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
                <td>{{ number_format($item->profit_percentage, 2) }}%</td>
                @endif
                <td class="text-right">Rs. {{ number_format($item->line_total ?? ($item->customer_rate * $item->quantity), 2) }}</td>
                <td>EX-STOCK</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            @if($quotation->freight_charges > 0)
            <tr>
                <td colspan="{{ 4 + ($canViewPurDisc ? 1 : 0) + ($canViewProfit ? 1 : 0) + 2 + ($canViewProfit ? 2 : 0) }}" class="text-right fw-bold">FREIGHT</td>
                <td class="text-right fw-bold">Rs. {{ number_format($quotation->freight_charges, 2) }}</td>
                <td></td>
            </tr>
            @endif
            <tr class="totals-row">
                <td colspan="3" class="text-left">TOTAL ({{ $itemCount }} ITEMS)</td>
                <td>{{ $totalQty }}</td>
                <td colspan="{{ ($canViewPurDisc ? 1 : 0) + ($canViewProfit ? 1 : 0) + 2 }}"></td>
                @if($canViewProfit)
                <td class="text-right">AVG PROFIT:</td>
                <td>{{ $itemCount > 0 ? number_format($totalProfitPctSum / $itemCount, 2) : '0.00' }}%</td>
                @endif
                <td class="text-right">Rs. {{ number_format($quotation->grand_total, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    
    <table class="bottom-shapes-table">
        <tr>
            <td style="background-color: #0baabf; width: 45%; height: 40px;"></td>
            <td style="width: 30px; height: 40px; vertical-align: top;">
                <div style="width: 0; height: 0; border-top: 40px solid #0baabf; border-right: 30px solid transparent;"></div>
            </td>
            <td style="width: 15px; height: 40px;"></td>
            <td style="width: 30px; height: 40px; vertical-align: bottom;">
                <div style="width: 0; height: 0; border-bottom: 40px solid #2d2d2d; border-left: 30px solid transparent;"></div>
            </td>
            <td style="background-color: #2d2d2d; width: 45%; height: 40px;"></td>
        </tr>
    </table>
</body>
</html>
