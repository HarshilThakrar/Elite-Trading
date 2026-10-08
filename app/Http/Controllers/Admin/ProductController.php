<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Request;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(
        ProductService $productService
    ) {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        $groupId = $request->input('group_id');
        $subgroupId = $request->input('subgroup_id');
        $search = $request->input('search');
        
        $query = \App\Models\Product::query();
        
        if ($groupId) {
            $query->where('product_group_id', $groupId);
        }
        
        if ($subgroupId) {
            $query->where('product_subgroup_id', $subgroupId);
        }
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('part_code', 'like', "%{$search}%");
            });
        }
        
        if ($groupId || $subgroupId || $search) {
            $products = $query->get();
        } else {
            $products = $this->productService->getAllProducts();
        }
        
        $groups = \App\Models\ProductGroup::all();
        $subgroups = $groupId ? \App\Models\ProductSubgroup::where('product_group_id', $groupId)->get() : [];
        
        return view('admin.products.index', compact('products', 'groups', 'subgroups', 'groupId', 'subgroupId', 'search'));
    }

    public function create()
    {
        $groups = \App\Models\ProductGroup::all();
        return view('admin.products.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'part_code'    => 'required|unique:products,part_code|max:255',
            'product_group_id' => 'required|exists:product_groups,id',
            'product_subgroup_id' => 'nullable|exists:product_subgroups,id',
            'item_name'    => 'required|max:255',
            'description'  => 'nullable',
            'unit'         => 'required|max:50',
            'lp_price'     => 'required|numeric|min:0',
            'minimum_stock'=> 'required|integer|min:0',
            'reorder_level'=> 'required|integer|min:0',
            'maximum_stock'=> 'required|integer|min:0',
            'available_stock'=> 'nullable|integer|min:0',
        ]);

        $data['status'] = $request->has('status');

        $this->productService->createProduct($data);

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function show($id)
    {
        $product = \App\Models\Product::with(['group', 'subgroup', 'lpHistories.user'])->findOrFail($id);
        
        // Fetch last 10 stock movements for the product
        $stockMovements = \App\Models\InventoryLedger::with('reference')
            ->where('product_id', $id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('admin.products.show', compact('product', 'stockMovements'));
    }

    public function edit($id)
    {
        $product = $this->productService->getProductById($id);
        $groups = \App\Models\ProductGroup::all();
        $subgroups = $product->product_group_id ? \App\Models\ProductSubgroup::where('product_group_id', $product->product_group_id)->get() : [];
        
        return view('admin.products.edit', compact('product', 'groups', 'subgroups'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'part_code'    => 'required|max:255|unique:products,part_code,' . $id,
            'product_group_id' => 'required|exists:product_groups,id',
            'product_subgroup_id' => 'nullable|exists:product_subgroups,id',
            'item_name'    => 'required|max:255',
            'description'  => 'nullable',
            'unit'         => 'required|max:50',
            'lp_price'     => 'required|numeric|min:0',
            'minimum_stock'=> 'required|integer|min:0',
            'reorder_level'=> 'required|integer|min:0',
            'maximum_stock'=> 'required|integer|min:0',
            'available_stock'=> 'nullable|integer|min:0',
        ]);

        $data['status'] = $request->has('status');

        $oldProduct = $this->productService->getProductById($id);
        
        $this->productService->updateProduct($id, $data);

        // Record LP History if changed
        if ($oldProduct && $oldProduct->lp_price != $data['lp_price']) {
            \App\Models\LpHistory::create([
                'product_id' => $id,
                'old_price' => $oldProduct->lp_price,
                'new_price' => $data['lp_price'],
                'user_id' => auth()->id()
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy($id)
    {
        // $this->productService->deleteProduct($id);
        $this->productService->updateProduct($id, ['status' => 0]);
        return redirect()->route('products.index')->with('success', 'Product deactivated successfully.');
    }

    public function truncate()
    {
        try {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            \App\Models\Product::truncate();
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return redirect()->route('products.index')->with('success', 'All products deleted successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return redirect()->route('products.index')->with('error', 'Error deleting products: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        $product = $this->productService->getProductById($id);
        $newStatus = $product->status ? 0 : 1;
        $this->productService->updateProduct($id, ['status' => $newStatus]);
        
        $message = $newStatus ? 'Product activated successfully.' : 'Product deactivated successfully.';
        return redirect()->route('products.index')->with('success', $message);
    }

    public function getAveragePurchaseRate($id)
    {
        $averageRate = \App\Models\PurchaseItem::where('product_id', $id)
            ->whereHas('purchase', function($query) {
                $query->whereIn('status', ['Approved', 'Received']);
            })
            ->avg('unit_price');

        return response()->json([
            'average_purchase_rate' => $averageRate ? round((float)$averageRate, 2) : 0
        ]);
    }
    public function getPurchaseHistory($id)
    {
        $history = \App\Models\PurchaseItem::with(['purchase.vendor'])
            ->where('product_id', $id)
            ->whereHas('purchase', function($query) {
                $query->whereIn('status', ['Approved', 'Received']);
            })
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->orderBy('purchases.po_date', 'desc')
            ->select('purchase_items.*')
            ->take(10)
            ->get()
            ->map(function($item) {
                return [
                    'po_number' => $item->purchase->po_number ?? 'N/A',
                    'po_date' => $item->purchase->po_date ? \Carbon\Carbon::parse($item->purchase->po_date)->format('Y-m-d') : 'N/A',
                    'vendor_name' => $item->purchase->vendor->company_name ?? $item->purchase->vendor->vendor_name ?? 'N/A',
                    'quantity' => $item->quantity,
                    'net_rate' => $item->unit_price
                ];
            });

        return response()->json($history);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // 10MB Max
        ]);

        try {
            Excel::import(new ProductsImport, $request->file('file'));
            return redirect()->route('products.index')->with('success', 'Products imported successfully.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $messages = [];
            foreach ($failures as $failure) {
                $messages[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
            }
            return redirect()->route('products.index')->with('error', 'Validation Error: ' . implode('<br>', $messages));
        } catch (\Exception $e) {
            return redirect()->route('products.index')->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function downloadSample()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="products_sample.csv"',
        ];

        $columns = ['part_code', 'item_name', 'group', 'subgroup', 'unit', 'lp_price', 'gst_rate', 'hsn_code', 'minimum_stock', 'description'];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            // Example row
            fputcsv($file, ['PC-001', 'Example Product', 'Hardware', 'Fasteners', 'Nos', '100', '18', '7318', '50', 'Example product description']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
