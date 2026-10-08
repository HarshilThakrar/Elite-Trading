@extends('layouts.app')

@section('title', 'Import Bank Statement')
@section('header_title', 'Import Bank Statement')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Import Statement: {{ $ledger->name }}</h2>
        <a href="{{ route('bank-reconciliation.index', ['bank_ledger_id' => $ledger->id]) }}" class="btn btn-light border shadow-sm">
            <i class="ph ph-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Upload CSV File</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('bank-reconciliation.import', $ledger->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="csv_file" class="form-label">Bank Statement CSV</label>
                    <input class="form-control" type="file" id="csv_file" name="csv_file" accept=".csv,.txt" required>
                    <small class="text-muted d-block mt-2">
                        Upload your bank statement in CSV format. You will map the columns on the next screen.
                    </small>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="ph ph-upload-simple"></i> Upload and Map Columns</button>
            </form>
        </div>
    </div>
</div>
@endsection
