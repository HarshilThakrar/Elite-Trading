@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Accounting Settings</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('accounting-settings.update') }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white pt-3 pb-0 border-bottom-0">
                <ul class="nav nav-tabs border-bottom" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">General</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="numbering-tab" data-bs-toggle="tab" data-bs-target="#numbering" type="button" role="tab">Voucher Numbering</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="posting-tab" data-bs-toggle="tab" data-bs-target="#posting" type="button" role="tab">Posting Rules</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rounding-tab" data-bs-toggle="tab" data-bs-target="#rounding" type="button" role="tab">Rounding & Precision</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledger" type="button" role="tab">Ledger Rules</button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body p-4">
                <div class="tab-content" id="settingsTabsContent">
                    
                    <!-- General Tab -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Base Currency <span class="text-danger">*</span></label>
                                <input type="text" name="base_currency" class="form-control" value="{{ old('base_currency', $settings->base_currency) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Currency Symbol <span class="text-danger">*</span></label>
                                <input type="text" name="currency_symbol" class="form-control" value="{{ old('currency_symbol', $settings->currency_symbol) }}" required>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" name="mandatory_narration" id="mandatory_narration" value="1" {{ old('mandatory_narration', $settings->mandatory_narration) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="mandatory_narration">Mandatory Narration on Vouchers</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Voucher Numbering Tab -->
                    <div class="tab-pane fade" id="numbering" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Voucher Type</th>
                                        <th>Prefix</th>
                                        <th>Start No</th>
                                        <th>Padding Length</th>
                                        <th>FY Wise Numbering</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $voucherTypes = ['Sales', 'Purchase', 'Payment', 'Receipt', 'Contra', 'Journal', 'Debit Note', 'Credit Note'];
                                        $numbering = $settings->voucher_numbering ?? [];
                                    @endphp
                                    @foreach($voucherTypes as $type)
                                        @php
                                            $config = $numbering[$type] ?? ['prefix' => '', 'start' => 1, 'padding' => 4, 'fy_wise' => true];
                                        @endphp
                                        <tr>
                                            <td class="fw-bold">{{ $type }}</td>
                                            <td>
                                                <input type="text" name="voucher_numbering[{{ $type }}][prefix]" class="form-control form-control-sm" value="{{ $config['prefix'] ?? '' }}">
                                            </td>
                                            <td>
                                                <input type="number" name="voucher_numbering[{{ $type }}][start]" class="form-control form-control-sm" value="{{ $config['start'] ?? 1 }}" min="1">
                                            </td>
                                            <td>
                                                <input type="number" name="voucher_numbering[{{ $type }}][padding]" class="form-control form-control-sm" value="{{ $config['padding'] ?? 4 }}" min="1" max="10">
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-inline-block">
                                                    <input class="form-check-input" type="checkbox" name="voucher_numbering[{{ $type }}][fy_wise]" value="1" {{ ($config['fy_wise'] ?? true) ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <small class="text-muted d-block mt-2"><i class="ph ph-info"></i> Note: Numbering changes will only affect newly generated vouchers. Historical voucher numbers will never be modified.</small>
                        </div>
                    </div>

                    <!-- Posting Rules Tab -->
                    <div class="tab-pane fade" id="posting" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="allow_backdated" id="allow_backdated" value="1" {{ old('allow_backdated', $settings->allow_backdated) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="allow_backdated">Allow Backdated Entries</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Max Backdate Days (0 = unlimited within open FY)</label>
                                    <input type="number" name="max_backdate_days" class="form-control form-control-sm w-50" value="{{ old('max_backdate_days', $settings->max_backdate_days) }}" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="allow_future_dated" id="allow_future_dated" value="1" {{ old('allow_future_dated', $settings->allow_future_dated) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="allow_future_dated">Allow Future-Dated Entries</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Max Future-Date Days (0 = unlimited within open FY)</label>
                                    <input type="number" name="max_future_days" class="form-control form-control-sm w-50" value="{{ old('max_future_days', $settings->max_future_days) }}" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="allow_voucher_editing" id="allow_voucher_editing" value="1" {{ old('allow_voucher_editing', $settings->allow_voucher_editing) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="allow_voucher_editing">Allow editing of posted vouchers</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="allow_voucher_cancellation" id="allow_voucher_cancellation" value="1" {{ old('allow_voucher_cancellation', $settings->allow_voucher_cancellation) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="allow_voucher_cancellation">Allow cancellation of posted vouchers</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rounding & Precision Tab -->
                    <div class="tab-pane fade" id="rounding" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Amount Decimal Places <span class="text-danger">*</span></label>
                                <input type="number" name="amount_decimals" class="form-control" value="{{ old('amount_decimals', $settings->amount_decimals) }}" min="0" max="4" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Quantity Decimal Places <span class="text-danger">*</span></label>
                                <input type="number" name="qty_decimals" class="form-control" value="{{ old('qty_decimals', $settings->qty_decimals) }}" min="0" max="4" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Rate Decimal Places <span class="text-danger">*</span></label>
                                <input type="number" name="rate_decimals" class="form-control" value="{{ old('rate_decimals', $settings->rate_decimals) }}" min="0" max="4" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Global Rounding Method <span class="text-danger">*</span></label>
                                <select name="rounding_method" class="form-select">
                                    <option value="Standard" {{ old('rounding_method', $settings->rounding_method) == 'Standard' ? 'selected' : '' }}>Standard (Nearest)</option>
                                    <option value="Up" {{ old('rounding_method', $settings->rounding_method) == 'Up' ? 'selected' : '' }}>Round Up</option>
                                    <option value="Down" {{ old('rounding_method', $settings->rounding_method) == 'Down' ? 'selected' : '' }}>Round Down</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Ledger Rules Tab -->
                    <div class="tab-pane fade" id="ledger" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="prevent_inactive_ledger_posting" id="prevent_inactive_ledger_posting" value="1" {{ old('prevent_inactive_ledger_posting', $settings->prevent_inactive_ledger_posting) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="prevent_inactive_ledger_posting">Prevent posting to Inactive Ledgers</label>
                                </div>
                                <small class="text-muted d-block ms-4 mt-1">If enabled, inactive ledgers will not appear in selection dropdowns for new transactions.</small>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            
            <div class="card-footer bg-light p-3 text-end">
                <button type="submit" class="btn btn-primary px-4"><i class="ph ph-floppy-disk"></i> Save All Settings</button>
            </div>
        </div>
    </form>
</div>
@endsection
