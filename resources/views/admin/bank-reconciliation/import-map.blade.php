@extends('layouts.app')

@section('title', 'Map CSV Columns')
@section('header_title', 'Map CSV Columns')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Map Columns: {{ $ledger->name }}</h2>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Map CSV Columns to System Fields</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="ph ph-info me-1"></i> Please map the columns from your uploaded CSV to the corresponding system fields. Transaction Date is required.
            </div>
            
            <form action="{{ route('bank-reconciliation.processImport', $ledger->id) }}" method="POST">
                @csrf
                <input type="hidden" name="file_path" value="{{ $path }}">
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>System Field</th>
                                    <th>CSV Column</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $fields = [
                                        'transaction_date' => 'Transaction Date *',
                                        'value_date' => 'Value Date',
                                        'description' => 'Description / Narration',
                                        'reference_number' => 'Ref No / Cheque No',
                                        'debit_amount' => 'Withdrawal (Debit)',
                                        'credit_amount' => 'Deposit (Credit)',
                                        'amount' => 'Amount (Single Column)',
                                        'running_balance' => 'Running Balance'
                                    ];
                                @endphp

                                @foreach($fields as $key => $label)
                                <tr>
                                    <td class="align-middle fw-bold {{ $key == 'transaction_date' ? 'text-primary' : '' }}">
                                        {{ $label }}
                                    </td>
                                    <td>
                                        <select name="mapping[{{ $key }}]" class="form-select" {{ $key == 'transaction_date' ? 'required' : '' }}>
                                            <option value="">-- Ignore / Not Present --</option>
                                            @foreach($headers as $index => $header)
                                                <option value="{{ $index }}">{{ $header }} (Column {{ $index + 1 }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6>Found Columns in CSV:</h6>
                                <ul>
                                    @foreach($headers as $index => $header)
                                        <li><strong>Col {{ $index + 1 }}:</strong> {{ $header }}</li>
                                    @endforeach
                                </ul>
                                <hr>
                                <p class="small text-muted mb-0">
                                    <strong>Tip:</strong> If your bank statement provides separate columns for Withdrawal and Deposit, map them to Debit and Credit. If it provides a single Amount column (positive for deposits, negative for withdrawals), map it to "Amount (Single Column)".
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('bank-reconciliation.import', $ledger->id) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success"><i class="ph ph-check-circle"></i> Process Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
