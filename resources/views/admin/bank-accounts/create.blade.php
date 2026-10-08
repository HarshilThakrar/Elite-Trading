@extends('layouts.app')

@section('title', 'Add Bank Account - Demo ERP')
@section('header_title', 'Banking')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Account</h5>
                <a href="{{ route('bank-accounts.index') }}" class="btn-close"></a>
            </div>
            <div class="card-body px-4">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('bank-accounts.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label small text-muted"><span class="text-danger">*</span> Account type</label>
                        <select class="form-select">
                            <option>Bank</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted"><span class="text-danger">*</span> Detail type</label>
                        <select class="form-select">
                            <option>Bank</option>
                        </select>
                    </div>
                    
                    <div class="mb-4 small text-muted">
                        Use Bank accounts to track all your current activity, including debit card transactions.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted"><span class="text-danger">*</span> Name</label>
                        <input type="text" name="account_name" class="form-control" value="{{ old('account_name') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Number</label>
                        <input type="text" name="account_number" class="form-control" value="{{ old('account_number') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Parent account</label>
                        <select class="form-select">
                            <option>None selected</option>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Balance</label>
                            <input type="number" name="opening_balance" class="form-control" step="0.01" value="{{ old('opening_balance', 0) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">as of</label>
                            <div class="input-group">
                                <input type="date" class="form-control">
                                <span class="input-group-text bg-white"><i class="ph ph-calendar"></i></span>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">IFSC Code (Auto-fetch)</label>
                            <input type="text" name="ifsc_code" id="ifsc_code" class="form-control" value="{{ old('ifsc_code') }}">
                            <span id="ifsc_status" class="small mt-1 d-block"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Branch</label>
                            <input type="text" name="branch" id="branch" class="form-control" value="{{ old('branch') }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Bank name</label>
                        <input type="text" name="bank_name" id="bank_name" class="form-control" value="{{ old('bank_name') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Bank account</label>
                        <input type="text" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Address</label>
                        <input type="text" class="form-control" id="address" name="address">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="{{ route('bank-accounts.index') }}" class="btn btn-light border px-4">Close</a>
                        <button type="submit" class="btn btn-primary px-4">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
    .ck-editor__editable_inline {
        min-height: 150px;
        resize: vertical;
        overflow: auto;
    }
    .ck.ck-powered-by {
        display: none !important;
    }
</style>
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    ClassicEditor
        .create(document.querySelector('#description'), {
            toolbar: [ 'heading', '|', 'bold', 'italic', 'bulletedList', 'numberedList', 'blockQuote', 'link' ]
        })
        .catch(error => {
            console.error(error);
        });
    const ifscInput = document.getElementById('ifsc_code');
    const bankNameInput = document.getElementById('bank_name');
    const branchInput = document.getElementById('branch');
    const addressInput = document.getElementById('address');
    const statusSpan = document.getElementById('ifsc_status');
    let debounceTimer;

    function fetchBankDetails(ifsc) {
        ifsc = ifsc.trim().toUpperCase();
        
        if (ifsc.length !== 11) {
            statusSpan.textContent = '';
            statusSpan.className = 'small mt-1 d-block';
            return;
        }

        const ifscRegex = /^[A-Z]{4}0[A-Z0-9]{6}$/;
        if (!ifscRegex.test(ifsc)) {
            statusSpan.textContent = 'Invalid IFSC format.';
            statusSpan.className = 'small mt-1 d-block text-danger';
            return;
        }

        statusSpan.textContent = 'Fetching bank details...';
        statusSpan.className = 'small mt-1 d-block text-info';

        fetch(`/api/bank-details/${ifsc}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.data.bank_name) {
                        bankNameInput.value = data.data.bank_name;
                    }
                    if (data.data.branch) {
                        branchInput.value = data.data.branch;
                    }
                    if (data.data.address && addressInput) {
                        addressInput.value = data.data.address;
                    }
                    statusSpan.textContent = 'Bank details fetched successfully.';
                    statusSpan.className = 'small mt-1 d-block text-success';
                } else {
                    statusSpan.textContent = data.message || 'Could not fetch details.';
                    statusSpan.className = 'small mt-1 d-block text-danger';
                }
            })
            .catch(error => {
                statusSpan.textContent = 'Error connecting to API.';
                statusSpan.className = 'small mt-1 d-block text-danger';
            });
    }

    ifscInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetchBankDetails(this.value);
        }, 500);
    });

    if (ifscInput.value) {
        fetchBankDetails(ifscInput.value);
    }
});
</script>
@endpush
@endsection
