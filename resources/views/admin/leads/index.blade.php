@extends('layouts.app')

@section('title', 'Lead Management - Demo ERP')
@section('header_title', 'Lead Management')

@section('content')
<div class="container-fluid px-0">
    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">Lead Management</h3>
            <p class="text-muted small mb-0">Track prospects, monitor pipeline stages, and manage follow-up remarks & notifications.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('leads.create') }}" class="btn btn-primary px-3 py-2 shadow-sm fw-semibold d-inline-flex align-items-center gap-2" style="background-color: #2563eb !important; border-color: #2563eb !important; color: #ffffff !important; border-radius: 8px;">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Create Lead</span>
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
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
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-left: 4px solid #3b82f6 !important;">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill fs-5 me-2 text-info"></i>
                <div class="fw-medium">{{ session('info') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metric Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Leads -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100 rounded-3" style="background: #ffffff; border: 1px solid #eef2f6 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Total Leads</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background-color: #eff6ff; color: #2563eb;">
                            <i class="bi bi-funnel"></i>
                        </div>
                    </div>
                    <div class="h3 mb-0 fw-bold text-dark">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>

        <!-- New Leads -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100 rounded-3" style="background: #ffffff; border: 1px solid #eef2f6 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">New Leads</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background-color: #ecfeff; color: #0891b2;">
                            <i class="bi bi-stars"></i>
                        </div>
                    </div>
                    <div class="h3 mb-0 fw-bold" style="color: #0891b2;">{{ number_format($stats['new']) }}</div>
                </div>
            </div>
        </div>

        <!-- In Progress -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100 rounded-3" style="background: #ffffff; border: 1px solid #eef2f6 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">In Progress</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background-color: #fffbeb; color: #d97706;">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                    </div>
                    <div class="h3 mb-0 fw-bold" style="color: #d97706;">{{ number_format($stats['in_progress']) }}</div>
                </div>
            </div>
        </div>

        <!-- Won / Converted -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100 rounded-3" style="background: #ffffff; border: 1px solid #eef2f6 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Won / Closed</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background-color: #ecfdf5; color: #059669;">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                    </div>
                    <div class="h3 mb-0 fw-bold" style="color: #059669;">{{ number_format($stats['won']) }}</div>
                </div>
            </div>
        </div>

        <!-- Lost -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100 rounded-3" style="background: #ffffff; border: 1px solid #eef2f6 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Lost</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background-color: #fef2f2; color: #dc2626;">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                    </div>
                    <div class="h3 mb-0 fw-bold" style="color: #dc2626;">{{ number_format($stats['lost']) }}</div>
                </div>
            </div>
        </div>

        <!-- Total Pipeline Value -->
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100 rounded-3" style="background: #ffffff; border: 1px solid #eef2f6 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 11px;">Pipeline Value</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background-color: #f8fafc; color: #1e293b;">
                            <i class="bi bi-currency-rupee"></i>
                        </div>
                    </div>
                    <div class="h4 mb-0 fw-bold text-dark text-truncate" title="₹{{ number_format($stats['total_value'], 2) }}">
                        ₹{{ number_format($stats['total_value'], 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4 rounded-3" style="background: #ffffff; border: 1px solid #eef2f6 !important;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('leads.index') }}">
                <div class="row g-2 align-items-center">
                    <div class="col-lg-3 col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search name, code, phone, company..." value="{{ request('search') }}" style="font-size: 13.5px;">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <select name="status" class="form-select" style="font-size: 13.5px;">
                            <option value="">All Statuses</option>
                            @foreach($statuses as $st)
                                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <select name="priority" class="form-select" style="font-size: 13.5px;">
                            <option value="">All Priorities</option>
                            @foreach($priorities as $pr)
                                <option value="{{ $pr }}" {{ request('priority') === $pr ? 'selected' : '' }}>{{ $pr }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <select name="source" class="form-select" style="font-size: 13.5px;">
                            <option value="">All Sources</option>
                            @foreach($sources as $src)
                                <option value="{{ $src }}" {{ request('source') === $src ? 'selected' : '' }}>{{ $src }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <select name="assigned_to" class="form-select" style="font-size: 13.5px;">
                            <option value="">All Assignees</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ request('assigned_to') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-1 col-md-4 d-flex gap-1">
                        <button type="submit" class="btn btn-primary w-100 fw-semibold d-flex align-items-center justify-content-center" title="Apply Filter" style="background-color: #2563eb !important; border-color: #2563eb !important; color: #fff !important; height: 38px;">
                            <i class="bi bi-funnel-fill me-1"></i> Filter
                        </button>
                        @if(request()->hasAny(['search', 'status', 'priority', 'source', 'assigned_to']))
                            <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary d-flex align-items-center justify-content-center" title="Clear Filters" style="height: 38px; width: 38px; flex-shrink: 0;">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Leads Table Card -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden" style="border: 1px solid #eef2f6 !important;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13.5px;">
                    <thead style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <tr>
                            <th class="ps-4 py-3 text-secondary fw-semibold text-uppercase" style="font-size: 11.5px; letter-spacing: 0.5px;">Lead Info</th>
                            <th class="py-3 text-secondary fw-semibold text-uppercase" style="font-size: 11.5px; letter-spacing: 0.5px;">Contact & Company</th>
                            <th class="py-3 text-secondary fw-semibold text-uppercase" style="font-size: 11.5px; letter-spacing: 0.5px;">Status & Source</th>
                            <th class="py-3 text-secondary fw-semibold text-uppercase" style="font-size: 11.5px; letter-spacing: 0.5px;">Priority</th>
                            <th class="py-3 text-secondary fw-semibold text-uppercase" style="font-size: 11.5px; letter-spacing: 0.5px;">Estimated Value</th>
                            <th class="py-3 text-secondary fw-semibold text-uppercase" style="font-size: 11.5px; letter-spacing: 0.5px;">Assigned To</th>
                            <th class="py-3 text-secondary fw-semibold text-uppercase" style="font-size: 11.5px; letter-spacing: 0.5px;">Latest Remark / Notification</th>
                            <th class="text-end pe-4 py-3 text-secondary fw-semibold text-uppercase" style="font-size: 11.5px; letter-spacing: 0.5px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($leads as $lead)
                            <tr>
                                <!-- Lead Info -->
                                <td class="ps-4 py-3">
                                    <div class="fw-bold">
                                        <a href="{{ route('leads.show', $lead->id) }}" class="text-decoration-none text-dark hover-primary" style="font-size: 14.5px;">
                                            {{ $lead->name }}
                                        </a>
                                    </div>
                                    <div class="d-flex align-items-center gap-1 mt-1">
                                        <span class="badge font-monospace" style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 11px; padding: 2px 6px;">
                                            {{ $lead->lead_code }}
                                        </span>
                                        @if($lead->customer_id)
                                            <span class="badge" style="background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-size: 10.5px;" title="Converted to Customer">
                                                <i class="bi bi-check-circle me-1"></i>Customer
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Contact & Company -->
                                <td class="py-3">
                                    @if($lead->company_name)
                                        <div class="fw-semibold text-dark text-truncate" style="max-width: 220px;">
                                            <i class="bi bi-building text-muted me-1"></i>{{ $lead->company_name }}
                                        </div>
                                    @endif
                                    <div class="text-muted small">
                                        <a href="tel:{{ $lead->phone }}" class="text-decoration-none text-secondary">
                                            <i class="bi bi-telephone-fill text-primary me-1" style="font-size: 11px;"></i>{{ $lead->phone }}
                                        </a>
                                    </div>
                                    @if($lead->email)
                                        <div class="text-muted small text-truncate" style="max-width: 220px;">
                                            <a href="mailto:{{ $lead->email }}" class="text-decoration-none text-secondary">
                                                <i class="bi bi-envelope-fill text-info me-1" style="font-size: 11px;"></i>{{ $lead->email }}
                                            </a>
                                        </div>
                                    @endif
                                </td>

                                <!-- Status & Source -->
                                <td class="py-3">
                                    @php
                                        $statusStyle = match($lead->status) {
                                            'New' => 'background-color: #eff6ff; color: #1d4ed8; border: 1px solid rgba(29, 78, 216, 0.25);',
                                            'Contacted' => 'background-color: #f5f3ff; color: #6d28d9; border: 1px solid rgba(109, 40, 217, 0.25);',
                                            'Qualified' => 'background-color: #ecfeff; color: #0e7490; border: 1px solid rgba(14, 116, 144, 0.25);',
                                            'Proposal Sent' => 'background-color: #fffbeb; color: #b45309; border: 1px solid rgba(180, 83, 9, 0.25);',
                                            'Negotiation' => 'background-color: #eef2ff; color: #4338ca; border: 1px solid rgba(67, 56, 202, 0.25);',
                                            'Won' => 'background-color: #ecfdf5; color: #047857; border: 1px solid rgba(4, 120, 87, 0.25);',
                                            'Lost' => 'background-color: #fef2f2; color: #b91c1c; border: 1px solid rgba(185, 28, 28, 0.25);',
                                            default => 'background-color: #f3f4f6; color: #374151; border: 1px solid rgba(55, 65, 81, 0.25);'
                                        };
                                    @endphp
                                    <span class="d-inline-flex align-items-center px-2 py-1 rounded-pill fw-semibold" style="font-size: 11.5px; {{ $statusStyle }}">
                                        <span class="rounded-circle me-1" style="width: 6px; height: 6px; background-color: currentColor;"></span>
                                        {{ $lead->status }}
                                    </span>
                                    <div class="text-muted small mt-1" style="font-size: 11.5px;">
                                        <i class="bi bi-diagram-3 me-1"></i>{{ $lead->source }}
                                    </div>
                                </td>

                                <!-- Priority -->
                                <td class="py-3">
                                    @php
                                        $priorityStyle = match($lead->priority) {
                                            'Urgent' => 'background-color: #fef2f2; color: #b91c1c; border: 1px solid rgba(220, 38, 38, 0.3);',
                                            'High' => 'background-color: #fff7ed; color: #c2410c; border: 1px solid rgba(234, 88, 12, 0.3);',
                                            'Medium' => 'background-color: #eff6ff; color: #1d4ed8; border: 1px solid rgba(37, 99, 235, 0.3);',
                                            'Low' => 'background-color: #f9fafb; color: #4b5563; border: 1px solid rgba(107, 114, 128, 0.3);',
                                            default => 'background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb;'
                                        };
                                    @endphp
                                    <span class="d-inline-flex align-items-center px-2 py-1 rounded fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.3px; {{ $priorityStyle }}">
                                        {{ $lead->priority }}
                                    </span>
                                </td>

                                <!-- Estimated Value -->
                                <td class="py-3">
                                    <div class="fw-bold text-dark" style="font-size: 14px;">
                                        ₹{{ number_format($lead->estimated_value, 2) }}
                                    </div>
                                    @if($lead->expected_close_date)
                                        <div class="text-muted small" style="font-size: 11px;">
                                            Target: {{ $lead->expected_close_date->format('d M Y') }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Assigned To -->
                                <td class="py-3">
                                    @if($lead->assignedUser)
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center text-primary fw-bold" style="width: 28px; height: 28px; background-color: #eff6ff; font-size: 12px; flex-shrink: 0;">
                                                {{ strtoupper(substr($lead->assignedUser->name, 0, 1)) }}
                                            </div>
                                            <span class="small fw-semibold text-dark text-truncate" style="max-width: 110px;">{{ $lead->assignedUser->name }}</span>
                                        </div>
                                    @else
                                        <span class="badge bg-light text-muted border" style="font-size: 11px;">Unassigned</span>
                                    @endif
                                </td>

                                <!-- Latest Remark / Notification -->
                                <td class="py-3" style="min-width: 220px; max-width: 270px;">
                                    @if($lead->latestRemark)
                                        <div class="p-2 rounded-2" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <span class="fw-bold text-dark text-truncate" style="font-size: 11px; max-width: 120px;">
                                                    <i class="bi bi-chat-left-text-fill text-primary me-1"></i>{{ $lead->latestRemark->user->name ?? 'System' }}
                                                </span>
                                                <span class="text-muted" style="font-size: 10.5px;">
                                                    <i class="bi bi-clock me-1"></i>{{ $lead->latestRemark->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            <div class="text-secondary small text-truncate" title="{{ $lead->latestRemark->remark }}" style="font-size: 11.5px; line-height: 1.3;">
                                                {{ $lead->latestRemark->remark }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted small fst-italic">
                                            <i class="bi bi-chat-dots me-1"></i>No remarks yet
                                        </span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="text-end pe-4 py-3">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <!-- View Details & Remarks -->
                                        <a href="{{ route('leads.show', $lead->id) }}" class="btn btn-sm btn-light border text-primary action-icon-btn" title="View Lead & Activity Timeline">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <!-- Quick Add Remark -->
                                        <button type="button" class="btn btn-sm btn-light border text-info action-icon-btn" data-bs-toggle="modal" data-bs-target="#remarkModal{{ $lead->id }}" title="Quick Add Remark & Notify">
                                            <i class="bi bi-chat-dots"></i>
                                        </button>

                                        <!-- Edit Lead -->
                                        <a href="{{ route('leads.edit', $lead->id) }}" class="btn btn-sm btn-light border text-secondary action-icon-btn" title="Edit Lead Details">
                                            <i class="bi bi-pencil"></i>
                                        </a>

                                        <!-- Delete Lead -->
                                        <form action="{{ route('leads.destroy', $lead->id) }}" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('Are you sure you want to remove Lead {{ $lead->lead_code }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border text-danger action-icon-btn" title="Delete Lead">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Quick Remark Modal -->
                                    <div class="modal fade" id="remarkModal{{ $lead->id }}" tabindex="-1" aria-labelledby="remarkModalLabel{{ $lead->id }}" aria-hidden="true">
                                        <div class="modal-dialog text-start modal-dialog-centered">
                                            <div class="modal-content border-0 shadow">
                                                <form action="{{ route('leads.remarks.store', $lead->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header border-bottom py-3">
                                                        <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2 mb-0" id="remarkModalLabel{{ $lead->id }}">
                                                            <div class="rounded-circle d-flex align-items-center justify-content-center text-primary" style="width: 28px; height: 28px; background-color: #eff6ff;">
                                                                <i class="bi bi-chat-left-dots-fill"></i>
                                                            </div>
                                                            <span>Add Remark & Notify: {{ $lead->name }}</span>
                                                        </h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-3">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold small text-muted">Update Pipeline Status (Optional)</label>
                                                            <select name="status" class="form-select">
                                                                <option value="">Keep current status ({{ $lead->status }})</option>
                                                                @foreach($statuses as $st)
                                                                    <option value="{{ $st }}" {{ $lead->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label fw-semibold small text-muted">Remark / Follow-up Note <span class="text-danger">*</span></label>
                                                            <textarea name="remark" class="form-control" rows="3" placeholder="Enter follow-up details, client discussion, meeting notes..." required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top py-2">
                                                        <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-sm btn-primary px-3 fw-semibold" style="background-color: #2563eb !important; border-color: #2563eb !important; color: #fff !important;">
                                                            <i class="bi bi-send-fill me-1"></i> Save Remark & Notify
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="my-4">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; background-color: #f1f5f9; color: #94a3b8;">
                                            <i class="bi bi-funnel fs-2"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No Leads Found</h5>
                                        <p class="small text-muted mb-3">No leads match your current search or filter criteria.</p>
                                        <a href="{{ route('leads.create') }}" class="btn btn-sm btn-primary px-3 shadow-sm fw-semibold" style="background-color: #2563eb !important; border-color: #2563eb !important; color: #fff !important;">
                                            <i class="bi bi-plus-circle me-1"></i> Create First Lead
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($leads->hasPages())
                <div class="p-3 border-top bg-light d-flex justify-content-end">
                    {{ $leads->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .hover-primary:hover {
        color: #2563eb !important;
        text-decoration: underline !important;
    }
    .action-icon-btn {
        width: 32px;
        height: 32px;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: all 0.15s ease-in-out;
    }
    .action-icon-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    }
</style>
@endsection
