@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="journalVoucher()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Edit Draft Journal Voucher</h2>
        <a href="{{ route('journal-vouchers.index') }}" class="btn btn-light shadow-sm">
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

    <form action="{{ route('journal-vouchers.update', $voucher->id) }}" method="POST" id="jvForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="action" id="formAction" value="draft">

        <div class="card shadow-sm border-0 mb-4">
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
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Master Narration {!! $settings->mandatory_narration ? '<span class="text-danger">*</span>' : '' !!}</label>
                        <input type="text" name="narration" class="form-control" value="{{ old('narration', $voucher->narration) }}" {{ $settings->mandatory_narration ? 'required' : '' }}>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th width="35%">Ledger <span class="text-danger">*</span></th>
                                <th width="25%">Line Narration</th>
                                <th width="15%">Debit (Dr)</th>
                                <th width="15%">Credit (Cr)</th>
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
                                                <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" :name="`lines[${index}][narration]`" class="form-control form-control-sm" x-model="line.narration">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" :name="`lines[${index}][debit]`" class="form-control text-end" x-model="line.debit" @input="line.credit = line.debit > 0 ? '' : line.credit">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" :name="`lines[${index}][credit]`" class="form-control text-end" x-model="line.credit" @input="line.debit = line.credit > 0 ? '' : line.debit">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" @click="removeLine(index)" :disabled="lines.length <= 2">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="bg-light">
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addLine">
                                        <i class="ph ph-plus"></i> Add Line
                                    </button>
                                </td>
                            </tr>
                            <tr class="fw-bold">
                                <td colspan="2" class="text-end">Totals:</td>
                                <td class="text-end text-primary" x-text="formatCurrency(totalDebit)"></td>
                                <td class="text-end text-primary" x-text="formatCurrency(totalCredit)"></td>
                                <td></td>
                            </tr>
                            <tr class="fw-bold">
                                <td colspan="2" class="text-end">Difference:</td>
                                <td colspan="2" class="text-center" :class="{'text-success': difference === 0, 'text-danger': difference !== 0}">
                                    <span x-text="formatCurrency(Math.abs(difference))"></span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
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

<!-- Add Alpine.js for dynamic form handling -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    function submitForm(action) {
        document.getElementById('formAction').value = action;
        
        if (action === 'post') {
            if (!confirm('Are you sure you want to post this Journal Voucher? This will update accounting balances.')) {
                return;
            }
        }
        
        document.getElementById('jvForm').submit();
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('journalVoucher', () => ({
            lines: {!! json_encode(old('lines', $voucher->draft_data['lines'] ?? [
                ['ledger_id' => '', 'narration' => '', 'debit' => '', 'credit' => ''],
                ['ledger_id' => '', 'narration' => '', 'debit' => '', 'credit' => '']
            ])) !!},
            addLine() {
                this.lines.push({ ledger_id: '', narration: '', debit: '', credit: '' });
            },
            removeLine(index) {
                if (this.lines.length > 2) {
                    this.lines.splice(index, 1);
                }
            },
            get totalDebit() {
                return this.lines.reduce((sum, line) => sum + (parseFloat(line.debit) || 0), 0);
            },
            get totalCredit() {
                return this.lines.reduce((sum, line) => sum + (parseFloat(line.credit) || 0), 0);
            },
            get difference() {
                return this.totalDebit - this.totalCredit;
            },
            get isValid() {
                return this.totalDebit > 0 && this.totalCredit > 0 && this.difference === 0;
            },
            formatCurrency(value) {
                return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(value);
            }
        }));
    });
</script>
@endsection
