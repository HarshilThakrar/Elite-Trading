@extends('layouts.app')

@section('title', 'Lead: ' . $lead->name . ' - Demo ERP')
@section('header_title', 'Lead Overview')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-1 shadow-sm px-3" style="border-radius: 8px;">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Leads</span>
            </a>
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h3 class="mb-0 text-dark fw-bold">{{ $lead->name }}</h3>
                    <span class="badge font-monospace" style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 12px; padding: 3px 8px;">
                        {{ $lead->lead_code }}
                    </span>
                    @php
                        $statusStyle = match($lead->status) {
                            'New' => 'background-color: #eff6ff; color: #1d4ed8; border: 1px solid rgba(29, 78, 216, 0.25);',
                            'Contacted' => 'background-color: #f5f3ff; color: #6d28d9; border: 1px solid rgba(109, 40, 217, 0.25);',
                            'Qualified' => 'background-color: #ecfeff; color: #0e7490; border: 1px solid rgba(14, 116, 144, 0.25);',
                            'Proposal Sent' => 'background-color: #fffbeb; color: #b45309; border: 1px solid rgba(180, 83, 9, 0.25);',
                            'Negotiation' => 'background-color: #eef2ff; color: #4338ca; border: 1px solid rgba(67, 56, 202, 0.25);',
                            'Won' => 'background-color: #ecfdf5; color: #047857; border: 1px solid rgba(4, 120, 87, 0.25);',
                            'Lost' => 'background-color: #fef2f2; color: #b91c1c; border: 1px solid rgba(185, 28, 28, 0.25);',
                            default => 'background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb;'
                        };
                    @endphp
                    <span class="d-inline-flex align-items-center px-3 py-1 rounded-pill fw-semibold" style="font-size: 12px; {{ $statusStyle }}">
                        <span class="rounded-circle me-1" style="width: 6px; height: 6px; background-color: currentColor;"></span>
                        {{ $lead->status }}
                    </span>
                </div>
                @if($lead->company_name)
                    <div class="text-muted small mt-1">
                        <i class="bi bi-building me-1"></i>{{ $lead->company_name }}
                    </div>
                @endif
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if(!$lead->customer_id && $lead->status !== 'Lost')
                <form action="{{ route('leads.convert', $lead->id) }}" method="POST" onsubmit="return confirm('Convert this lead into an official Customer?');">
                    @csrf
                    <button type="submit" class="btn btn-success shadow-sm fw-semibold d-inline-flex align-items-center gap-1" style="border-radius: 8px;">
                        <i class="bi bi-person-check-fill"></i>
                        <span>Convert to Customer</span>
                    </button>
                </form>
            @elseif($lead->customer)
                <a href="{{ route('customers.show', $lead->customer->id) }}" class="btn btn-outline-success fw-semibold d-inline-flex align-items-center gap-1" style="border-radius: 8px;">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span>View Customer Profile</span>
                </a>
            @endif

            <a href="{{ route('leads.edit', $lead->id) }}" class="btn btn-outline-primary fw-semibold d-inline-flex align-items-center gap-1 shadow-sm" style="border-radius: 8px;">
                <i class="bi bi-pencil-square"></i>
                <span>Edit Lead</span>
            </a>

            <form action="{{ route('leads.destroy', $lead->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this lead?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger shadow-sm d-inline-flex align-items-center justify-content-center" title="Delete Lead" style="width: 38px; height: 38px; border-radius: 8px;">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-left: 4px solid #10b981 !important;">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
                <div class="fw-medium">{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-left: 4px solid #ef4444 !important;">
            <div class="d-flex align-items-center">
                <i class="bi bi-x-circle-fill fs-5 me-2 text-danger"></i>
                <div class="fw-medium">{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Pipeline Stage Progress Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4" style="border: 1px solid #eef2f6 !important;">
        <div class="card-body p-4">
            <div class="small fw-bold text-muted mb-3 text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Pipeline Stage Progression</div>
            @php
                $stages = ['New', 'Contacted', 'Qualified', 'Proposal Sent', 'Negotiation', 'Won'];
                $currentIndex = array_search($lead->status, $stages);
                if ($currentIndex === false && $lead->status === 'Lost') {
                    $currentIndex = -1;
                }
            @endphp
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                @foreach($stages as $index => $stage)
                    @php
                        $isCompleted = ($currentIndex !== false && $currentIndex >= $index && $currentIndex !== -1);
                        $isCurrent = ($lead->status === $stage);
                    @endphp
                    <div class="d-flex align-items-center flex-grow-1">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold shadow-sm"
                                  style="width: 32px; height: 32px; font-size: 13px; background-color: {{ $isCurrent ? '#2563eb' : ($isCompleted ? '#10b981' : '#f1f5f9') }}; color: {{ ($isCompleted || $isCurrent) ? '#ffffff' : '#64748b' }} !important;">
                                @if($isCompleted && !$isCurrent)
                                    <i class="bi bi-check-lg fw-bold"></i>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </span>
                            <span class="small fw-bold {{ $isCurrent ? 'text-primary' : ($isCompleted ? 'text-success' : 'text-muted') }}" style="font-size: 13px;">
                                {{ $stage }}
                            </span>
                        </div>
                        @if(!$loop->last)
                            <div class="flex-grow-1 mx-2" style="height: 3px; background-color: {{ $isCompleted && $currentIndex > $index ? '#10b981' : '#e2e8f0' }}; border-radius: 2px;"></div>
                        @endif
                    </div>
                @endforeach
                @if($lead->status === 'Lost')
                    <div class="ms-2">
                        <span class="badge bg-danger px-3 py-2"><i class="bi bi-x-circle-fill me-1"></i> Marked as Lost</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Lead Information -->
        <div class="col-lg-5">
            <!-- Contact & Company Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4" style="border: 1px solid #eef2f6 !important;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-person-lines-fill text-primary"></i>
                        <span>Contact & Organization</span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-borderless table-striped mb-0" style="font-size: 13.5px;">
                        <tbody>
                            <tr>
                                <td class="text-muted w-40 ps-4 py-2">Contact Person</td>
                                <td class="fw-bold py-2 text-dark">{{ $lead->name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-4 py-2">Company</td>
                                <td class="fw-semibold py-2 text-dark">{{ $lead->company_name ?: '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-4 py-2">Phone / Mobile</td>
                                <td class="py-2">
                                    <a href="tel:{{ $lead->phone }}" class="text-decoration-none fw-bold text-primary">
                                        <i class="bi bi-telephone-fill me-1" style="font-size: 11px;"></i> {{ $lead->phone }}
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-4 py-2">Email Address</td>
                                <td class="py-2">
                                    @if($lead->email)
                                        <a href="mailto:{{ $lead->email }}" class="text-decoration-none text-info">
                                            <i class="bi bi-envelope-fill me-1" style="font-size: 11px;"></i> {{ $lead->email }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-4 py-2">Location</td>
                                <td class="py-2">
                                    {{ $lead->city ? $lead->city . ', ' : '' }}{{ $lead->state ?: '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pipeline & Valuation Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4" style="border: 1px solid #eef2f6 !important;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-graph-up-arrow text-primary"></i>
                        <span>Pipeline & Deal Details</span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-borderless table-striped mb-0" style="font-size: 13.5px;">
                        <tbody>
                            <tr>
                                <td class="text-muted w-40 ps-4 py-2">Estimated Value</td>
                                <td class="fw-bold fs-5 text-success py-2">₹{{ number_format($lead->estimated_value, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-4 py-2">Lead Source</td>
                                <td class="fw-semibold py-2">{{ $lead->source }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-4 py-2">Priority</td>
                                <td class="py-2">
                                    @php
                                        $priorityStyle = match($lead->priority) {
                                            'Urgent' => 'background-color: #fef2f2; color: #b91c1c; border: 1px solid rgba(220, 38, 38, 0.3);',
                                            'High' => 'background-color: #fff7ed; color: #c2410c; border: 1px solid rgba(234, 88, 12, 0.3);',
                                            'Medium' => 'background-color: #eff6ff; color: #1d4ed8; border: 1px solid rgba(37, 99, 235, 0.3);',
                                            'Low' => 'background-color: #f9fafb; color: #4b5563; border: 1px solid rgba(107, 114, 128, 0.3);',
                                            default => 'background-color: #f3f4f6; color: #374151;'
                                        };
                                    @endphp
                                    <span class="d-inline-flex align-items-center px-2 py-1 rounded fw-bold text-uppercase" style="font-size: 11px; {{ $priorityStyle }}">
                                        {{ $lead->priority }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-4 py-2">Assigned Salesperson</td>
                                <td class="py-2 fw-semibold">
                                    @if($lead->assignedUser)
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="bi bi-person-circle text-primary"></i>
                                            <span>{{ $lead->assignedUser->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-muted fst-italic">Unassigned</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-4 py-2">Target Close Date</td>
                                <td class="py-2">
                                    {{ $lead->expected_close_date ? $lead->expected_close_date->format('d M Y') : '—' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-4 py-2">Created By</td>
                                <td class="py-2">{{ $lead->creator->name ?? 'System' }} on {{ $lead->created_at->format('d M Y, h:i A') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-4 py-2">Last Activity</td>
                                <td class="py-2">{{ $lead->last_contacted_at ? $lead->last_contacted_at->diffForHumans() : '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Requirement Details -->
            @if($lead->requirement_details)
                <div class="card border-0 shadow-sm rounded-3 mb-4" style="border: 1px solid #eef2f6 !important;">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-file-text-fill text-primary"></i>
                            <span>Requirement Details</span>
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <p class="text-dark mb-0 whitespace-pre-line" style="font-size: 13.5px; line-height: 1.6;">{{ $lead->requirement_details }}</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column: Remarks & Notification Timeline -->
        <div class="col-lg-7">
            <!-- Add New Remark Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 border-top border-primary border-4" style="border: 1px solid #eef2f6 !important; border-top: 4px solid #2563eb !important;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-chat-left-dots-fill text-primary"></i>
                        <span>Add Remark & Activity Notification</span>
                    </h6>
                    <small class="text-muted">Post client notes, phone call updates, or change pipeline status</small>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('leads.remarks.store', $lead->id) }}" method="POST">
                        @csrf
                        <div class="row g-2 mb-3">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold text-muted">Update Pipeline Status (Optional)</label>
                                <select name="status" class="form-select" style="font-size: 13.5px;">
                                    <option value="">Keep current status ({{ $lead->status }})</option>
                                    @foreach($statuses as $st)
                                        <option value="{{ $st }}" {{ $lead->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Remark / Follow-up Note <span class="text-danger">*</span></label>
                            <textarea name="remark" class="form-control" rows="3" placeholder="Enter follow-up details, client discussion, proposal shared, next actions..." required style="font-size: 13.5px;"></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm d-inline-flex align-items-center gap-2" style="background-color: #2563eb !important; border-color: #2563eb !important; color: #fff !important; border-radius: 8px;">
                                <i class="bi bi-send-fill"></i>
                                <span>Post Remark & Notify</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Remarks & Activity Timeline -->
            <div class="card border-0 shadow-sm rounded-3" style="border: 1px solid #eef2f6 !important;">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-primary"></i>
                        <span>Remarks & Activity Log</span>
                    </h6>
                    <span class="badge font-monospace" style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 11px;">
                        {{ $lead->remarks->count() }} activities
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="position-relative">
                        @forelse($lead->remarks as $remark)
                            <div class="d-flex gap-3 mb-4 position-relative">
                                <div class="flex-shrink-0">
                                    @php
                                        $actionBg = match($remark->action_type) {
                                            'created' => 'background-color: #ecfdf5; color: #059669;',
                                            'status_changed' => 'background-color: #fffbeb; color: #d97706;',
                                            default => 'background-color: #eff6ff; color: #2563eb;'
                                        };
                                        $actionIcon = match($remark->action_type) {
                                            'created' => 'bi bi-plus-circle-fill',
                                            'status_changed' => 'bi bi-arrow-repeat',
                                            default => 'bi bi-chat-left-text-fill'
                                        };
                                    @endphp
                                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                                         style="width: 36px; height: 36px; {{ $actionBg }}">
                                        <i class="{{ $actionIcon }} fs-6"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 p-3 rounded-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="fw-bold text-dark" style="font-size: 13.5px;">{{ $remark->user->name ?? 'System' }}</span>
                                            <span class="badge" style="background-color: #e2e8f0; color: #334155; font-size: 10.5px;">
                                                {{ ucfirst(str_replace('_', ' ', $remark->action_type)) }}
                                            </span>
                                            @if($remark->old_status && $remark->new_status)
                                                <span class="badge bg-white text-dark border" style="font-size: 11px;">
                                                    {{ $remark->old_status }} <i class="bi bi-arrow-right mx-1"></i> {{ $remark->new_status }}
                                                </span>
                                            @endif
                                        </div>
                                        <small class="text-muted" style="font-size: 11px;" title="{{ $remark->created_at->format('Y-m-d H:i:s') }}">
                                            <i class="bi bi-clock me-1"></i>{{ $remark->created_at->diffForHumans() }}
                                        </small>
                                    </div>
                                    <div class="text-secondary small mt-2" style="white-space: pre-wrap; line-height: 1.5; font-size: 13px;">{{ $remark->remark }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-chat-dots fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                <p class="mb-0 small">No remarks or activity logged yet.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
