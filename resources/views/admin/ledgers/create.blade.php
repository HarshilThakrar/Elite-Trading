@extends('layouts.app')

@section('title', 'Create Ledger')
@section('header_title', 'Ledger Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Create Ledger</h2>
    <a href="{{ route('ledgers.index') }}" class="btn btn-secondary">
        <i class="ph ph-arrow-left"></i> Back to List
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body bg-light rounded">
        <form action="{{ route('ledgers.store') }}" method="POST">
            @csrf

            <div class="row g-4">
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3">Basic Details</h5>
                    
                    <div class="mb-3">
                        <label class="form-label required">Ledger Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ledger Code</label>
                        <input type="text" name="ledger_code" class="form-control @error('ledger_code') is-invalid @enderror" value="{{ old('ledger_code') }}">
                        @error('ledger_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Ledger Group</label>
                        <select name="account_group_id" id="account_group_id" class="form-select select2 @error('account_group_id') is-invalid @enderror" required>
                            <option value="">Select Group...</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" data-normal-balance="{{ $group->normal_balance }}" {{ old('account_group_id') == $group->id ? 'selected' : '' }}>
                                    {{ $group->name }} ({{ $group->nature }})
                                </option>
                            @endforeach
                        </select>
                        @error('account_group_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ledger Type</label>
                        <select name="type" id="ledger_type" class="form-select">
                            <option value="general" {{ old('type') == 'general' ? 'selected' : '' }}>General</option>
                            <option value="customer" {{ old('type') == 'customer' ? 'selected' : '' }}>Customer</option>
                            <option value="vendor" {{ old('type') == 'vendor' ? 'selected' : '' }}>Vendor</option>
                            <option value="bank" {{ old('type') == 'bank' ? 'selected' : '' }}>Bank</option>
                            <option value="cash" {{ old('type') == 'cash' ? 'selected' : '' }}>Cash</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="border-bottom pb-2 mb-3">Opening Balance</h5>

                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Amount (₹)</label>
                            <input type="number" step="0.01" min="0" name="opening_balance" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', 0) }}">
                            @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Type (Dr/Cr)</label>
                            <select name="opening_balance_type" id="opening_balance_type" class="form-select @error('opening_balance_type') is-invalid @enderror">
                                <option value="Dr" {{ old('opening_balance_type') == 'Dr' ? 'selected' : '' }}>Debit (Dr)</option>
                                <option value="Cr" {{ old('opening_balance_type') == 'Cr' ? 'selected' : '' }}>Credit (Cr)</option>
                            </select>
                            <div class="form-text text-muted" id="normal_balance_hint"></div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Opening Balance Date</label>
                        <input type="date" name="opening_balance_date" class="form-control" value="{{ old('opening_balance_date') }}">
                    </div>

                    <!-- Dynamic Sections -->
                    <div id="customer_section" class="dynamic-section d-none">
                        <h5 class="border-bottom pb-2 mb-3 text-info"><i class="ph ph-users"></i> Customer Details</h5>
                        <div class="mb-3">
                            <label class="form-label required">Link to Customer</label>
                            <select name="reference_id" id="customer_id" class="form-select select2" style="width: 100%;">
                                <option value="">Select Customer...</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('reference_id') == $customer->id && old('type') == 'customer' ? 'selected' : '' }}>
                                        {{ $customer->company_name }} ({{ $customer->customer_code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('reference_id') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div id="vendor_section" class="dynamic-section d-none">
                        <h5 class="border-bottom pb-2 mb-3 text-warning"><i class="ph ph-storefront"></i> Vendor Details</h5>
                        <div class="mb-3">
                            <label class="form-label required">Link to Vendor</label>
                            <select name="reference_id" id="vendor_id" class="form-select select2" style="width: 100%;">
                                <option value="">Select Vendor...</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ old('reference_id') == $vendor->id && old('type') == 'vendor' ? 'selected' : '' }}>
                                        {{ $vendor->company_name }} ({{ $vendor->vendor_code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('reference_id') <div class="text-danger mt-1 small">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div id="bank_section" class="dynamic-section d-none">
                        <h5 class="border-bottom pb-2 mb-3 text-success"><i class="ph ph-bank"></i> Bank Details</h5>
                        <div class="mb-3">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Number</label>
                            <input type="text" name="account_number" class="form-control" value="{{ old('account_number') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">IFSC Code</label>
                            <input type="text" name="ifsc" class="form-control" value="{{ old('ifsc') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Branch</label>
                            <input type="text" name="branch" class="form-control" value="{{ old('branch') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Type</label>
                            <select name="account_type" class="form-select">
                                <option value="Current" {{ old('account_type') == 'Current' ? 'selected' : '' }}>Current</option>
                                <option value="Savings" {{ old('account_type') == 'Savings' ? 'selected' : '' }}>Savings</option>
                                <option value="CC/OD" {{ old('account_type') == 'CC/OD' ? 'selected' : '' }}>CC / OD</option>
                            </select>
                        </div>
                    </div>

                </div>
            </div>

            <div class="text-end mt-4">
                <button type="submit" class="btn btn-primary px-5"><i class="ph ph-check"></i> Save Ledger</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        function toggleDynamicSections() {
            var type = $('#ledger_type').val();
            $('.dynamic-section').addClass('d-none');
            
            // Disable reference_id selects to prevent validation errors on hidden fields
            $('#customer_id, #vendor_id').prop('disabled', true);
            
            if (type === 'customer') {
                $('#customer_section').removeClass('d-none');
                $('#customer_id').prop('disabled', false);
            } else if (type === 'vendor') {
                $('#vendor_section').removeClass('d-none');
                $('#vendor_id').prop('disabled', false);
            } else if (type === 'bank') {
                $('#bank_section').removeClass('d-none');
            }
        }

        function updateNormalBalanceHint() {
            var option = $('#account_group_id').find('option:selected');
            if (option.val()) {
                var normal = option.data('normal-balance');
                $('#normal_balance_hint').text('Suggested default: ' + normal).addClass('text-info');
                
                // Set default only if user hasn't explicitly chosen yet (or on initial group select)
                // We won't force it since opening balance can differ.
                if(!$('#opening_balance_type').data('user-changed')) {
                    $('#opening_balance_type').val(normal);
                }
            } else {
                $('#normal_balance_hint').text('');
            }
        }

        $('#ledger_type').change(toggleDynamicSections);
        $('#account_group_id').change(updateNormalBalanceHint);
        
        $('#opening_balance_type').change(function() {
            $(this).data('user-changed', true);
        });

        // Initialize
        toggleDynamicSections();
        updateNormalBalanceHint();
    });
</script>
@endpush
