<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountingSetting;
use App\Services\AccountingSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class AccountingSettingsController extends Controller
{
    protected $service;

    public function __construct(AccountingSettingsService $service)
    {
        $this->service = $service;
        // Assume user permissions exist as requested
        // $this->middleware('permission:manage accounting settings');
    }

    public function index()
    {
        $settings = $this->service->getSettings();
        return view('admin.accounting-settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $settings = $this->service->getSettings();
        
        $request->validate([
            'base_currency' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:10',
            'amount_decimals' => 'required|integer|min:0|max:4',
            'qty_decimals' => 'required|integer|min:0|max:4',
            'rate_decimals' => 'required|integer|min:0|max:4',
            'rounding_method' => 'required|in:Standard,Up,Down',
            'max_backdate_days' => 'required|integer|min:0',
            'max_future_days' => 'required|integer|min:0',
            'voucher_numbering' => 'array'
        ]);

        try {
            DB::transaction(function () use ($request, $settings) {
                // Log all changes
                $changes = [];
                $attributes = $request->except(['_token', '_method']);
                
                // Construct voucher numbering array
                if ($request->has('voucher_numbering')) {
                    $numbering = [];
                    foreach ($request->voucher_numbering as $type => $config) {
                        $numbering[$type] = [
                            'prefix' => $config['prefix'] ?? '',
                            'start' => (int)($config['start'] ?? 1),
                            'padding' => (int)($config['padding'] ?? 4),
                            'fy_wise' => isset($config['fy_wise']),
                            'mode' => 'automatic' // Hardcoded to automatic for now
                        ];
                    }
                    $attributes['voucher_numbering'] = $numbering;
                }

                $attributes['mandatory_narration'] = $request->has('mandatory_narration');
                $attributes['allow_backdated'] = $request->has('allow_backdated');
                $attributes['allow_future_dated'] = $request->has('allow_future_dated');
                $attributes['allow_voucher_editing'] = $request->has('allow_voucher_editing');
                $attributes['allow_voucher_cancellation'] = $request->has('allow_voucher_cancellation');
                $attributes['prevent_inactive_ledger_posting'] = $request->has('prevent_inactive_ledger_posting');

                foreach ($attributes as $key => $value) {
                    if ($settings->$key != $value) {
                        $oldValue = is_array($settings->$key) ? json_encode($settings->$key) : $settings->$key;
                        $newValue = is_array($value) ? json_encode($value) : $value;
                        activity()->log("Updated Accounting Setting [{$key}]: '{$oldValue}' -> '{$newValue}'");
                    }
                }

                $settings->update($attributes);
            });

            // Clear cache
            $this->service->clearCache();

            return redirect()->route('accounting-settings.index')->with('success', 'Accounting Settings updated successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
