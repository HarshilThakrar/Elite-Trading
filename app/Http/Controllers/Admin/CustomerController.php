<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index()
    {
        $customers = $this->customerService->getAllCustomers();
        return view('admin.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_code' => 'nullable|unique:customers,customer_code|max:255',
            'company_name'  => 'required|max:255|unique:customers,company_name',
            'contact_person'=> 'nullable|max:255',
            'mobile'        => 'required|max:20',
            'email'         => 'nullable|email|max:255',
            'gst_no'        => 'nullable|max:50',
            'address'       => 'nullable',
            'city'          => 'nullable|max:100',
            'state'         => 'nullable|max:100',
            'pincode'       => 'nullable|max:20',
            'credit_limit'  => 'nullable|numeric',
            'payment_terms' => 'nullable|max:255',
        ]);

        $data['status'] = $request->has('status');

        $this->customerService->createCustomer($data);

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function show($id)
    {
        $customer = $this->customerService->getCustomerById($id);
        $customer->load(['sales.items.product', 'quotations.items.product']);

        $totalRevenue = 0;
        $totalProfit = 0;
        $itemsBoughtMap = [];

        foreach ($customer->sales as $sale) {
            if ($sale->status !== 'Cancelled') {
                $totalRevenue += $sale->total_amount;
                foreach ($sale->items as $item) {
                    $product = $item->product;
                    $lp_price = $product ? $product->lp_price : 0;
                    $profit = ($item->unit_price - $lp_price) * $item->quantity;
                    $totalProfit += $profit;

                    if ($product) {
                        $productId = $product->id;
                        if (!isset($itemsBoughtMap[$productId])) {
                            $itemsBoughtMap[$productId] = [
                                'product' => $product,
                                'quantity' => 0,
                                'total_spent' => 0
                            ];
                        }
                        $itemsBoughtMap[$productId]['quantity'] += $item->quantity;
                        $itemsBoughtMap[$productId]['total_spent'] += $item->total_price;
                    }
                }
            }
        }

        $itemsBought = array_values($itemsBoughtMap);
        usort($itemsBought, function($a, $b) {
            return $b['quantity'] <=> $a['quantity'];
        });

        // Calculate Customer Rank
        $customerRank = \DB::table('customers')
            ->leftJoin('sales', function($join) {
                $join->on('customers.id', '=', 'sales.customer_id')
                     ->where('sales.status', '!=', 'Cancelled');
            })
            ->select('customers.id', \DB::raw('COALESCE(SUM(sales.total_amount), 0) as total_revenue'))
            ->groupBy('customers.id')
            ->orderByDesc('total_revenue')
            ->pluck('id')
            ->search($customer->id) + 1;

        // Get Top Item
        $topItemName = 'N/A';
        $topItemPartCode = 'N/A';
        if (count($itemsBought) > 0) {
            $topProduct = $itemsBought[0]['product'];
            $topItemName = $topProduct ? $topProduct->item_name : 'Unknown Product';
            $topItemPartCode = $topProduct ? $topProduct->part_code : 'N/A';
        }

        return view('admin.customers.show', compact('customer', 'totalRevenue', 'itemsBought', 'customerRank', 'topItemName', 'topItemPartCode'));
    }

    public function edit($id)
    {
        $customer = $this->customerService->getCustomerById($id);
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'customer_code' => 'required|max:255|unique:customers,customer_code,' . $id,
            'company_name'  => 'required|max:255|unique:customers,company_name,' . $id,
            'contact_person'=> 'nullable|max:255',
            'mobile'        => 'required|max:20',
            'email'         => 'nullable|email|max:255',
            'gst_no'        => 'nullable|max:50',
            'address'       => 'nullable',
            'city'          => 'nullable|max:100',
            'state'         => 'nullable|max:100',
            'pincode'       => 'nullable|max:20',
            'credit_limit'  => 'nullable|numeric',
            'payment_terms' => 'nullable|max:255',
        ]);

        $data['status'] = $request->has('status');

        $this->customerService->updateCustomer($id, $data);

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy($id)
    {
        try {
            $result = $this->customerService->deleteCustomer($id);
            $customer = $result['customer'];

            if (!$customer || $result['status'] === 'not_found') {
                return redirect()->route('customers.index')->with('error', 'Customer not found.');
            }

            if ($result['status'] === 'deleted') {
                return redirect()->route('customers.index')->with(
                    'success',
                    "Customer '{$customer->company_name}' has been deleted successfully."
                );
            }

            if (!empty($result['already_inactive'])) {
                return redirect()->route('customers.index')->with(
                    'warning',
                    "Customer '{$customer->company_name}' has linked sales or accounting records and cannot be permanently deleted. It is already marked as Inactive."
                );
            }

            return redirect()->route('customers.index')->with(
                'warning',
                "Customer '{$customer->company_name}' has linked sales orders or transaction history and cannot be permanently deleted. It has been deactivated (marked as Inactive) instead."
            );

        } catch (\Illuminate\Database\QueryException $e) {
            // Foreign key fallback
            try {
                $customer = $this->customerService->getCustomerById($id);
                if ($customer) {
                    $customer->update(['status' => false]);
                }
            } catch (\Exception $ex) {}

            return redirect()->route('customers.index')->with(
                'warning',
                "Customer has linked database records and could not be permanently deleted. It has been deactivated instead."
            );
        } catch (\Exception $e) {
            return redirect()->route('customers.index')->with('error', 'Error deleting customer: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        $customer = $this->customerService->getCustomerById($id);
        $newStatus = $customer->status ? 0 : 1;
        $this->customerService->updateCustomer($id, ['status' => $newStatus]);
        
        $message = $newStatus ? 'Customer activated successfully.' : 'Customer deactivated successfully.';
        return redirect()->route('customers.index')->with('success', $message);
    }

    public function checkName(Request $request)
    {
        $query = \App\Models\Customer::where('company_name', $request->company_name);
        
        if ($request->has('exclude_id') && $request->exclude_id) {
            $query->where('id', '!=', $request->exclude_id);
        }
        
        return response()->json(['exists' => $query->exists()]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // 10MB Max
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\CustomersImport, $request->file('file'));
            return redirect()->route('customers.index')->with('success', 'Customers imported successfully.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $messages = [];
            foreach ($failures as $failure) {
                $messages[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
            }
            return redirect()->route('customers.index')->with('error', 'Validation Error: ' . implode('<br>', $messages));
        } catch (\Exception $e) {
            return redirect()->route('customers.index')->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function downloadSample()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customers_sample.csv"',
        ];

        $columns = ['customer_code', 'company_name', 'contact_person', 'mobile', 'email', 'gst_no', 'address', 'city', 'state', 'pincode', 'credit_limit', 'payment_terms'];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            // Example row
            fputcsv($file, ['CUST-001', 'Example Company', 'John Doe', '9876543210', 'john@example.com', '27ABCDE1234F1Z5', '123 Business St', 'Mumbai', 'Maharashtra', '400001', '50000', 'Net 30']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
