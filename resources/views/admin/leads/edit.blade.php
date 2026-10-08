@extends('layouts.app')

@section('title', 'Edit Lead: ' . $lead->name . ' - Demo ERP')
@section('header_title', 'Edit Lead')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('leads.show', $lead->id) }}" class="btn btn-outline-secondary d-flex align-items-center gap-1 shadow-sm px-3 me-3" style="border-radius: 8px;">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Lead</span>
        </a>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h3 class="fw-bold text-dark mb-0">Edit Lead: {{ $lead->name }}</h3>
                <span class="badge font-monospace" style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 12px; padding: 2px 8px;">
                    {{ $lead->lead_code }}
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Update contact details, pipeline status and log follow-up remarks.</p>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-left: 4px solid #ef4444 !important;">
            <div class="d-flex align-items-center">
                <i class="bi bi-x-circle-fill fs-5 me-2 text-danger"></i>
                <div class="fw-medium">{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-3" style="border: 1px solid #eef2f6 !important;">
        <div class="card-body p-4">
            <form action="{{ route('leads.update', $lead->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Section 1: Contact Information -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-person-lines-fill text-primary"></i>
                        <span>1. Contact & Organization Details</span>
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold small text-muted">Contact Person / Lead Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $lead->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="company_name" class="form-label fw-semibold small text-muted">Company / Business Name</label>
                            <input type="text" name="company_name" id="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name', $lead->company_name) }}">
                            @error('company_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold small text-muted">Phone / Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $lead->phone) }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold small text-muted">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $lead->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="city" class="form-label fw-semibold small text-muted">City</label>
                            <input type="text" name="city" id="city" class="form-control" value="{{ old('city', $lead->city) }}">
                        </div>

                        <div class="col-md-6">
                            <label for="state" class="form-label fw-semibold small text-muted">State</label>
                            <input type="text" name="state" id="state" class="form-control" value="{{ old('state', $lead->state) }}">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Pipeline & Status -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-graph-up-arrow text-primary"></i>
                        <span>2. Lead Pipeline & Valuation</span>
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="source" class="form-label fw-semibold small text-muted">Lead Source <span class="text-danger">*</span></label>
                            <select name="source" id="source" class="form-select @error('source') is-invalid @enderror" required>
                                @foreach($sources as $src)
                                    <option value="{{ $src }}" {{ old('source', $lead->source) === $src ? 'selected' : '' }}>{{ $src }}</option>
                                @endforeach
                            </select>
                            @error('source')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="status" class="form-label fw-semibold small text-muted">Lead Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                @foreach($statuses as $st)
                                    <option value="{{ $st }}" {{ old('status', $lead->status) === $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="priority" class="form-label fw-semibold small text-muted">Priority <span class="text-danger">*</span></label>
                            <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                @foreach($priorities as $pr)
                                    <option value="{{ $pr }}" {{ old('priority', $lead->priority) === $pr ? 'selected' : '' }}>{{ $pr }}</option>
                                @endforeach
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="estimated_value" class="form-label fw-semibold small text-muted">Estimated Deal Value (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">₹</span>
                                <input type="number" step="0.01" min="0" name="estimated_value" id="estimated_value" class="form-control" value="{{ old('estimated_value', $lead->estimated_value) }}">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="assigned_to" class="form-label fw-semibold small text-muted">Assign Salesperson</label>
                            <select name="assigned_to" id="assigned_to" class="form-select">
                                <option value="">-- Unassigned --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to', $lead->assigned_to) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="expected_close_date" class="form-label fw-semibold small text-muted">Expected Closing Date</label>
                            <input type="date" name="expected_close_date" id="expected_close_date" class="form-control" value="{{ old('expected_close_date', $lead->expected_close_date ? $lead->expected_close_date->format('Y-m-d') : '') }}">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Requirement Details -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-file-text-fill text-primary"></i>
                        <span>3. Requirements & Notes</span>
                    </h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="requirement_details" class="form-label fw-semibold small text-muted">Requirement Details</label>
                            <textarea name="requirement_details" id="requirement_details" rows="3" class="form-control">{{ old('requirement_details', $lead->requirement_details) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Update Remark & Activity Notification -->
                <div class="mb-4">
                    <div class="p-3 rounded-3" style="background-color: #f8fafc; border: 1px solid #bfdbfe; border-left: 4px solid #2563eb;">
                        <h6 class="fw-bold text-primary mb-1 d-flex align-items-center gap-2">
                            <i class="bi bi-bell-fill"></i>
                            <span>Update Remark & Activity Notification</span>
                        </h6>
                        <p class="text-muted small mb-2">
                            Enter a remark or follow-up reason for this update. It will be recorded into the lead activity timeline and notification history.
                        </p>
                        <textarea name="update_remark" id="update_remark" rows="2" class="form-control" placeholder="e.g. Spoke to client, agreed on pricing terms, scheduled follow-up call..." style="font-size: 13.5px;">{{ old('update_remark') }}</textarea>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="pt-3 border-top d-flex justify-content-end align-items-center gap-2">
                    <a href="{{ route('leads.show', $lead->id) }}" class="btn btn-light border px-4" style="border-radius: 8px;">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm fw-semibold d-inline-flex align-items-center gap-2" style="background-color: #2563eb !important; border-color: #2563eb !important; color: #ffffff !important; border-radius: 8px;">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Update Lead</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
