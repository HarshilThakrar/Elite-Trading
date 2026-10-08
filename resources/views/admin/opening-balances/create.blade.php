@extends('layouts.app')

@section('title', 'Add Opening Balance')
@section('header_title', 'Add Opening Balance')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Add Opening Balance</h2>
        <a href="{{ route('opening-balances.index', ['financial_year_id' => $selectedFyId]) }}" class="btn btn-light border shadow-sm">
            <i class="ph ph-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            @if($currentFy && $currentFy->is_closed)
                <div class="alert alert-danger">
                    <i class="ph ph-warning-circle"></i> The selected Financial Year is closed. You cannot add opening balances.
                </div>
            @else
            <form action="{{ route('opening-balances.store') }}" method="POST">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Financial Year <span class="text-danger">*</span></label>
                        <select name="financial_year_id" id="financial_year_id" class="form-select select2" required>
                            @foreach($financialYears as $fy)
                                <option value="{{ $fy->id }}" 
                                    data-start="{{ \Carbon\Carbon::parse($fy->start_date)->format('Y-m-d') }}"
                                    data-end="{{ \Carbon\Carbon::parse($fy->end_date)->format('Y-m-d') }}"
                                    {{ $selectedFyId == $fy->id ? 'selected' : '' }}
                                    {{ $fy->is_closed ? 'disabled' : '' }}>
                                    {{ \Carbon\Carbon::parse($fy->start_date)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($fy->end_date)->format('d-M-Y') }}
                                    {{ $fy->is_closed ? '(Closed)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Opening Date <span class="text-danger">*</span></label>
                        <input type="date" name="opening_date" id="opening_date" class="form-control" value="{{ old('opening_date') }}" required>
                        <small class="text-muted">Must be within the selected Financial Year.</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Ledger <span class="text-danger">*</span></label>
                        <select name="ledger_id" id="ledger_id" class="form-select select2" required>
                            <option value="">-- Select Ledger --</option>
                            @foreach($ledgers as $ledger)
                                @php
                                    $nature = $ledger->accountGroup->nature ?? 'Assets';
                                    $normalBalance = \App\Models\AccountGroup::deriveNormalBalance($nature);
                                @endphp
                                <option value="{{ $ledger->id }}" data-normal="{{ $normalBalance }}" {{ old('ledger_id') == $ledger->id ? 'selected' : '' }}>
                                    {{ $ledger->name }} ({{ $ledger->ledger_code }}) - Normal: {{ $normalBalance }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0" value="{{ old('amount') }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Dr / Cr <span class="text-danger">*</span></label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="Dr" {{ old('type') == 'Dr' ? 'selected' : '' }}>Debit (Dr)</option>
                            <option value="Cr" {{ old('type') == 'Cr' ? 'selected' : '' }}>Credit (Cr)</option>
                        </select>
                        <small id="normalBalanceWarning" class="text-warning fw-bold d-none mt-1">
                            <i class="ph ph-warning"></i> Opposite of normal balance.
                        </small>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Narration</label>
                        <input type="text" name="narration" class="form-control" value="{{ old('narration') }}" placeholder="Opening balance details...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reference</label>
                        <input type="text" name="reference" class="form-control" value="{{ old('reference') }}">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="ph ph-check-circle"></i> Save Opening Balance</button>
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
        let selectedOption = $('#financial_year_id option:selected');
        let start = selectedOption.data('start');
        let end = selectedOption.data('end');
        
        let dateInput = $('#opening_date');
        dateInput.attr('min', start);
        dateInput.attr('max', end);
        
        if (!dateInput.val() || dateInput.val() < start || dateInput.val() > end) {
            dateInput.val(start);
        }
    }
    
    function checkNormalBalance() {
        let selectedLedger = $('#ledger_id option:selected');
        let normal = selectedLedger.data('normal');
        let selectedType = $('#type').val();
        
        if (normal && selectedType && normal !== selectedType) {
            $('#normalBalanceWarning').removeClass('d-none');
        } else {
            $('#normalBalanceWarning').addClass('d-none');
        }
    }

    $('#financial_year_id').change(updateDateConstraints);
    $('#ledger_id').change(checkNormalBalance);
    $('#type').change(checkNormalBalance);
    
    // Init
    updateDateConstraints();
    checkNormalBalance();
});
</script>
@endpush
