@extends('layouts.app')

@section('title', 'Edit Ledger - ' . $ledger->name)
@section('header_title', 'Ledger Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Edit Ledger: <span class="text-primary">{{ $ledger->name }}</span></h2>
    <div>
        <a href="{{ route('ledgers.index') }}" class="btn btn-secondary me-2">
            <i class="ph ph-arrow-left"></i> Back to List
        </a>
        <a href="{{ route('ledgers.show', $ledger->id) }}" class="btn btn-outline-info">
            <i class="ph ph-eye"></i> View Details
        </a>
    </div>
</div>

@if($hasTransactions || $ledger->is_system)
<div class="alert alert-warning d-flex align-items-center" role="alert">
    <i class="ph-fill ph-warning-circle me-3 fs-4"></i>
    <div>
        <strong>Restricted Editing:</strong> 
        @if($ledger->is_system)
            This is a protected system ledger. 
        @elseif($hasTransactions)
            This ledger has posted transactions. 
        @endif
        Accounting classification, type, and opening balances cannot be changed.
    </div>
</div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-body bg-light rounded">
        <form action="{{ route('ledgers.update', $ledger->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3">Basic Details</h5>
                    
                    <div class="mb-3">
                        <label class="form-label required">Ledger Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $ledger->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ledger Code</label>
                        <input type="text" name="ledger_code" class="form-control @error('ledger_code') is-invalid @enderror" value="{{ old('ledger_code', $ledger->ledger_code) }}">
                        @error('ledger_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Ledger Group</label>
                        @if($hasTransactions || $ledger->is_system)
                            <input type="hidden" name="account_group_id" value="{{ $ledger->account_group_id }}">
                            <input type="text" class="form-control bg-white" value="{{ $ledger->accountGroup->name }} ({{ $ledger->accountGroup->nature }})" readonly disabled>
                        @else
                            <select name="account_group_id" id="account_group_id" class="form-select select2 @error('account_group_id') is-invalid @enderror" required>
                                <option value="">Select Group...</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}" data-normal-balance="{{ $group->normal_balance }}" {{ old('account_group_id', $ledger->account_group_id) == $group->id ? 'selected' : '' }}>
                                        {{ $group->name }} ({{ $group->nature }})
                                    </option>
                                @endforeach
                            </select>
                            @error('account_group_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ledger Type</label>
                        @if($hasTransactions || $ledger->is_system)
                            <input type="hidden" name="type" value="{{ $ledger->type }}">
                            <input type="text" class="form-control bg-white text-capitalize" value="{{ $ledger->type ?? 'General' }}" readonly disabled>
                        @else
                            <select name="type" id="ledger_type" class="form-select">
                                <option value="general" {{ old('type', $ledger->type) == 'general' ? 'selected' : '' }}>General</option>
                                <option value="customer" {{ old('type', $ledger->type) == 'customer' ? 'selected' : '' }}>Customer</option>
                                <option value="vendor" {{ old('type', $ledger->type) == 'vendor' ? 'selected' : '' }}>Vendor</option>
                                <option value="bank" {{ old('type', $ledger->type) == 'bank' ? 'selected' : '' }}>Bank</option>
                                <option value="cash" {{ old('type', $ledger->type) == 'cash' ? 'selected' : '' }}>Cash</option>
                            </select>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $ledger->description) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $ledger->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3">Opening Balance</h5>

                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Amount (₹)</label>
                            @if($hasTransactions || $ledger->is_system)
                                <input type="hidden" name="opening_balance" value="{{ $ledger->opening_balance }}">
                                <input type="text" class="form-control bg-white" value="{{ number_format($ledger->opening_balance, 2) }}" readonly disabled>
                            @else
                                <input type="number" step="0.01" min="0" name="opening_balance" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', $ledger->opening_balance) }}">
                                @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Type (Dr/Cr)</label>
                            @if($hasTransactions || $ledger->is_system)
                                <input type="hidden" name="opening_balance_type" value="{{ $ledger->opening_balance_type }}">
                                <input type="text" class="form-control bg-white" value="{{ $ledger->opening_balance_type == 'Dr' ? 'Debit (Dr)' : 'Credit (Cr)' }}" readonly disabled>
                            @else
                                <select name="opening_balance_type" id="opening_balance_type" class="form-select @error('opening_balance_type') is-invalid @enderror">
                                    <option value="Dr" {{ old('opening_balance_type', $ledger->opening_balance_type) == 'Dr' ? 'selected' : '' }}>Debit (Dr)</option>
                                    <option value="Cr" {{ old('opening_balance_type', $ledger->opening_balance_type) == 'Cr' ? 'selected' : '' }}>Credit (Cr)</option>
                                </select>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Opening Balance Date</label>
                        @if($hasTransactions || $ledger->is_system)
                            <input type="hidden" name="opening_balance_date" value="{{ $ledger->opening_balance_date ? $ledger->opening_balance_date->format('Y-m-d') : '' }}">
                            <input type="date" class="form-control bg-white" value="{{ $ledger->opening_balance_date ? $ledger->opening_balance_date->format('Y-m-d') : '' }}" readonly disabled>
                        @else
                            <input type="date" name="opening_balance_date" class="form-control" value="{{ old('opening_balance_date', $ledger->opening_balance_date ? $ledger->opening_balance_date->format('Y-m-d') : '') }}">
                        @endif
                    </div>

                    <!-- Dynamic Sections -->
                    <div id="customer_section" class="dynamic-section d-none">
                        <h5 class="border-bottom pb-2 mb-3 text-info"><i class="ph ph-users"></i> Customer Details</h5>
                        <div class="mb-3">
                            <label class="form-label required">Link to Customer</label>
                            @if($hasTransactions || $ledger->is_system)
                                <input type="hidden" name="reference_id" value="{{ $ledger->reference_id }}">
                                <select class="form-select bg-white" disabled>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ $ledger->reference_id == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->company_name }} ({{ $customer->customer_code }})
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <select name="reference_id" id="customer_id" class="form-select select2" style="width: 100%;">
                                    <option value="">Select Customer...</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('reference_id', $ledger->reference_id) == $customer->id && old('type', $ledger->type) == 'customer' ? 'selected' : '' }}>
                                            {{ $customer->company_name }} ({{ $customer->customer_code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('reference_id') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            @endif
                        </div>
                    </div>

                    <div id="vendor_section" class="dynamic-section d-none">
                        <h5 class="border-bottom pb-2 mb-3 text-warning"><i class="ph ph-storefront"></i> Vendor Details</h5>
                        <div class="mb-3">
                            <label class="form-label required">Link to Vendor</label>
                            @if($hasTransactions || $ledger->is_system)
                                <input type="hidden" name="reference_id" value="{{ $ledger->reference_id }}">
                                <select class="form-select bg-white" disabled>
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}" {{ $ledger->reference_id == $vendor->id ? 'selected' : '' }}>
                                            {{ $vendor->company_name }} ({{ $vendor->vendor_code }})
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <select name="reference_id" id="vendor_id" class="form-select select2" style="width: 100%;">
                                    <option value="">Select Vendor...</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}" {{ old('reference_id', $ledger->reference_id) == $vendor->id && old('type', $ledger->type) == 'vendor' ? 'selected' : '' }}>
                                            {{ $vendor->company_name }} ({{ $vendor->vendor_code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('reference_id') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                            @endif
                        </div>
                    </div>

                    <div id="bank_section" class="dynamic-section d-none">
                        <h5 class="border-bottom pb-2 mb-3 text-success"><i class="ph ph-bank"></i> Bank Details</h5>
                        @php
                            $meta = $ledger->metadata ?? [];
                        @endphp
                        <div class="mb-3">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $meta['bank_name'] ?? '') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Number</label>
                            <input type="text" name="account_number" class="form-control" value="{{ old('account_number', $meta['account_number'] ?? '') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">IFSC Code</label>
                            <input type="text" name="ifsc" class="form-control" value="{{ old('ifsc', $meta['ifsc'] ?? '') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Branch</label>
                            <input type="text" name="branch" class="form-control" value="{{ old('branch', $meta['branch'] ?? '') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Type</label>
                            <select name="account_type" class="form-select">
                                <option value="Current" {{ old('account_type', $meta['account_type'] ?? '') == 'Current' ? 'selected' : '' }}>Current</option>
                                <option value="Savings" {{ old('account_type', $meta['account_type'] ?? '') == 'Savings' ? 'selected' : '' }}>Savings</option>
                                <option value="CC/OD" {{ old('account_type', $meta['account_type'] ?? '') == 'CC/OD' ? 'selected' : '' }}>CC / OD</option>
                            </select>
                        </div>
                    </div>

                </div>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary px-5"><i class="ph ph-check"></i> Update Ledger</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        function toggleDynamicSections() {
            // Use the hidden field value if select is disabled (due to transactions/system)
            var typeSelect = $('#ledger_type').length ? $('#ledger_type').val() : null;
            var typeHidden = $('input[name="type"]').val();
            var type = typeSelect || typeHidden;

            $('.dynamic-section').addClass('d-none');
            
            // Only disable reference_id selects if they are actual form inputs (not already hidden by server logic)
            if($('#customer_id').length) $('#customer_id').prop('disabled', true);
            if($('#vendor_id').length) $('#vendor_id').prop('disabled', true);
            
            if (type === 'customer') {
                $('#customer_section').removeClass('d-none');
                if($('#customer_id').length) $('#customer_id').prop('disabled', false);
            } else if (type === 'vendor') {
                $('#vendor_section').removeClass('d-none');
                if($('#vendor_id').length) $('#vendor_id').prop('disabled', false);
            } else if (type === 'bank') {
                $('#bank_section').removeClass('d-none');
            }
        }

        $('#ledger_type').change(toggleDynamicSections);
        
        // Initialize
        toggleDynamicSections();
    });
</script>
@endpush
