<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Premium Invoice</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;      /* Indigo 600 */
            --primary-light: #e0e7ff; /* Indigo 100 */
            --text-main: #1f2937;    /* Gray 800 */
            --text-muted: #6b7280;   /* Gray 500 */
            --border: #e5e7eb;       /* Gray 200 */
            --bg-color: #f3f4f6;     /* Gray 100 */
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        .action-bar {
            width: 100%;
            text-align: center;
            margin-bottom: 20px;
            position: absolute;
            top: 10px;
        }

        .action-bar button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 14px;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: background-color 0.2s;
        }

        .action-bar button:hover {
            background-color: #4338ca;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #525659;
            color: var(--text-main);
            line-height: 1.5;
            display: flex;
            justify-content: center;
            padding: 40px 0;
        }

        .invoice-wrapper {
            width: 210mm;
            min-height: auto;
            margin: 0;
            background-color: var(--white);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            position: relative;
        }

        /* Decorative top accent */
        .invoice-wrapper::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, #4f46e5, #ec4899);
        }

        .invoice-container {
            padding: 40px;
        }

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .brand-section h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .brand-section .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), #818cf8);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: bold;
        }

        .brand-section p {
            color: var(--text-muted);
            font-size: 14px;
        }

        .invoice-meta {
            text-align: right;
        }

        .invoice-meta h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-main);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 12px;
        }

        .invoice-meta .meta-grid {
            display: grid;
            grid-template-columns: auto auto;
            gap: 8px 20px;
            text-align: right;
            font-size: 14px;
        }

        .meta-grid span.label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .meta-grid span.value {
            font-weight: 600;
            color: var(--text-main);
        }

        /* Billing Section */
        .billing-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
            padding: 20px;
            background-color: #f9fafb;
            border-radius: 12px;
        }

        .billing-block h3 {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }

        .billing-block p {
            font-size: 15px;
            color: var(--text-main);
            line-height: 1.6;
        }
        
        .billing-block .bold-name {
            font-weight: 600;
            font-size: 16px;
            color: var(--primary);
            margin-bottom: 4px;
        }

        /* Additional Details */
        .additional-details {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px 20px;
            background-color: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .detail-item .label {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .detail-item .value {
            font-size: 14px;
            color: var(--text-main);
            font-weight: 500;
        }

        /* Invoice Table */
        .table-wrapper {
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 16px;
            text-align: left;
        }

        th {
            background-color: #f9fafb;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
        }

        td {
            font-size: 15px;
            color: var(--text-main);
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        
        tr:last-child td {
            border-bottom: none;
        }

        tbody tr:nth-child(odd) {
            background-color: #f9fafb; /* Light gray for odd rows */
        }
        
        tbody tr:nth-child(even) {
            background-color: var(--white);
        }

        th.text-right, td.text-right {
            text-align: right;
        }
        th.text-center, td.text-center {
            text-align: center;
        }

        .item-title {
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 4px;
        }
        .item-desc {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Summary Section */
        .summary-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }

        .summary-box {
            width: 350px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 15px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
        }

        .summary-row.total {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            border-bottom: none;
            padding-top: 15px;
        }
        
        .summary-row.total .val {
            color: var(--text-main);
        }

        /* Footer */
        footer {
            border-top: 1px solid var(--border);
            padding-top: 20px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .notes h4, .payment-info h4 {
            font-size: 13px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .notes p, .payment-info p {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
        }
        
        .payment-info p {
            color: var(--text-main);
        }
        .payment-info strong {
            font-weight: 600;
            color: var(--text-muted);
            margin-right: 8px;
        }

        .thank-you {
            text-align: center;
            margin-top: 40px;
            font-size: 16px;
            font-weight: 500;
            color: var(--text-main);
        }

        @page {
            size: A4;
            margin: 0;
        }

        @media print {
            .action-bar, .action-bar button, button, .print-hide { display: none !important; visibility: hidden !important; }
            body { 
                background: none !important; 
                padding: 0 !important; 
                display: block !important; 
                zoom: 0.88; /* Scaled slightly down to guarantee 1 page fit */
                -webkit-print-color-adjust: exact !important; /* Chrome/Safari */
                print-color-adjust: exact !important; /* Firefox/Standard */
            }
            .invoice-wrapper { 
                box-shadow: none !important; 
                width: 100% !important; 
                min-height: auto !important; 
                margin: 0 !important; 
            }
            .thank-you {
                margin-top: 20px !important;
            }
        }
    </style>
</head>
<body>

    <div class="action-bar print-hide">
        <button onclick="window.print()" class="print-hide">Download / Print PDF</button>
    </div>

    <div class="invoice-wrapper">
        <div class="invoice-container">
            
            <!-- Header -->
            <header>
                <div class="brand-section">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('elite.png'))) }}" alt="Elite Trading Logo" style="height: 65px; object-fit: contain; margin-bottom: 12px;">
                    <p>Ground Floor, GF-25, Earth Icon, Nr. Khodiyar Nagar<br>
                    Cross Roads, New VIP Road, Vadodara - 390021<br>
                    GSTIN: 24BLZPM7890R1ZL | +91 98253 27710</p>
                </div>
                
                <div class="invoice-meta">
                    <h2>Invoice</h2>
                    <div class="meta-grid">
                        <span class="label">Invoice No:</span>
                        <span class="value">{{ $invoice->invoice_number }}</span>
                        
                        <span class="label">Date:</span>
                        <span class="value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('F d, Y') }}</span>
                        
                        <span class="label">Due Date:</span>
                        <span class="value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->addDays(30)->format('F d, Y') }}</span>
                        
                        @php
                            $salesVoucher = \App\Models\Voucher::where('type', 'Sales')
                                ->where('reference_id', $invoice->id)
                                ->where('reference_type', \App\Models\Invoice::class)
                                ->first();
                        @endphp

                        <span class="label">Accounting:</span>
                        <span class="value" style="color: {{ $invoice->status === 'Posted' ? 'green' : 'orange' }}">
                            {{ $invoice->status }}
                        </span>

                        @if($salesVoucher)
                        <span class="label">Sales Voucher:</span>
                        <span class="value">
                            <a href="{{ route('sales-vouchers.show', $salesVoucher->id) }}" style="color: var(--primary); text-decoration: none;">{{ $salesVoucher->voucher_number }}</a>
                        </span>
                        @endif
                    </div>
                </div>
            </header>

            <!-- Billing Info -->
            <section class="billing-section">
                <div class="billing-block">
                    <h3>Billed To</h3>
                    <p>
                        <div class="bold-name">{{ $sale->customer->company_name ?? $sale->customer->customer_name }}</div>
                        {{ $sale->customer->address }}<br>
                        {{ $sale->customer->city }}, {{ $sale->customer->state }} {{ $sale->customer->pincode }}<br>
                        {{ $sale->customer->email ?? '' }}
                    </p>
                </div>
                <div class="billing-block">
                    <h3>Shipped To</h3>
                    <p>
                        <div class="bold-name">{{ $sale->customer->company_name ?? $sale->customer->customer_name }}</div>
                        {{ $sale->customer->address }}<br>
                        {{ $sale->customer->city }}, {{ $sale->customer->state }} {{ $sale->customer->pincode }}<br>
                        Contact: {{ $sale->customer->phone ?? '' }}
                    </p>
                </div>
            </section>

            <!-- Additional Details -->
            <section class="additional-details">
                <div class="detail-item">
                    <span class="label">E-way Bill No.</span>
                    <span class="value">{{ $sale->eway_bill_no ?? 'N/A' }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">E-way Bill Date</span>
                    <span class="value">{{ $sale->eway_bill_date ? \Carbon\Carbon::parse($sale->eway_bill_date)->format('d-m-Y') : 'N/A' }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Buyer's Order No.</span>
                    <span class="value">{{ $sale->buyer_order_no ?? 'N/A' }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Buyer's Order Date</span>
                    <span class="value">{{ $sale->buyer_order_date ? \Carbon\Carbon::parse($sale->buyer_order_date)->format('d-m-Y') : 'N/A' }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Dispatched through</span>
                    <span class="value">{{ $sale->dispatched_through ?? 'N/A' }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Destination</span>
                    <span class="value">{{ $sale->destination ?? 'N/A' }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Delivery Note</span>
                    <span class="value">{{ $sale->delivery_note ?? 'N/A' }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Terms of Payment</span>
                    <span class="value">{{ $sale->mode_of_payment ?? '30 Days' }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Reference No. & Date</span>
                    <span class="value">{{ $sale->reference_no_date ?? 'N/A' }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Other References</span>
                    <span class="value">{{ $sale->other_references ?? 'N/A' }}</span>
                </div>
                <div class="detail-item" style="grid-column: span 2;">
                    <span class="label">Terms of Delivery</span>
                    <span class="value">{{ $sale->terms_of_delivery ?? 'Door Delivery' }}</span>
                </div>
            </section>

            <!-- Table -->
            <div class="table-wrapper">
                <table>
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
                        @foreach($invoice->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div class="item-title">{{ $item->product->item_name ?? $item->product->product_name ?? 'Product' }}</div>
                                <div class="item-desc">Model: {{ $item->product->part_code ?? '' }} (HSN: {{ $item->product->hsn_code ?? '85362090' }})</div>
                            </td>
                            <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                            <td class="text-right">₹{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-right">₹{{ number_format($item->total_price, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Summary -->
            <section class="summary-section">
                <?php 
                    $cgst = $invoice->total_amount * 0.09;
                    $sgst = $invoice->total_amount * 0.09;
                    $total_with_tax = $invoice->total_amount + $cgst + $sgst;
                ?>
                <div class="summary-box">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span class="val">₹{{ number_format($invoice->total_amount, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span>CGST (9%)</span>
                        <span class="val">₹{{ number_format($cgst, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span>SGST (9%)</span>
                        <span class="val">₹{{ number_format($sgst, 2) }}</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Amount</span>
                        <span class="val">₹{{ number_format($total_with_tax, 2) }}</span>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <footer>
                <div class="notes">
                    <h4>Terms & Conditions</h4>
                    <p>
                        1. Payment is due within 30 days from the invoice date.<br>
                        2. Interest @ 24% will be charged if payment is not made within the due date.<br>
                        3. Goods once sold will not be taken back without proper authorization.
                    </p>
                </div>
                <div class="payment-info">
                    <h4>Payment Details</h4>
                    <p>
                        <strong>Bank:</strong> Axis Bank Ltd<br>
                        <strong>A/C No:</strong> 923030059651283<br>
                        <strong>IFSC:</strong> UTIB0002640
                    </p>
                </div>
            </footer>
            
            <div class="thank-you">
                Thank you for your business!
            </div>

        </div>
    </div>

</body>
</html>
