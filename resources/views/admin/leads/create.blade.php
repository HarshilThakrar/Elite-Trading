@extends('layouts.app')

@section('title', 'Create Lead - Demo ERP')
@section('header_title', 'Create Lead')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-1 shadow-sm px-3 me-3" style="border-radius: 8px;">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Leads</span>
        </a>
        <div>
            <h3 class="fw-bold text-dark mb-1">Create New Lead</h3>
            <p class="text-muted small mb-0">Register a new client prospect into the sales pipeline.</p>
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
            <form action="{{ route('leads.store') }}" method="POST">
                @csrf

                <!-- Section 1: Contact Information -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-person-lines-fill text-primary"></i>
                        <span>1. Contact & Organization Details</span>
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold small text-muted">Contact Person / Lead Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Rajesh Kumar" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="company_name" class="form-label fw-semibold small text-muted">Company / Business Name</label>
                            <input type="text" name="company_name" id="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name') }}" placeholder="e.g. Apex Engineering Ltd">
                            @error('company_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-semibold small text-muted">Phone / Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="e.g. +91 9876543210" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold small text-muted">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="e.g. contact@apexengineering.com">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="city" class="form-label fw-semibold small text-muted">City</label>
                            <input type="text" name="city" id="city" class="form-control" value="{{ old('city') }}" placeholder="e.g. Ahmedabad">
                        </div>

                        <div class="col-md-6">
                            <label for="state" class="form-label fw-semibold small text-muted">State</label>
                            <input type="text" name="state" id="state" class="form-control" value="{{ old('state', 'Gujarat') }}" placeholder="e.g. Gujarat">
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
                                    <option value="{{ $src }}" {{ old('source', 'Direct / Walk-in') === $src ? 'selected' : '' }}>{{ $src }}</option>
                                @endforeach
                            </select>
                            @error('source')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="status" class="form-label fw-semibold small text-muted">Initial Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                @foreach($statuses as $st)
                                    <option value="{{ $st }}" {{ old('status', 'New') === $st ? 'selected' : '' }}>{{ $st }}</option>
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
                                    <option value="{{ $pr }}" {{ old('priority', 'Medium') === $pr ? 'selected' : '' }}>{{ $pr }}</option>
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
                                <input type="number" step="0.01" min="0" name="estimated_value" id="estimated_value" class="form-control @error('estimated_value') is-invalid @enderror" value="{{ old('estimated_value', '0.00') }}" placeholder="0.00">
                            </div>
                            @error('estimated_value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="assigned_to" class="form-label fw-semibold small text-muted">Assign Salesperson</label>
                            <select name="assigned_to" id="assigned_to" class="form-select">
                                <option value="">-- Unassigned --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to', auth()->id()) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="expected_close_date" class="form-label fw-semibold small text-muted">Expected Closing Date</label>
                            <input type="date" name="expected_close_date" id="expected_close_date" class="form-control" value="{{ old('expected_close_date') }}">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Requirement & Initial Remarks -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-chat-left-dots-fill text-primary"></i>
                        <span>3. Requirements & Initial Remark</span>
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="requirement_details" class="form-label fw-semibold small text-muted">Requirement / Products Inquired</label>
                            <textarea name="requirement_details" id="requirement_details" rows="3" class="form-control" placeholder="Specify items, quantities or specifications inquired..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="initial_remark" class="form-label fw-semibold small text-muted">Initial Remark / Follow-up Note</label>
                            <textarea name="initial_remark" id="initial_remark" rows="3" class="form-control" placeholder="Enter first activity note (will be saved in the lead remarks notification timeline)..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="pt-3 border-top d-flex justify-content-end align-items-center gap-2">
                    <a href="{{ route('leads.index') }}" class="btn btn-light border px-4" style="border-radius: 8px;">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm fw-semibold d-inline-flex align-items-center gap-2" style="background-color: #2563eb !important; border-color: #2563eb !important; color: #ffffff !important; border-radius: 8px;">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Save & Create Lead</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
