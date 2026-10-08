@extends('layouts.app')

@section('title', 'Edit Opening Balance')
@section('header_title', 'Edit Opening Balance')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Edit Opening Balance</h2>
        <a href="{{ route('opening-balances.index', ['financial_year_id' => $openingBalance->financial_year_id]) }}" class="btn btn-light border shadow-sm">
            <i class="ph ph-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            @if($openingBalance->financialYear->is_closed)
                <div class="alert alert-danger">
                    <i class="ph ph-warning-circle"></i> This Financial Year is closed. You cannot edit this opening balance.
                </div>
            @else
            <form action="{{ route('opening-balances.update', $openingBalance->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Financial Year</label>
                        <input type="text" class="form-control bg-light" value="{{ \Carbon\Carbon::parse($openingBalance->financialYear->start_date)->format('Y') }}-{{ \Carbon\Carbon::parse($openingBalance->financialYear->end_date)->format('y') }}" readonly>
                        <input type="hidden" id="financial_year_id" value="{{ $openingBalance->financial_year_id }}"
                            data-start="{{ \Carbon\Carbon::parse($openingBalance->financialYear->start_date)->format('Y-m-d') }}"
                            data-end="{{ \Carbon\Carbon::parse($openingBalance->financialYear->end_date)->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Opening Date <span class="text-danger">*</span></label>
                        <input type="date" name="opening_date" id="opening_date" class="form-control" value="{{ old('opening_date', $openingBalance->opening_date->format('Y-m-d')) }}" required>
                        <small class="text-muted">Must be within the selected Financial Year.</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Ledger</label>
                        @php
                            $nature = $openingBalance->ledger->accountGroup->nature ?? 'Assets';
                            $normalBalance = \App\Models\AccountGroup::deriveNormalBalance($nature);
                        @endphp
                        <input type="text" class="form-control bg-light" value="{{ $openingBalance->ledger->name }} ({{ $openingBalance->ledger->ledger_code }})" readonly>
                        <input type="hidden" id="ledger_id" data-normal="{{ $normalBalance }}">
                        <small class="text-muted">Normal Balance: {{ $normalBalance }}</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0" value="{{ old('amount', $openingBalance->amount) }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Dr / Cr <span class="text-danger">*</span></label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="Dr" {{ old('type', $openingBalance->type) == 'Dr' ? 'selected' : '' }}>Debit (Dr)</option>
                            <option value="Cr" {{ old('type', $openingBalance->type) == 'Cr' ? 'selected' : '' }}>Credit (Cr)</option>
                        </select>
                        <small id="normalBalanceWarning" class="text-warning fw-bold d-none mt-1">
                            <i class="ph ph-warning"></i> Opposite of normal balance.
                        </small>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Narration</label>
                        <input type="text" name="narration" class="form-control" value="{{ old('narration', $openingBalance->narration) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reference</label>
                        <input type="text" name="reference" class="form-control" value="{{ old('reference', $openingBalance->reference) }}">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="ph ph-check-circle"></i> Update Opening Balance</button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function updateDateConstraints() {
        let selectedOption = $('#financial_year_id');
        let start = selectedOption.data('start');
        let end = selectedOption.data('end');
        
        let dateInput = $('#opening_date');
        dateInput.attr('min', start);
        dateInput.attr('max', end);
    }
    
    function checkNormalBalance() {
        let normal = $('#ledger_id').data('normal');
        let selectedType = $('#type').val();
        
        if (normal && selectedType && normal !== selectedType) {
            $('#normalBalanceWarning').removeClass('d-none');
        } else {
            $('#normalBalanceWarning').addClass('d-none');
        }
    }

    $('#type').change(checkNormalBalance);
    
    // Init
    updateDateConstraints();
    checkNormalBalance();
});
</script>
@endpush
