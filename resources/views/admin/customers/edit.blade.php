@extends('layouts.app')

@section('title', 'Edit Customer - Demo ERP')
@section('header_title', 'Edit Customer')

@section('content')
<div class="card card-custom">
    <div class="card-body">
        <form action="{{ route('customers.update', $customer->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Customer Code</label>
                    <input type="text" name="customer_code" class="form-control @error('customer_code') is-invalid @enderror" value="{{ old('customer_code', $customer->customer_code) }}" readonly>
                    @error('customer_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" id="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name', $customer->company_name) }}" required>
                    <div id="company_name_error" class="invalid-feedback" style="display: none;">The company name has already been taken.</div>
                    @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Contact Person</label>
                    <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $customer->contact_person) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Mobile <span class="text-danger">*</span></label>
                    <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile', $customer->mobile) }}" required>
                    @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address', $customer->address) }}</textarea>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">City</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $customer->city) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">State</label>
                    <input type="text" name="state" class="form-control" value="{{ old('state', $customer->state) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Pincode</label>
                    <input type="text" name="pincode" class="form-control" value="{{ old('pincode', $customer->pincode) }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">GST No</label>
                    <input type="text" name="gst_no" class="form-control" value="{{ old('gst_no', $customer->gst_no) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Credit Limit (₹)</label>
                    <input type="number" step="0.01" name="credit_limit" class="form-control" value="{{ old('credit_limit', $customer->credit_limit) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Payment Terms</label>
                    <select name="payment_terms" class="form-select select2">
                        <option value="">Select Terms</option>
                        <option value="Advance" {{ $customer->payment_terms == 'Advance' ? 'selected' : '' }}>Advance</option>
                        <option value="Net 15" {{ $customer->payment_terms == 'Net 15' ? 'selected' : '' }}>Net 15</option>
                        <option value="Net 30" {{ $customer->payment_terms == 'Net 30' ? 'selected' : '' }}>Net 30</option>
                        <option value="Net 45" {{ $customer->payment_terms == 'Net 45' ? 'selected' : '' }}>Net 45</option>
                        <option value="Net 60" {{ $customer->payment_terms == 'Net 60' ? 'selected' : '' }}>Net 60</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-4 form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="statusSwitch" name="status" {{ $customer->status ? 'checked' : '' }}>
                <label class="form-check-label" for="statusSwitch">Active Customer</label>
            </div>

            <div class="d-flex justify-content-end">
                <a href="{{ route('customers.index') }}" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary-custom">Update Customer</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let checkTimeout;
    $('#company_name').on('input', function() {
        clearTimeout(checkTimeout);
        let companyName = $(this).val();
        let errorDiv = $('#company_name_error');
        let submitBtn = $('button[type="submit"]');

        if(companyName.trim().length > 0) {
            checkTimeout = setTimeout(function() {
                $.ajax({
                    url: '{{ route('customers.checkName') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        company_name: companyName,
                        exclude_id: '{{ $customer->id }}'
                    },
                    success: function(response) {
                        if(response.exists) {
                            $('#company_name').addClass('is-invalid');
                            errorDiv.show();
                            submitBtn.prop('disabled', true);
                        } else {
                            $('#company_name').removeClass('is-invalid');
                            errorDiv.hide();
                            submitBtn.prop('disabled', false);
                        }
                    }
                });
            }, 500);
        } else {
            $('#company_name').removeClass('is-invalid');
            errorDiv.hide();
            submitBtn.prop('disabled', false);
        }
    });
    // Enter key to move to next input like Tab
    $('input, select, textarea').on('keydown', function(e) {
        if (e.key === 'Enter') {
            if ($(this).is('textarea') && e.shiftKey) {
                return; // Allow Shift+Enter to add a new line in textarea
            }
            e.preventDefault();
            var inputs = $(this).closest('form').find(':input:visible:not([disabled]):not([readonly])');
            var index = inputs.index(this);
            if (index > -1 && (index + 1) < inputs.length) {
                var nextInput = inputs.eq(index + 1);
                nextInput.focus();
                if (nextInput.hasClass('select2-hidden-accessible')) {
                    nextInput.select2('open');
                }
            } else {
                $(this).closest('form').submit();
            }
        }
    });

    // Move to next input after selecting from Select2
    $('.select2, .form-select').on('select2:select change', function(e) {
        if ($(this).hasClass('select2-hidden-accessible') && e.type === 'change') return;
        
        var inputs = $(this).closest('form').find(':input:visible:not([disabled]):not([readonly])');
        var index = inputs.index(this);
        if (index > -1 && (index + 1) < inputs.length) {
            var nextInput = inputs.eq(index + 1);
            setTimeout(function() {
                nextInput.focus();
                if (nextInput.hasClass('select2-hidden-accessible')) {
                    nextInput.select2('open');
                }
            }, 50);
        }
    });
});
</script>
@endpush
