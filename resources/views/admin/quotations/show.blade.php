<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation</title>
    <style>
        body { font-family: "Arial", sans-serif; background-color: #222; color: #000; }
        .quotation-container { width: 1000px; margin: 20px auto; background: #fff; }
        table.quotation-table { width: 100%; border-collapse: collapse; border: 2px solid #000; }
        table.quotation-table th, table.quotation-table td { border: 1px solid #000; padding: 4px 8px; font-size: 13px; }
        
        /* Headers */
        .header-title { color: #b91d22; font-size: 20px; font-weight: bold; text-align: center; }
        .red-text { color: #b91d22; font-weight: bold; }
        
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        
        .no-border-bottom { border-bottom: none !important; }
        .no-border-top { border-top: none !important; }
    </style>
</head>
<body>
    <div class="quotation-container">
        <table class="quotation-table">
            <tr>
                <td colspan="9" class="header-title">QUOTATION</td>
            </tr>
            <tr>
                <td colspan="2" class="red-text fs-14">Demo ERP System :</td>
                <td colspan="6" class="text-center fw-bold fs-14">GF 25, EARTH ICON, NR. KHODIYAR NAGAR CHAR RASTA, NEW VIP ROAD, VADODARA-390019</td>
                <td colspan="1" class="red-text fs-14">DATE :08-06-2026</td>
            </tr>
            <tr>
                <td colspan="8"></td>
                <td colspan="1" class="red-text fs-14">QUOTE NO. :219</td>
            </tr>
            
            <!-- Table Headers -->
            <tr class="fw-bold text-center">
                <td width="5%">SR.NO.</td>
                <td width="25%">DISCRIPTION</td>
                <td width="15%">PART CODE</td>
                <td width="5%">QTY</td>
                <td width="10%">LP</td>
                <td width="10%">DISCOUNT</td>
                <td width="10%">NETRATE</td>
                <td width="10%">VALUE</td>
                <td width="10%">AVAILABILITY</td>
            </tr>
            
            <!-- Data Rows -->
            <tr class="text-center">
                <td>1</td>
                <td class="text-left">RCCB 40A, FP 30mA</td>
                <td>5SV43440RC</td>
                <td>10</td>
                <td>7420</td>
                <td>64.00%</td>
                <td>2671.20</td>
                <td>26712.00</td>
                <td>EX-STOCK</td>
            </tr>
            <tr class="text-center">
                <td>2</td>
                <td class="text-left">MCB 16A, DP 10kA</td>
                <td>5SL42167RC</td>
                <td>10</td>
                <td>1495</td>
                <td>65.00%</td>
                <td>523.25</td>
                <td>5232.50</td>
                <td>EX-STOCK</td>
            </tr>
            <tr class="text-center">
                <td>3</td>
                <td class="text-left">RCCB 25A, DP 30mA</td>
                <td>5SV43120RC</td>
                <td>10</td>
                <td>5225</td>
                <td>64.00%</td>
                <td>1881.00</td>
                <td>18810.00</td>
                <td>EX-STOCK</td>
            </tr>
            
            <!-- Footer rows -->
            <tr>
                <td colspan="3" class="text-right fw-bold">FREIGHT</td>
                <td colspan="4"></td>
                <td class="text-center fw-bold">200.00</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="3" class="text-right fw-bold">TOTAL</td>
                <td colspan="4"></td>
                <td class="text-center fw-bold">50954.50</td>
                <td></td>
            </tr>
            
            <!-- Notes -->
            <tr>
                <td colspan="9" class="text-left fw-bold">NOTE :<br>1 . GST EXTRA.</td>
            </tr>
        </table>
    </div>
</body>
</html>
