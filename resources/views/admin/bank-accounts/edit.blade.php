@extends('layouts.app')

@section('title', 'Edit Bank Account - Demo ERP')
@section('header_title', 'Edit Bank Account')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Edit Bank Account</h5>
    </div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('bank-accounts.update', $bankAccount) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Bank Name <span class="text-danger">*</span></label>
                    <input type="text" name="bank_name" id="bank_name" class="form-control" value="{{ old('bank_name', $bankAccount->bank_name) }}" required>
                </div>
                <div class="col-md-6">
                    <label>Account Name <span class="text-danger">*</span></label>
                    <input type="text" name="account_name" class="form-control" value="{{ old('account_name', $bankAccount->account_name) }}" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Account Number <span class="text-danger">*</span></label>
                    <input type="text" name="account_number" class="form-control" value="{{ old('account_number', $bankAccount->account_number) }}" required>
                </div>
                <div class="col-md-6">
                    <label>IFSC Code</label>
                    <input type="text" name="ifsc_code" id="ifsc_code" class="form-control" value="{{ old('ifsc_code', $bankAccount->ifsc_code) }}">
                    <span id="ifsc_status" class="small mt-1 d-block"></span>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Branch</label>
                    <input type="text" name="branch" id="branch" class="form-control" value="{{ old('branch', $bankAccount->branch) }}">
                </div>
            </div>
            <div class="text-end mt-3">
                <a href="{{ route('bank-accounts.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ifscInput = document.getElementById('ifsc_code');
    const bankNameInput = document.getElementById('bank_name');
    const branchInput = document.getElementById('branch');
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
                    statusSpan.textContent = 'Bank details fetched successfully.';
                    statusSpan.className = 'small mt-1 d-block text-success';
                } else {
                    statusSpan.textContent = data.message || 'Invalid or unavailable IFSC code.';
                    statusSpan.className = 'small mt-1 d-block text-danger';
                }
            })
            .catch(error => {
                console.error('Error fetching IFSC:', error);
                statusSpan.textContent = 'Could not fetch details. Please enter manually.';
                statusSpan.className = 'small mt-1 d-block text-warning';
            });
    }

    ifscInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetchBankDetails(this.value);
        }, 800);
    });

    ifscInput.addEventListener('blur', function() {
        clearTimeout(debounceTimer);
        fetchBankDetails(this.value);
    });
});
</script>
@endsection
