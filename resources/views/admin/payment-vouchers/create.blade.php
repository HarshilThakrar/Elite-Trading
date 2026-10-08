@extends('layouts.app')

@section('title', 'Create Payment Voucher - Demo ERP')
@section('header_title', 'Create Payment Voucher')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-0 text-gray-800">Create Payment Voucher</h2>
            <p class="text-muted small mb-0">Record payments to vendors, expenses, or other parties</p>
        </div>
        <a href="{{ route('payment-vouchers.index') }}" class="btn btn-light shadow-sm">
            <i class="ph ph-arrow-left me-1"></i> Back to List
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger shadow-sm border-0 mb-4">
            <div class="fw-bold mb-1"><i class="ph ph-warning-octagon me-1"></i> Please check the following errors:</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4">
            <i class="ph ph-warning-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('payment-vouchers.store') }}" method="POST" id="pvForm">
        @csrf
        <input type="hidden" name="action" id="formAction" value="draft">

        <!-- 1. Payment Details Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold text-primary"><i class="ph ph-wallet me-2"></i> Payment Source & Mode</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 mb-2">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Voucher No</label>
                        <input type="text" class="form-control bg-light text-muted" value="Auto-generated on Post" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="voucherDate" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Payment Mode <span class="text-danger">*</span></label>
                        <select name="payment_mode" id="paymentMode" class="form-select" required onchange="handlePaymentModeChange()">
                            <option value="">Select Mode...</option>
                            <option value="Cash" {{ old('payment_mode') == 'Cash' ? 'selected' : '' }}>Cash</option>
                            <option value="Bank" {{ old('payment_mode') == 'Bank' ? 'selected' : '' }}>Bank (NEFT / RTGS / IMPS)</option>
                            <option value="UPI" {{ old('payment_mode') == 'UPI' ? 'selected' : '' }}>UPI</option>
                            <option value="Cheque" {{ old('payment_mode') == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                            <option value="Other" {{ old('payment_mode') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Payment From (Cr) <span class="text-danger">*</span></label>
                        <select name="payment_from_id" id="paymentFromId" class="form-select" required onchange="handlePaymentFromChange()">
                            <option value="">Select Cash/Bank Ledger...</option>
                            @foreach($cashBankLedgers as $ledger)
                                <option value="{{ $ledger->id }}" {{ old('payment_from_id') == $ledger->id ? 'selected' : '' }}>
                                    {{ $ledger->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted">Source account to credit</div>
                    </div>
                </div>

                <!-- Dynamic Reference Details (Hidden for Cash by default) -->
                <div class="row g-3 mt-1" id="referenceFieldsRow" style="display: none;">
                    <div class="col-md-4" id="referenceTypeCol">
                        <label class="form-label fw-bold">Reference Type</label>
                        <select name="reference_type" id="referenceType" class="form-select">
                            <option value="">None / NA</option>
                            <option value="UTR" {{ old('reference_type') == 'UTR' ? 'selected' : '' }}>UTR / IMPS / NEFT</option>
                            <option value="Cheque" {{ old('reference_type') == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                            <option value="Transaction ID" {{ old('reference_type') == 'Transaction ID' ? 'selected' : '' }}>Transaction ID / UPI Ref</option>
                            <option value="Other" {{ old('reference_type') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4" id="referenceNumberCol">
                        <label class="form-label fw-bold">Reference / Cheque No</label>
                        <input type="text" name="reference_number" id="referenceNumber" class="form-control" value="{{ old('reference_number') }}" placeholder="e.g. UTR / Cheque / Txn Number">
                    </div>
                    <div class="col-md-4" id="referenceDateCol">
                        <label class="form-label fw-bold">Reference / Cheque Date</label>
                        <input type="date" name="reference_date" id="referenceDate" class="form-control" value="{{ old('reference_date') }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Payee / Expense Details (Dr) Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-primary"><i class="ph ph-receipt me-2"></i> Payee / Expense Details (Dr)</h5>
                <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" onclick="addNewLine()">
                    <i class="ph ph-plus me-1"></i> Add Another Row
                </button>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="voucherLinesTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40%;">Payee / Expense Ledger (Dr) <span class="text-danger">*</span></th>
                                <th style="width: 30%;">Line Narration</th>
                                <th style="width: 20%;" class="text-end">Amount (₹ Dr) <span class="text-danger">*</span></th>
                                <th style="width: 10%;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="voucherLinesBody">
                            @php
                                $oldLines = old('lines', [
                                    ['ledger_id' => '', 'narration' => '', 'debit' => '']
                                ]);
                            @endphp

                            @foreach($oldLines as $index => $line)
                            <tr class="voucher-line-row">
                                <td>
                                    <select name="lines[{{ $index }}][ledger_id]" class="form-select line-ledger-select" required onchange="validateLineLedger(this); recalculateTotal();">
                                        <option value="">Select Ledger...</option>
                                        @foreach($ledgers as $ledger)
                                            <option value="{{ $ledger->id }}" {{ (isset($line['ledger_id']) && $line['ledger_id'] == $ledger->id) ? 'selected' : '' }}>
                                                {{ $ledger->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="lines[{{ $index }}][narration]" class="form-control form-control-sm line-narration-input" value="{{ $line['narration'] ?? '' }}" placeholder="Line Narration (optional)">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0.01" name="lines[{{ $index }}][debit]" class="form-control text-end line-debit-input" value="{{ $line['debit'] ?? '' }}" placeholder="0.00" required oninput="recalculateTotal();">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line" onclick="removeLineRow(this)" title="Remove Row" {{ count($oldLines) <= 1 ? 'disabled' : '' }}>
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4">
                                    <button type="button" class="btn btn-sm btn-primary-custom" onclick="addNewLine()">
                                        <i class="ph ph-plus me-1"></i> Add Line
                                    </button>
                                </td>
                            </tr>
                            <tr class="fw-bold fs-6">
                                <td colspan="2" class="text-end text-dark">Total Payment Amount:</td>
                                <td class="text-end text-primary" id="totalAmountDisplay">₹ 0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Master Narration -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">
                            Master Narration {!! $settings->mandatory_narration ? '<span class="text-danger">*</span>' : '' !!}
                        </label>
                        <input type="text" name="narration" id="masterNarration" class="form-control" value="{{ old('narration') }}" placeholder="Enter purpose / master remarks for this payment..." {{ $settings->mandatory_narration ? 'required' : '' }}>
                        <div class="form-text small text-muted">Overall narration printed on voucher register and reports</div>
                    </div>
                </div>

                <!-- Action Buttons: Save Draft & Save & Post -->
                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                    <a href="{{ route('payment-vouchers.index') }}" class="btn btn-light shadow-sm">
                        <i class="ph ph-x me-1"></i> Cancel
                    </a>
                    <div>
                        <button type="button" class="btn btn-outline-secondary px-4 py-2 me-2 shadow-sm fw-semibold" id="btnSaveDraft" onclick="submitVoucherForm('draft')">
                            <i class="ph ph-floppy-disk me-1"></i> Save Draft
                        </button>
                        <button type="button" class="btn btn-primary px-4 py-2 shadow-sm fw-semibold" id="btnSavePost" onclick="submitVoucherForm('post')">
                            <i class="ph ph-paper-plane-right me-1"></i> Save & Post
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Template for cloning rows in JavaScript -->
<template id="lineRowTemplate">
    <tr class="voucher-line-row">
        <td>
            <select name="lines[INDEX][ledger_id]" class="form-select line-ledger-select" required onchange="validateLineLedger(this); recalculateTotal();">
                <option value="">Select Ledger...</option>
                @foreach($ledgers as $ledger)
                    <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="text" name="lines[INDEX][narration]" class="form-control form-control-sm line-narration-input" placeholder="Line Narration (optional)">
        </td>
        <td>
            <input type="number" step="0.01" min="0.01" name="lines[INDEX][debit]" class="form-control text-end line-debit-input" placeholder="0.00" required oninput="recalculateTotal();">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line" onclick="removeLineRow(this)" title="Remove Row">
                <i class="ph ph-trash"></i>
            </button>
        </td>
    </tr>
</template>

@endsection

@push('scripts')
<script>
    let rowIndex = {{ count($oldLines) }};

    function handlePaymentModeChange() {
        const mode = document.getElementById('paymentMode').value;
        const refRow = document.getElementById('referenceFieldsRow');
        const refTypeCol = document.getElementById('referenceTypeCol');
        const refTypeSelect = document.getElementById('referenceType');
        const refNumberCol = document.getElementById('referenceNumberCol');
        const refDateCol = document.getElementById('referenceDateCol');

        if (!mode || mode === 'Cash') {
            refRow.style.display = 'none';
        } else if (mode === 'Cheque') {
            refRow.style.display = 'flex';
            refTypeSelect.value = 'Cheque';
            refDateCol.style.display = 'block';
        } else {
            refRow.style.display = 'flex';
            if (mode === 'Bank' && !refTypeSelect.value) refTypeSelect.value = 'UTR';
            if (mode === 'UPI' && !refTypeSelect.value) refTypeSelect.value = 'Transaction ID';
            refDateCol.style.display = 'block';
        }
    }

    function handlePaymentFromChange() {
        const sourceLedgerId = document.getElementById('paymentFromId').value;
        document.querySelectorAll('.line-ledger-select').forEach(select => {
            validateLineLedger(select);
        });
    }

    function validateLineLedger(selectElement) {
        const sourceLedgerId = document.getElementById('paymentFromId').value;
        if (sourceLedgerId && selectElement.value === sourceLedgerId) {
            alert('Cannot select the same ledger for Payment Source and Payee/Expense.');
            selectElement.value = '';
            selectElement.focus();
        }
    }

    function addNewLine() {
        const tbody = document.getElementById('voucherLinesBody');
        const template = document.getElementById('lineRowTemplate').innerHTML;
        const newHtml = template.replace(/INDEX/g, rowIndex++);
        tbody.insertAdjacentHTML('beforeend', newHtml);

        updateRemoveButtons();
        recalculateTotal();
    }

    function removeLineRow(button) {
        const tbody = document.getElementById('voucherLinesBody');
        const row = button.closest('tr');
        if (tbody.querySelectorAll('tr.voucher-line-row').length > 1) {
            row.remove();
            updateRemoveButtons();
            recalculateTotal();
        }
    }

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('#voucherLinesBody tr.voucher-line-row');
        const removeButtons = document.querySelectorAll('.btn-remove-line');
        if (rows.length <= 1) {
            removeButtons.forEach(btn => btn.disabled = true);
        } else {
            removeButtons.forEach(btn => btn.disabled = false);
        }
    }

    function recalculateTotal() {
        let total = 0;
        document.querySelectorAll('.line-debit-input').forEach(input => {
            const val = parseFloat(input.value) || 0;
            total += val;
        });

        const formatted = new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR'
        }).format(total);

        document.getElementById('totalAmountDisplay').innerText = formatted;
        return total;
    }

    function submitVoucherForm(action) {
        const form = document.getElementById('pvForm');
        document.getElementById('formAction').value = action;

        // 1. Validate Date
        const dateInput = document.getElementById('voucherDate');
        if (!dateInput.value) {
            alert('Please select a valid Voucher Date.');
            dateInput.focus();
            return;
        }

        // 2. Validate Payment Mode
        const modeSelect = document.getElementById('paymentMode');
        if (!modeSelect.value) {
            alert('Please select a Payment Mode (Cash, Bank, UPI, Cheque, or Other).');
            modeSelect.focus();
            return;
        }

        // 3. Validate Payment Source
        const sourceSelect = document.getElementById('paymentFromId');
        if (!sourceSelect.value) {
            alert('Please select a Payment Source Ledger (Cash/Bank account to credit).');
            sourceSelect.focus();
            return;
        }
        const sourceId = sourceSelect.value;

        // 4. Validate Debit Lines
        const rows = document.querySelectorAll('#voucherLinesBody tr.voucher-line-row');
        if (rows.length === 0) {
            alert('Please add at least one Payee / Expense line.');
            return;
        }

        let totalDebit = 0;
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const ledgerSelect = row.querySelector('.line-ledger-select');
            const debitInput = row.querySelector('.line-debit-input');

            const ledgerId = ledgerSelect ? ledgerSelect.value : '';
            const debit = debitInput ? parseFloat(debitInput.value) || 0 : 0;

            if (!ledgerId) {
                alert(`Row #${i + 1}: Please select a Payee / Expense Ledger.`);
                if (ledgerSelect) ledgerSelect.focus();
                return;
            }

            if (ledgerId === sourceId) {
                alert(`Row #${i + 1}: Cannot select the same ledger for Payment Source and Debit target.`);
                if (ledgerSelect) ledgerSelect.focus();
                return;
            }

            if (debit <= 0) {
                alert(`Row #${i + 1}: Please enter a valid amount greater than 0.`);
                if (debitInput) debitInput.focus();
                return;
            }

            totalDebit += debit;
        }

        if (totalDebit <= 0) {
            alert('Total payment amount must be greater than 0.');
            return;
        }

        // 5. Validate Master Narration if mandatory
        const narrationInput = document.getElementById('masterNarration');
        if (narrationInput && narrationInput.hasAttribute('required') && !narrationInput.value.trim()) {
            alert('Master Narration is mandatory according to Accounting Settings.');
            narrationInput.focus();
            return;
        }

        // 6. Confirm if Posting
        if (action === 'post') {
            const formattedTotal = new Intl.NumberFormat('en-IN', {
                style: 'currency',
                currency: 'INR'
            }).format(totalDebit);

            const confirmMsg = `Are you sure you want to Post this Payment Voucher for ${formattedTotal}?\n\nThis will permanently update accounting balances.`;
            if (!confirm(confirmMsg)) {
                return;
            }
        }

        // 7. Show loading state to prevent double submit
        const postBtn = document.getElementById('btnSavePost');
        const draftBtn = document.getElementById('btnSaveDraft');

        if (action === 'post') {
            postBtn.disabled = true;
            postBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Posting...';
            draftBtn.disabled = true;
        } else {
            draftBtn.disabled = true;
            draftBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Saving...';
            postBtn.disabled = true;
        }

        // 8. Submit Form
        form.submit();
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        handlePaymentModeChange();
        recalculateTotal();
        updateRemoveButtons();
    });
</script>
@endpush
