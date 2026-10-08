<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\VendorService;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    protected $vendorService;

    public function __construct(VendorService $vendorService)
    {
        $this->vendorService = $vendorService;
    }

    public function index()
    {
        $vendors = $this->vendorService->getAllVendors();
        return view('admin.vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('admin.vendors.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vendor_code'   => 'nullable|unique:vendors,vendor_code|max:255',
            'company_name'  => 'required|max:255',
            'contact_person'=> 'nullable|max:255',
            'mobile'        => 'required|max:20',
            'email'         => 'nullable|email|max:255',
            'gst_no'        => 'nullable|max:50',
            'lead_time_days'=> 'nullable|integer|min:0',
        ]);

        $data['status'] = $request->has('status');
        $data['lead_time_days'] = $data['lead_time_days'] ?? 0;

        $this->vendorService->createVendor($data);

        return redirect()->route('vendors.index')->with('success', 'Vendor created successfully.');
    }

    public function show($id)
    {
        $vendor = $this->vendorService->getVendorById($id);
        
        $purchases = $vendor->purchases()->with('items')->latest('po_date')->get();
        
        $rateHistory = [];
        foreach ($purchases as $purchase) {
            foreach ($purchase->items as $item) {
                $rateHistory[] = [
                    'po_number'    => $purchase->po_number,
                    'po_date'      => $purchase->po_date,
                    'product_name' => $item->product_name,
                    'quantity'     => $item->quantity,
                    'unit_price'   => $item->unit_price,
                    'total_price'  => $item->total_price,
                ];
            }
        }
        
        return view('admin.vendors.show', compact('vendor', 'purchases', 'rateHistory'));
    }

    public function edit($id)
    {
        $vendor = $this->vendorService->getVendorById($id);
        return view('admin.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'vendor_code'   => 'nullable|max:255|unique:vendors,vendor_code,' . $id,
            'company_name'  => 'required|max:255',
            'contact_person'=> 'nullable|max:255',
            'mobile'        => 'required|max:20',
            'email'         => 'nullable|email|max:255',
            'gst_no'        => 'nullable|max:50',
            'lead_time_days'=> 'nullable|integer|min:0',
        ]);

        $data['status'] = $request->has('status');
        $data['lead_time_days'] = $data['lead_time_days'] ?? 0;

        $vendor = $this->vendorService->updateVendor($id, $data);

        // Keep linked ledger in sync
        $ledger = \App\Models\Ledger::where('type', 'vendor')->where('reference_id', $id)->first();
        if ($ledger) {
            $ledger->update([
                'name' => $vendor->company_name,
                'is_active' => (bool)$data['status'],
            ]);
        }

        return redirect()->route('vendors.index')->with('success', 'Vendor updated successfully.');
    }

    public function destroy($id)
    {
        try {
            $result = $this->vendorService->deleteVendor($id);
            $vendor = $result['vendor'];

            if ($result['status'] === 'deleted') {
                return redirect()->route('vendors.index')->with(
                    'success',
                    "Vendor '{$vendor->company_name}' has been deleted successfully."
                );
            }

            if (!empty($result['already_inactive'])) {
                return redirect()->route('vendors.index')->with(
                    'warning',
                    "Vendor '{$vendor->company_name}' has linked purchase records or transaction history and cannot be deleted. It is already marked as Inactive."
                );
            }

            return redirect()->route('vendors.index')->with(
                'warning',
                "Vendor '{$vendor->company_name}' has linked purchase orders or transaction history and cannot be permanently deleted. It has been deactivated (marked as Inactive) instead."
            );

        } catch (\Illuminate\Database\QueryException $e) {
            // Foreign key fallback
            try {
                $vendor = $this->vendorService->getVendorById($id);
                if ($vendor) {
                    $vendor->update(['status' => false]);
                }
            } catch (\Exception $ex) {}

            return redirect()->route('vendors.index')->with(
                'warning',
                "Vendor has linked database records and cannot be permanently deleted. It has been marked as Inactive instead."
            );
        } catch (\Exception $e) {
            return redirect()->route('vendors.index')->with(
                'error',
                "Error deleting vendor: " . $e->getMessage()
            );
        }
    }

    public function toggleStatus($id)
    {
        $vendor = $this->vendorService->getVendorById($id);
        $newStatus = $vendor->status ? 0 : 1;
        $this->vendorService->updateVendor($id, ['status' => $newStatus]);

        $ledger = \App\Models\Ledger::where('type', 'vendor')->where('reference_id', $id)->first();
        if ($ledger) {
            $ledger->update(['is_active' => (bool)$newStatus]);
        }

        $message = $newStatus 
            ? "Vendor '{$vendor->company_name}' activated successfully." 
            : "Vendor '{$vendor->company_name}' deactivated successfully.";

        return redirect()->route('vendors.index')->with('success', $message);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // 10MB Max
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\VendorsImport, $request->file('file'));
            return redirect()->route('vendors.index')->with('success', 'Vendors imported successfully.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $messages = [];
            foreach ($failures as $failure) {
                $messages[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
            }
            return redirect()->route('vendors.index')->with('error', 'Validation Error: ' . implode('<br>', $messages));
        } catch (\Exception $e) {
            return redirect()->route('vendors.index')->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function downloadSample()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="vendors_sample.csv"',
        ];

        $columns = ['vendor_code', 'company_name', 'contact_person', 'mobile', 'email', 'gst_no', 'lead_time_days'];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            // Example row
            fputcsv($file, ['VEND-001', 'Example Vendor', 'Jane Smith', '9876543211', 'jane@vendor.com', '27ABCDE1234F1Z6', '7']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
