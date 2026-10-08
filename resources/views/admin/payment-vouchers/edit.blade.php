@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="paymentVoucher()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Edit Draft Payment Voucher</h2>
        <a href="{{ route('payment-vouchers.index') }}" class="btn btn-light shadow-sm">
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

    <form action="{{ route('payment-vouchers.update', $voucher->id) }}" method="POST" id="pvForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="action" id="formAction" value="draft">

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Payment Details</h5>
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
                        <label class="form-label fw-bold">Payment Mode <span class="text-danger">*</span></label>
                        <select name="payment_mode" class="form-select" x-model="paymentMode" required>
                            <option value="">Select Mode...</option>
                            <option value="Cash">Cash</option>
                            <option value="Bank">Bank</option>
                            <option value="UPI">UPI</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Payment From (Cr) <span class="text-danger">*</span></label>
                        <select name="payment_from_id" class="form-select" x-model="paymentFromId" required>
                            <option value="">Select Cash/Bank Ledger...</option>
                            @foreach($cashBankLedgers as $ledger)
                                <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <template x-if="paymentMode !== 'Cash' && paymentMode !== ''">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Reference Type</label>
                            <select name="reference_type" class="form-select" x-model="referenceType">
                                <option value="">None</option>
                                <option value="UTR">UTR / IMPS / NEFT</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Transaction ID">Transaction ID</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </template>
                    <template x-if="paymentMode !== 'Cash' && paymentMode !== ''">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Reference Number</label>
                            <input type="text" name="reference_number" class="form-control" x-model="referenceNumber">
                        </div>
                    </template>
                    <template x-if="paymentMode === 'Cheque'">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Cheque Date</label>
                            <input type="date" name="reference_date" class="form-control" x-model="referenceDate">
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Payee / Expense Details (Dr)</h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th width="40%">Ledger <span class="text-danger">*</span></th>
                                <th width="30%">Line Narration</th>
                                <th width="20%">Amount (Dr) <span class="text-danger">*</span></th>
                                <th width="10%" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(line, index) in lines" :key="index">
                                <tr>
                                    <td>
                                        <select :name="`lines[${index}][ledger_id]`" class="form-select" x-model="line.ledger_id" required>
                                            <option value="">Select Ledger...</option>
                                            @foreach($ledgers as $ledger)
                                                <option value="{{ $ledger->id }}" x-show="paymentFromId != {{ $ledger->id }}">{{ $ledger->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" :name="`lines[${index}][narration]`" class="form-control form-control-sm" x-model="line.narration">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" :name="`lines[${index}][debit]`" class="form-control text-end" x-model="line.debit" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" @click="removeLine(index)" :disabled="lines.length <= 1">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="bg-light">
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addLine">
                                        <i class="ph ph-plus"></i> Add Line
                                    </button>
                                </td>
                            </tr>
                            <tr class="fw-bold">
                                <td colspan="2" class="text-end">Total Payment Amount:</td>
                                <td class="text-end text-primary" x-text="formatCurrency(totalAmount)"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Master Narration {!! $settings->mandatory_narration ? '<span class="text-danger">*</span>' : '' !!}</label>
                        <input type="text" name="narration" class="form-control" value="{{ old('narration', $voucher->narration) }}" {{ $settings->mandatory_narration ? 'required' : '' }}>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="button" class="btn btn-light px-4 me-2" onclick="submitForm('draft')">
                        <i class="ph ph-floppy-disk"></i> Update Draft
                    </button>
                    <button type="button" class="btn btn-primary px-4" onclick="submitForm('post')">
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
            if (!confirm('Are you sure you want to post this Payment Voucher? This will permanently update accounting balances.')) {
                return;
            }
        }
        
        document.getElementById('pvForm').submit();
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('paymentVoucher', () => ({
            paymentMode: '{{ old("payment_mode", $voucher->draft_data["metadata"]["payment_mode"] ?? "") }}',
            paymentFromId: '{{ old("payment_from_id", $voucher->draft_data["metadata"]["payment_from_id"] ?? "") }}',
            referenceType: '{{ old("reference_type", $voucher->draft_data["metadata"]["reference_type"] ?? "") }}',
            referenceNumber: '{{ old("reference_number", $voucher->draft_data["metadata"]["reference_number"] ?? "") }}',
            referenceDate: '{{ old("reference_date", $voucher->draft_data["metadata"]["reference_date"] ?? "") }}',
            lines: {!! json_encode(old('lines', $voucher->draft_data['lines'] ?? [
                ['ledger_id' => '', 'narration' => '', 'debit' => '']
            ])) !!},
            addLine() {
                this.lines.push({ ledger_id: '', narration: '', debit: '' });
            },
            removeLine(index) {
                if (this.lines.length > 1) {
                    this.lines.splice(index, 1);
                }
            },
            get totalAmount() {
                return this.lines.reduce((sum, line) => sum + (parseFloat(line.debit) || 0), 0);
            },
            get isValid() {
                return this.totalAmount > 0 && this.paymentFromId !== '';
            },
            formatCurrency(value) {
                return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(value);
            }
        }));
    });
</script>
@endsection
