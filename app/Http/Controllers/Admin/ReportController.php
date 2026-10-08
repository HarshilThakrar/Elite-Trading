<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function monthlySales(Request $request)
    {
        $sales = \App\Models\Sale::whereNotIn('status', ['Draft', 'Cancelled'])
            ->with('items.product')
            ->orderBy('sale_date', 'desc')
            ->get();

        $monthlyDataMap = [];

        foreach ($sales as $sale) {
            $year = Carbon::parse($sale->sale_date)->year;
            $month = Carbon::parse($sale->sale_date)->month;
            $key = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

            if (!isset($monthlyDataMap[$key])) {
                $monthlyDataMap[$key] = [
                    'year' => $year,
                    'month' => $month,
                    'total_sales' => 0,
                    'total_orders' => 0,
                    'items' => []
                ];
            }

            $monthlyDataMap[$key]['total_sales'] += $sale->total_amount;
            $monthlyDataMap[$key]['total_orders'] += 1;

            foreach ($sale->items as $item) {
                $productName = $item->product ? ($item->product->description ?? $item->product->part_code) : 'Unknown Product';
                $productUnit = $item->product ? $item->product->unit : 'NOS';
                
                $itemKey = $productName . ' (' . $productUnit . ')';
                if (!isset($monthlyDataMap[$key]['items'][$itemKey])) {
                    $monthlyDataMap[$key]['items'][$itemKey] = 0;
                }
                
                $monthlyDataMap[$key]['items'][$itemKey] += $item->quantity;
            }
        }

        krsort($monthlyDataMap);

        $monthlyData = [];
        foreach ($monthlyDataMap as $key => $data) {
            $monthName = Carbon::createFromDate($data['year'], $data['month'], 1)->format('F Y');
            $monthlyData[] = [
                'month_name' => $monthName,
                'total_sales' => $data['total_sales'],
                'total_orders' => $data['total_orders'],
                'year' => $data['year'],
                'month' => $data['month'],
                'items' => $data['items']
            ];
        }

        return view('admin.reports.monthly-sales', compact('monthlyData'));
    }

    public function monthlyPurchases(Request $request)
    {
        $purchases = \App\Models\Purchase::whereNotIn('status', ['Draft', 'Cancelled'])
            ->with('items.product')
            ->orderBy('po_date', 'desc')
            ->get();

        $monthlyDataMap = [];

        foreach ($purchases as $purchase) {
            $year = Carbon::parse($purchase->po_date)->year;
            $month = Carbon::parse($purchase->po_date)->month;
            $key = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

            if (!isset($monthlyDataMap[$key])) {
                $monthlyDataMap[$key] = [
                    'year' => $year,
                    'month' => $month,
                    'total_purchases' => 0,
                    'total_orders' => 0,
                    'items' => []
                ];
            }

            $monthlyDataMap[$key]['total_purchases'] += $purchase->total_amount;
            $monthlyDataMap[$key]['total_orders'] += 1;

            foreach ($purchase->items as $item) {
                $productName = $item->product ? ($item->product->description ?? $item->product->part_code) : 'Unknown Product';
                $productUnit = $item->product ? $item->product->unit : 'NOS';
                
                $itemKey = $productName . ' (' . $productUnit . ')';
                if (!isset($monthlyDataMap[$key]['items'][$itemKey])) {
                    $monthlyDataMap[$key]['items'][$itemKey] = 0;
                }
                
                $monthlyDataMap[$key]['items'][$itemKey] += $item->quantity;
            }
        }

        krsort($monthlyDataMap);

        $monthlyData = [];
        foreach ($monthlyDataMap as $key => $data) {
            $monthName = Carbon::createFromDate($data['year'], $data['month'], 1)->format('F Y');
            $monthlyData[] = [
                'month_name' => $monthName,
                'total_purchases' => $data['total_purchases'],
                'total_orders' => $data['total_orders'],
                'year' => $data['year'],
                'month' => $data['month'],
                'items' => $data['items']
            ];
        }

        return view('admin.reports.monthly-purchases', compact('monthlyData'));
    }

    public function sales(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $sales = Sale::with(['customer', ])
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->orderBy('sale_date', 'desc')
            ->get();

        return view('admin.reports.sales', compact('sales', 'startDate', 'endDate'));
    }

    public function purchases(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $purchases = Purchase::with(['vendor', ])
            ->whereBetween('po_date', [$startDate, $endDate])
            ->orderBy('po_date', 'desc')
            ->get();

        return view('admin.reports.purchases', compact('purchases', 'startDate', 'endDate'));
    }

    public function smartReorder(Request $request)
    {
        $products = \App\Models\Product::where('status', true)->get();
        $reorderData = [];

        $ninetyDaysAgo = Carbon::now()->subDays(90);

        foreach ($products as $product) {
            // Get total OUT quantity in last 90 days
            $outQty = \App\Models\InventoryLedger::where('product_id', $product->id)
                ->where('type', 'OUT')
                ->where('created_at', '>=', $ninetyDaysAgo)
                ->sum('quantity');

            // Average Daily Consumption
            $adc = $outQty / 90;
            
            // Average Monthly Consumption (just for display, 30 days)
            $amc = $adc * 30;
            $ayc = $adc * 365; // Yearly consumption

            // Dynamic Reorder Level
            $dynamicReorderLevel = ceil($adc * $product->lead_time_days);
            $needsReorder = $product->available_stock < $dynamicReorderLevel;

            // Check if Ordered
            $isOrdered = \App\Models\PurchaseItem::where('product_id', $product->id)
                ->whereHas('purchase', function($query) {
                    $query->whereIn('status', ['Draft', 'Approved', 'Sent']);
                })->exists();

            if ($isOrdered) {
                $status = 'Ordered';
            } elseif ($needsReorder) {
                $status = 'Upcoming';
            } else {
                $status = 'Sufficient';
            }

            $reorderData[] = [
                'product' => $product,
                'adc' => round($adc, 2),
                'amc' => round($amc, 2),
                'ayc' => round($ayc, 2),
                'lead_time_days' => $product->lead_time_days,
                'dynamic_reorder_level' => $dynamicReorderLevel,
                'available_stock' => $product->available_stock,
                'needs_reorder' => $needsReorder,
                'status' => $status,
                'order_volume' => $outQty,
            ];
        }

        // Sort by order volume descending
        usort($reorderData, function ($a, $b) {
            return $b['order_volume'] <=> $a['order_volume'];
        });

        return view('admin.reports.smart-reorder', compact('reorderData'));
    }

    public function deadStock(Request $request)
    {
        $products = \App\Models\Product::where('status', true)->get();
        $deadStockData = [];
        $totalTiedUpCapital = 0;

        foreach ($products as $product) {
            $physicalStock = $product->available_stock + $product->reserved_stock;
            
            // Only care about items we actually have in stock
            if ($physicalStock > 0) {
                // Find last movement in ledger
                $lastMovement = \App\Models\InventoryLedger::where('product_id', $product->id)
                    ->latest('created_at')
                    ->first();

                // If no movement, use product creation date
                $lastMovementDate = $lastMovement ? $lastMovement->created_at : $product->created_at;
                
                $daysInactive = floor(Carbon::now()->diffInDays($lastMovementDate));

                if ($daysInactive >= 90) {
                    $tiedUpCapital = $physicalStock * $product->lp_price;
                    $totalTiedUpCapital += $tiedUpCapital;

                    // Find customers who previously purchased this product
                    $previousCustomers = \App\Models\SaleItem::where('product_id', $product->id)
                        ->with('sale.customer')
                        ->get()
                        ->pluck('sale.customer')
                        ->unique('id')
                        ->filter();

                    $deadStockData[] = [
                        'product' => $product,
                        'physical_stock' => $physicalStock,
                        'last_movement_date' => $lastMovementDate,
                        'days_inactive' => $daysInactive,
                        'tied_up_capital' => $tiedUpCapital,
                        'previous_customers' => $previousCustomers,
                    ];
                }
            }
        }

        // Sort by days inactive (highest first)
        usort($deadStockData, function ($a, $b) {
            return $b['days_inactive'] <=> $a['days_inactive'];
        });

        return view('admin.reports.dead-stock', compact('deadStockData', 'totalTiedUpCapital'));
    }

    public function abcAnalysis(Request $request)
    {
        $products = \App\Models\Product::where('status', true)->get();
        $abcData = [];

        $ninetyDaysAgo = Carbon::now()->subDays(90);

        foreach ($products as $product) {
            $outQty = \App\Models\InventoryLedger::where('product_id', $product->id)
                ->where('type', 'OUT')
                ->where('created_at', '>=', $ninetyDaysAgo)
                ->sum('quantity');

            $abcData[] = [
                'product' => $product,
                'out_qty' => $outQty,
                'revenue' => $outQty * $product->lp_price,
            ];
        }

        // Sort by volume descending
        usort($abcData, function ($a, $b) {
            return $b['out_qty'] <=> $a['out_qty'];
        });

        $totalProducts = count($abcData);
        $countA = 0;
        $countB = 0;
        $countC = 0;

        foreach ($abcData as $index => &$data) {
            $product = $data['product'];
            $percentile = ($index + 1) / max(1, $totalProducts);

            if ($percentile <= 0.20) {
                $category = 'A';
                $countA++;
            } elseif ($percentile <= 0.50) {
                $category = 'B';
                $countB++;
            } else {
                $category = 'C';
                $countC++;
            }

            $data['category'] = $category;

            if ($product->abc_category !== $category) {
                $product->abc_category = $category;
                $product->save();
            }
        }

        return view('admin.reports.abc-analysis', compact('abcData', 'countA', 'countB', 'countC'));
    }

    public function customerConsumption(Request $request)
    {
        $customers = \App\Models\Customer::orderBy('company_name')->get();
        $selectedCustomerId = $request->input('customer_id');
        $selectedCustomer = null;
        $matrix = [];
        $months = [];

        // Generate the last 12 months list
        for ($i = 11; $i >= 0; $i--) {
            $months[] = [
                'label' => Carbon::now()->subMonths($i)->format('M Y'),
                'start' => Carbon::now()->subMonths($i)->startOfMonth(),
                'end' => Carbon::now()->subMonths($i)->endOfMonth(),
                'key' => Carbon::now()->subMonths($i)->format('Y-m'),
            ];
        }

        if ($selectedCustomerId) {
            $selectedCustomer = \App\Models\Customer::find($selectedCustomerId);

            if ($selectedCustomer) {
                // Get all valid Sales for this customer in the last 12 months
                $startDate = $months[0]['start'];
                $endDate = $months[11]['end'];

                $sales = Sale::where('customer_id', $selectedCustomerId)
                    ->whereIn('status', ['Approved', 'Dispatched'])
                    ->whereBetween('sale_date', [$startDate, $endDate])
                    ->with('items.product')
                    ->get();

                foreach ($sales as $sale) {
                    $saleMonthKey = Carbon::parse($sale->sale_date)->format('Y-m');

                    foreach ($sale->items as $item) {
                        $productId = $item->product_id;
                        
                        if (!isset($matrix[$productId])) {
                            $matrix[$productId] = [
                                'product' => $item->product,
                                'months' => array_fill_keys(array_column($months, 'key'), 0),
                            ];
                        }

                        $matrix[$productId]['months'][$saleMonthKey] += $item->quantity;
                    }
                }

                // Calculate Trends
                $currentMonthKey = $months[11]['key'];
                $previousMonthKey = $months[10]['key'];

                foreach ($matrix as $productId => &$data) {
                    $currentQty = $data['months'][$currentMonthKey];
                    $prevQty = $data['months'][$previousMonthKey];

                    if ($prevQty > 0) {
                        $trend = (($currentQty - $prevQty) / $prevQty) * 100;
                    } elseif ($currentQty > 0) {
                        $trend = 100; // From 0 to something
                    } else {
                        $trend = 0; // 0 to 0
                    }

                    $data['trend'] = round($trend);
                }
            }
        }

        return view('admin.reports.customer-consumption', compact('customers', 'selectedCustomerId', 'selectedCustomer', 'months', 'matrix'));
    }

    public function orderFrequency(Request $request)
    {
        $customers = \App\Models\Customer::with(['sales' => function($query) {
            $query->whereIn('status', ['Approved', 'Dispatched'])
                  ->orderBy('sale_date', 'asc');
        }])->get();

        $predictionData = [];

        foreach ($customers as $customer) {
            $sales = $customer->sales;
            $totalOrders = $sales->count();

            if ($totalOrders >= 2) {
                $totalDaysGap = 0;
                $gapCount = 0;

                for ($i = 1; $i < $totalOrders; $i++) {
                    $prevDate = Carbon::parse($sales[$i - 1]->sale_date);
                    $currDate = Carbon::parse($sales[$i]->sale_date);
                    $daysDiff = $prevDate->diffInDays($currDate);
                    
                    if ($daysDiff > 0) {
                        $totalDaysGap += $daysDiff;
                        $gapCount++;
                    }
                }

                if ($gapCount > 0) {
                    $averageGap = floor($totalDaysGap / $gapCount);
                    $lastSaleDate = Carbon::parse($sales->last()->sale_date);
                    $nextExpectedDate = $lastSaleDate->copy()->addDays($averageGap);
                    
                    $today = Carbon::now()->startOfDay();
                    $daysUntilDue = $today->diffInDays($nextExpectedDate, false); // false for negative if passed
                    
                    if ($daysUntilDue < 0) {
                        $status = 'Overdue';
                        $statusClass = 'danger';
                    } elseif ($daysUntilDue <= 3) {
                        $status = 'Due Now';
                        $statusClass = 'warning';
                    } else {
                        $status = 'Upcoming';
                        $statusClass = 'success';
                    }

                    $predictionData[] = [
                        'customer' => $customer,
                        'total_orders' => $totalOrders,
                        'average_gap' => $averageGap,
                        'last_sale_date' => $lastSaleDate,
                        'next_expected_date' => $nextExpectedDate,
                        'days_until_due' => $daysUntilDue,
                        'status' => $status,
                        'status_class' => $statusClass
                    ];
                }
            }
        }

        // Sort by days_until_due ascending (most overdue first)
        usort($predictionData, function ($a, $b) {
            return $a['days_until_due'] <=> $b['days_until_due'];
        });

        return view('admin.reports.order-frequency', compact('predictionData'));
    }

    public function customerProfitability(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Admin']) && !auth()->user()->can('View Profit')) {
            abort(403, 'Unauthorized access.');
        }

        $customers = \App\Models\Customer::with(['sales' => function($query) {
            $query->whereIn('status', ['Approved', 'Dispatched'])->with('items.product');
        }])->get();

        $profitabilityData = [];

        // Pre-fetch average purchase rates for all products to avoid N+1 queries
        $averagePurchaseRates = [];
        $purchaseItems = \App\Models\PurchaseItem::whereHas('purchase', function($q) {
            $q->whereIn('status', ['Approved', 'Received']);
        })->get();
        
        $productPurchases = $purchaseItems->groupBy('product_id');
        foreach ($productPurchases as $productId => $items) {
            $averagePurchaseRates[$productId] = $items->avg('unit_price');
        }

        foreach ($customers as $customer) {
            $sales = $customer->sales;
            
            if ($sales->count() > 0) {
                $grossSalesValue = 0; // List price * qty
                $netRevenue = 0; // Actual sold price
                $totalCogs = 0; // Average purchase rate * qty

                foreach ($sales as $sale) {
                    $netRevenue += $sale->total_amount;

                    foreach ($sale->items as $item) {
                        $product = $item->product;
                        
                        // Calculate Gross Sales (List Price)
                        $listPrice = $product ? $product->lp_price : $item->unit_price;
                        $grossSalesValue += ($listPrice * $item->quantity);

                        // Calculate COGS
                        $avgCost = $averagePurchaseRates[$item->product_id] ?? 0;
                        $totalCogs += ($avgCost * $item->quantity);
                    }
                }

                // Discount is the difference between what it should have sold for (List Price) and what it actually sold for.
                // If netRevenue > grossSalesValue, it means they bought above list price, so discount is 0.
                $totalDiscount = max(0, $grossSalesValue - $netRevenue);
                
                $grossProfit = $netRevenue - $totalCogs;
                $marginPercent = $netRevenue > 0 ? ($grossProfit / $netRevenue) * 100 : 0;

                $profitabilityData[] = [
                    'customer' => $customer,
                    'total_orders' => $sales->count(),
                    'gross_sales' => $grossSalesValue,
                    'net_revenue' => $netRevenue,
                    'total_discount' => $totalDiscount,
                    'total_cogs' => $totalCogs,
                    'gross_profit' => $grossProfit,
                    'margin_percent' => $marginPercent,
                ];
            }
        }

        // Sort by Gross Profit descending (Most profitable first)
        usort($profitabilityData, function ($a, $b) {
            return $b['gross_profit'] <=> $a['gross_profit'];
        });

        return view('admin.reports.customer-profitability', compact('profitabilityData'));
    }
    public function vendorAnalysis(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Admin']) && !auth()->user()->can('View Profit')) {
            abort(403, 'Unauthorized access.');
        }

        $vendors = \App\Models\Vendor::with(['purchases' => function($query) {
            $query->whereIn('status', ['Approved', 'Received', 'Draft', 'Sent'])->with('items.product');
        }])->get();

        $analysisData = [];
        $totalOverallOrders = 0;
        $totalOverallMaterial = 0;
        $totalOverallAmount = 0;

        foreach ($vendors as $vendor) {
            $purchases = $vendor->purchases;
            $totalOrders = $purchases->count();
            
            if ($totalOrders > 0) {
                $totalMaterialQty = 0;
                $totalAmount = 0;

                foreach ($purchases as $purchase) {
                    $totalAmount += $purchase->total_amount;
                    foreach ($purchase->items as $item) {
                        $totalMaterialQty += $item->quantity;
                    }
                }

                $totalOverallOrders += $totalOrders;
                $totalOverallMaterial += $totalMaterialQty;
                $totalOverallAmount += $totalAmount;

                $analysisData[] = [
                    'vendor' => $vendor,
                    'total_orders' => $totalOrders,
                    'total_material_qty' => $totalMaterialQty,
                    'total_amount' => $totalAmount,
                ];
            }
        }

        usort($analysisData, function ($a, $b) {
            return $b['total_material_qty'] <=> $a['total_material_qty'];
        });

        return view('admin.reports.vendor-analysis', compact('analysisData', 'totalOverallOrders', 'totalOverallMaterial', 'totalOverallAmount'));
    }
    public function topCustomers(Request $request)
    {
        $period = $request->input('period', '365_days');
        $limit = $request->input('limit', 10);
        
        $startDate = null;
        $endDate = Carbon::now()->endOfDay();
        $periodLabel = 'Last 365 Days';
        
        switch ($period) {
            case 'today':
                $startDate = Carbon::today();
                $periodLabel = 'Today';
                break;
            case 'yesterday':
                $startDate = Carbon::yesterday();
                $endDate = Carbon::yesterday()->endOfDay();
                $periodLabel = 'Yesterday';
                break;
            case 'this_week':
                $startDate = Carbon::now()->startOfWeek();
                $periodLabel = 'This Week';
                break;
            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                $periodLabel = 'This Month';
                break;
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                $periodLabel = 'Last Month';
                break;
            case 'this_quarter':
                $startDate = Carbon::now()->startOfQuarter();
                $periodLabel = 'This Quarter';
                break;
            case 'this_year':
                $startDate = Carbon::now()->startOfYear();
                $periodLabel = 'This Year';
                break;
            case 'last_year':
                $startDate = Carbon::now()->subYear()->startOfYear();
                $endDate = Carbon::now()->subYear()->endOfYear();
                $periodLabel = 'Last Year';
                break;
            case 'all':
                $startDate = Carbon::create(2000, 1, 1);
                $periodLabel = 'All Time';
                break;
            default: // 365_days
                $startDate = Carbon::now()->subDays(365);
                $period = '365_days';
                $periodLabel = 'Last 365 Days';
                break;
        }
        
        $service = new \App\Services\CustomerAnalyticsService();
        $topCustomers = $service->getTopCustomers($startDate, $endDate, (int)$limit);

        return view('admin.reports.top-customers', compact('topCustomers', 'period', 'periodLabel', 'limit'));
    }
}
