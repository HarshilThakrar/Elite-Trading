@extends('layouts.app')

@section('title', 'Add Vendor - Demo ERP')
@section('header_title', 'Add New Vendor')

@section('content')
<div class="card card-custom">
    <div class="card-body">
        <form action="{{ route('vendors.store') }}" method="POST">
            @csrf
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Vendor Code</label>
                    <input type="text" name="vendor_code" class="form-control @error('vendor_code') is-invalid @enderror" value="{{ old('vendor_code') }}" placeholder="Leave empty to auto-generate">
                    @error('vendor_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name') }}" required>
                    @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Contact Person</label>
                    <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Mobile <span class="text-danger">*</span></label>
                    <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile') }}" required>
                    @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">GST No</label>
                    <input type="text" name="gst_no" class="form-control" value="{{ old('gst_no') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Lead Time (Days)</label>
                    <input type="number" name="lead_time_days" class="form-control" value="{{ old('lead_time_days', 0) }}" min="0">
                </div>
            </div>
            
            <div class="mb-4 form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="statusSwitch" name="status" checked>
                <label class="form-check-label" for="statusSwitch">Active Vendor</label>
            </div>

            <div class="d-flex justify-content-end">
                <a href="{{ route('vendors.index') }}" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary-custom">Save Vendor</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Escape key to redirect back to vendors list
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                window.location.href = "{{ route('vendors.index') }}";
            }
        });
    });
</script>
@endpush
