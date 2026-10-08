<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadRemark;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    protected array $sources = [
        'Website',
        'Referral',
        'Cold Call',
        'Social Media',
        'Exhibition',
        'Direct / Walk-in',
        'Google Ads',
        'Email Campaign',
        'Other',
    ];

    protected array $statuses = [
        'New',
        'Contacted',
        'Qualified',
        'Proposal Sent',
        'Negotiation',
        'Won',
        'Lost',
    ];

    protected array $priorities = [
        'Low',
        'Medium',
        'High',
        'Urgent',
    ];

    public function index(Request $request)
    {
        $query = Lead::with(['assignedUser', 'latestRemark.user'])->latest();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('lead_code', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $leads = $query->paginate(15)->withQueryString();

        // Metrics Summary
        $stats = [
            'total' => Lead::count(),
            'new' => Lead::where('status', 'New')->count(),
            'in_progress' => Lead::whereIn('status', ['Contacted', 'Qualified', 'Proposal Sent', 'Negotiation'])->count(),
            'won' => Lead::where('status', 'Won')->count(),
            'lost' => Lead::where('status', 'Lost')->count(),
            'total_value' => Lead::sum('estimated_value'),
        ];

        $users = User::orderBy('name')->get();
        $statuses = $this->statuses;
        $priorities = $this->priorities;
        $sources = $this->sources;

        return view('admin.leads.index', compact('leads', 'stats', 'users', 'statuses', 'priorities', 'sources'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        $statuses = $this->statuses;
        $priorities = $this->priorities;
        $sources = $this->sources;

        return view('admin.leads.create', compact('users', 'statuses', 'priorities', 'sources'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'source' => 'required|string|max:100',
            'status' => 'required|string|max:100',
            'priority' => 'required|string|max:100',
            'estimated_value' => 'nullable|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'expected_close_date' => 'nullable|date',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'requirement_details' => 'nullable|string',
            'initial_remark' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate Lead Code: LEAD-YYYY-0001 (guaranteed unique)
            $year = date('Y');
            $maxNumber = Lead::withTrashed()
                ->where('lead_code', 'like', "LEAD-{$year}-%")
                ->pluck('lead_code')
                ->map(function ($code) use ($year) {
                    return preg_match('/LEAD-' . $year . '-(\d+)/', $code, $m) ? (int)$m[1] : 0;
                })
                ->max() ?? 0;

            $nextNumber = $maxNumber + 1;
            do {
                $leadCode = sprintf('LEAD-%s-%04d', $year, $nextNumber);
                $exists = Lead::withTrashed()->where('lead_code', $leadCode)->exists();
                if ($exists) {
                    $nextNumber++;
                }
            } while ($exists);

            $validated['lead_code'] = $leadCode;
            $validated['created_by'] = auth()->id();
            $validated['last_contacted_at'] = now();
            $validated['estimated_value'] = $validated['estimated_value'] ?? 0;

            $lead = Lead::create($validated);

            // Create initial Remark & Notification
            $remarkText = $request->filled('initial_remark')
                ? $request->initial_remark
                : "Lead {$lead->lead_code} created with status '{$lead->status}'.";

            LeadRemark::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'remark' => $remarkText,
                'action_type' => 'created',
                'new_status' => $lead->status,
                'is_notification' => true,
            ]);

            DB::commit();

            return redirect()->route('leads.show', $lead->id)
                ->with('success', "Lead {$lead->lead_code} created successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error creating lead: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $lead = Lead::with(['remarks.user', 'assignedUser', 'creator', 'customer'])->findOrFail($id);
        $users = User::orderBy('name')->get();
        $statuses = $this->statuses;
        $priorities = $this->priorities;

        return view('admin.leads.show', compact('lead', 'users', 'statuses', 'priorities'));
    }

    public function edit($id)
    {
        $lead = Lead::findOrFail($id);
        $users = User::orderBy('name')->get();
        $statuses = $this->statuses;
        $priorities = $this->priorities;
        $sources = $this->sources;

        return view('admin.leads.edit', compact('lead', 'users', 'statuses', 'priorities', 'sources'));
    }

    public function update(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'source' => 'required|string|max:100',
            'status' => 'required|string|max:100',
            'priority' => 'required|string|max:100',
            'estimated_value' => 'nullable|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'expected_close_date' => 'nullable|date',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'requirement_details' => 'nullable|string',
            'update_remark' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $lead->status;
            $newStatus = $validated['status'];
            $oldPriority = $lead->priority;
            $newPriority = $validated['priority'];
            $statusChanged = ($oldStatus !== $newStatus);
            $priorityChanged = ($oldPriority !== $newPriority);

            $validated['estimated_value'] = $validated['estimated_value'] ?? 0;
            $validated['last_contacted_at'] = now();

            $lead->update($validated);

            // Log Remark / Notification if status changed, priority changed, or remark provided
            $remarkText = $request->input('update_remark');
            $actionType = 'updated';

            if ($statusChanged) {
                $actionType = 'status_changed';
                $statusNote = "Status changed from '{$oldStatus}' to '{$newStatus}'";
                $remarkText = $remarkText ? "{$remarkText} ({$statusNote})" : $statusNote;
            } elseif ($priorityChanged && !$remarkText) {
                $remarkText = "Priority changed from '{$oldPriority}' to '{$newPriority}'";
            } elseif (!$remarkText) {
                $remarkText = "Lead details updated by " . (auth()->user()->name ?? 'Admin');
            }

            LeadRemark::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'remark' => $remarkText,
                'action_type' => $actionType,
                'old_status' => $statusChanged ? $oldStatus : null,
                'new_status' => $statusChanged ? $newStatus : null,
                'is_notification' => true,
            ]);

            DB::commit();

            return redirect()->route('leads.show', $lead->id)
                ->with('success', "Lead {$lead->lead_code} updated successfully! Remark logged in notification history.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error updating lead: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $lead = Lead::findOrFail($id);
        $leadCode = $lead->lead_code;
        $lead->delete();

        return redirect()->route('leads.index')
            ->with('success', "Lead {$leadCode} removed successfully.");
    }

    public function addRemark(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);

        $request->validate([
            'remark' => 'required|string|max:2000',
            'status' => 'nullable|string|in:' . implode(',', $this->statuses),
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $lead->status;
            $newStatus = $request->input('status', $oldStatus);
            $statusChanged = ($newStatus && $newStatus !== $oldStatus);

            if ($statusChanged) {
                $lead->status = $newStatus;
            }
            $lead->last_contacted_at = now();
            $lead->save();

            $remarkContent = $request->remark;
            if ($statusChanged) {
                $remarkContent .= " [Status updated: {$oldStatus} → {$newStatus}]";
            }

            LeadRemark::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'remark' => $remarkContent,
                'action_type' => $statusChanged ? 'status_changed' : 'remark',
                'old_status' => $statusChanged ? $oldStatus : null,
                'new_status' => $statusChanged ? $newStatus : null,
                'is_notification' => true,
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Remark added and notification logged successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to add remark: ' . $e->getMessage());
        }
    }

    public function convertCustomer(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);

        if ($lead->customer_id) {
            return redirect()->back()->with('info', 'This lead is already converted to a customer.');
        }

        DB::beginTransaction();
        try {
            // Find or create customer
            $companyName = $lead->company_name ?: $lead->name;
            $customer = Customer::where('company_name', $companyName)->first();

            if (!$customer) {
                $custCode = 'CUST-' . strtoupper(uniqid());
                $customer = Customer::create([
                    'customer_code' => $custCode,
                    'company_name' => $companyName,
                    'contact_person' => $lead->name,
                    'mobile' => $lead->phone,
                    'email' => $lead->email,
                    'city' => $lead->city,
                    'state' => $lead->state,
                    'status' => 1,
                ]);

                // Create Ledger for Customer if Sundry Debtors group exists
                $debtorsGroup = \App\Models\AccountGroup::where('name', 'Sundry Debtors')->first();
                if ($debtorsGroup) {
                    \App\Models\Ledger::create([
                        'name' => $customer->company_name,
                        'account_group_id' => $debtorsGroup->id,
                        'opening_balance' => 0,
                        'opening_balance_type' => 'Dr',
                        'is_system' => false,
                        'type' => 'customer',
                        'reference_id' => $customer->id,
                    ]);
                }
            }

            $lead->customer_id = $customer->id;
            $lead->status = 'Won';
            $lead->save();

            LeadRemark::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'remark' => "Lead converted into Customer: '{$customer->company_name}'. Status updated to Won.",
                'action_type' => 'status_changed',
                'old_status' => $lead->getOriginal('status'),
                'new_status' => 'Won',
                'is_notification' => true,
            ]);

            DB::commit();

            return redirect()->route('customers.show', $customer->id)
                ->with('success', "Lead {$lead->lead_code} successfully converted into Customer '{$customer->company_name}'!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error converting lead: ' . $e->getMessage());
        }
    }
}
