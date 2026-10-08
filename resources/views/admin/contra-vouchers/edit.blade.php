@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="contraVoucher()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Edit Draft Contra Voucher</h2>
        <a href="{{ route('contra-vouchers.index') }}" class="btn btn-light shadow-sm">
            <i class="ph ph-arrow-left"></i> Back to List
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('contra-vouchers.update', $voucher->id) }}" method="POST" id="cvForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="action" id="formAction" value="draft">

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Transfer Details</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Voucher No</label>
                        <input type="text" class="form-control bg-light" value="{{ $voucher->voucher_number }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" value="{{ old('date', $voucher->date->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Transfer Mode <span class="text-danger">*</span></label>
                        <select name="transfer_mode" class="form-select" x-model="transferMode" required>
                            <option value="">Select Mode...</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cash Deposit">Cash Deposit</option>
                            <option value="Cash Withdrawal">Cash Withdrawal</option>
                            <option value="Internal Transfer">Internal Transfer</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control text-end" x-model="amount" required>
                    </div>
                </div>
                
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-danger">Transfer From (Source / Cr) <span class="text-danger">*</span></label>
                        <select name="source_ledger_id" class="form-select" x-model="sourceLedgerId" required>
                            <option value="">Select Cash/Bank Ledger...</option>
                            @foreach($cashBankLedgers as $ledger)
                                <option value="{{ $ledger->id }}" x-show="destinationLedgerId != {{ $ledger->id }}">{{ $ledger->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 align-self-end text-center pb-2">
                        <i class="ph ph-arrow-right fs-3 text-muted"></i>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-success">Transfer To (Destination / Dr) <span class="text-danger">*</span></label>
                        <select name="destination_ledger_id" class="form-select" x-model="destinationLedgerId" required>
                            <option value="">Select Cash/Bank Ledger...</option>
                            @foreach($cashBankLedgers as $ledger)
                                <option value="{{ $ledger->id }}" x-show="sourceLedgerId != {{ $ledger->id }}">{{ $ledger->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <template x-if="transferMode !== 'Cash Deposit' && transferMode !== 'Cash Withdrawal' && transferMode !== ''">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Reference Number</label>
                            <input type="text" name="reference_number" class="form-control" x-model="referenceNumber">
                        </div>
                    </template>
                    <template x-if="transferMode === 'Cheque' || transferMode === 'Bank Transfer'">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Reference / Cheque Date</label>
                            <input type="date" name="reference_date" class="form-control" x-model="referenceDate">
                        </div>
                    </template>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Narration {!! $settings->mandatory_narration ? '<span class="text-danger">*</span>' : '' !!}</label>
                        <input type="text" name="narration" class="form-control" value="{{ old('narration', $voucher->narration) }}" {{ $settings->mandatory_narration ? 'required' : '' }}>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="button" class="btn btn-light px-4 me-2" onclick="submitForm('draft')">
                        <i class="ph ph-floppy-disk"></i> Update Draft
                    </button>
                    <button type="button" class="btn btn-primary px-4" :disabled="!isValid" onclick="submitForm('post')">
                        <i class="ph ph-paper-plane-right"></i> Update & Post
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    function submitForm(action) {
        document.getElementById('formAction').value = action;
        
        if (action === 'post') {
            if (!confirm('Are you sure you want to post this Contra Voucher? This will permanently update accounting balances.')) {
                return;
            }
        }
        
        document.getElementById('cvForm').submit();
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('contraVoucher', () => ({
            transferMode: '{{ old("transfer_mode", $voucher->draft_data["metadata"]["transfer_mode"] ?? "") }}',
            sourceLedgerId: '{{ old("source_ledger_id", $voucher->draft_data["metadata"]["source_ledger_id"] ?? "") }}',
            destinationLedgerId: '{{ old("destination_ledger_id", $voucher->draft_data["metadata"]["destination_ledger_id"] ?? "") }}',
            amount: '{{ old("amount", $voucher->draft_data["metadata"]["amount"] ?? "") }}',
            referenceNumber: '{{ old("reference_number", $voucher->draft_data["metadata"]["reference_number"] ?? "") }}',
            referenceDate: '{{ old("reference_date", $voucher->draft_data["metadata"]["reference_date"] ?? "") }}',
            get isValid() {
                return parseFloat(this.amount) > 0 && this.sourceLedgerId !== '' && this.destinationLedgerId !== '' && (this.sourceLedgerId !== this.destinationLedgerId);
            }
        }));
    });
</script>
@endsection
