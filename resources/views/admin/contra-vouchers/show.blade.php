@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Contra Voucher Details</h2>
        <div>
            <a href="{{ route('contra-vouchers.index') }}" class="btn btn-light shadow-sm me-2">
                <i class="ph ph-arrow-left"></i> Back to List
            </a>
            <button class="btn btn-primary shadow-sm" onclick="window.print()">
                <i class="ph ph-printer"></i> Print
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="mb-0 fw-bold text-primary">{{ $voucher->voucher_number }}</h5>
            @if($voucher->status === 'Draft')
                <span class="badge bg-warning text-dark fs-6">Draft</span>
            @else
                <span class="badge bg-success text-white fs-6">Posted</span>
            @endif
        </div>
        <div class="card-body p-4">
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <span class="text-muted small d-block mb-1">Date</span>
                    <span class="fw-bold">{{ $voucher->date->format('d-M-Y') }}</span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block mb-1">Financial Year</span>
                    <span class="fw-bold">{{ $voucher->financialYear->name ?? '-' }}</span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block mb-1">Transfer Mode</span>
                    <span class="fw-bold">{{ $voucher->metadata['transfer_mode'] ?? '-' }}</span>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small d-block mb-1">Reference No / Cheque</span>
                    <span class="fw-bold">{{ $voucher->metadata['reference_number'] ?? '-' }}</span>
                </div>
                <div class="col-md-12">
                    <span class="text-muted small d-block mb-1">Narration</span>
                    <span class="fw-bold">{{ $voucher->narration ?? '-' }}</span>
                </div>
            </div>

            <h5 class="fw-bold mb-3">Accounting Details</h5>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th width="35%">Ledger</th>
                            <th width="25%">Line Narration</th>
                            <th width="20%" class="text-end">Debit (Dr)</th>
                            <th width="20%" class="text-end">Credit (Cr)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalDr = 0;
                            $totalCr = 0;
                            $lines = [];

                            if ($voucher->status === 'Draft' && !empty($voucher->draft_data['metadata'])) {
                                $sourceLedger = \App\Models\Ledger::find($voucher->draft_data['metadata']['source_ledger_id']);
                                $destLedger = \App\Models\Ledger::find($voucher->draft_data['metadata']['destination_ledger_id']);
                                $amount = (float)($voucher->draft_data['metadata']['amount'] ?? 0);
                                
                                $lines[] = [
                                    'ledger_name' => $destLedger ? $destLedger->getPath() : 'Unknown Destination',
                                    'narration' => '-',
                                    'debit' => $amount,
                                    'credit' => 0
                                ];
                                $lines[] = [
                                    'ledger_name' => $sourceLedger ? $sourceLedger->getPath() : 'Unknown Source',
                                    'narration' => '-',
                                    'debit' => 0,
                                    'credit' => $amount
                                ];
                                $totalDr = $amount;
                                $totalCr = $amount;
                            } else {
                                foreach($voucher->entries as $entry) {
                                    $debit = $entry->type === 'Dr' ? $entry->amount : 0;
                                    $credit = $entry->type === 'Cr' ? $entry->amount : 0;
                                    $lines[] = [
                                        'ledger_name' => $entry->ledger->getPath(),
                                        'narration' => $entry->narration,
                                        'debit' => $debit,
                                        'credit' => $credit
                                    ];
                                    $totalDr += $debit;
                                    $totalCr += $credit;
                                }
                            }
                        @endphp

                        @foreach($lines as $line)
                            <tr>
                                <td>{!! $line['ledger_name'] !!}</td>
                                <td>{{ $line['narration'] ?? '-' }}</td>
                                <td class="text-end">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '-' }}</td>
                                <td class="text-end">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td colspan="2" class="text-end">Totals:</td>
                            <td class="text-end text-primary">₹{{ number_format($totalDr, 2) }}</td>
                            <td class="text-end text-primary">₹{{ number_format($totalCr, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <span class="text-muted small d-block mb-1">Created By</span>
                    <span class="fw-bold">{{ \App\Models\User::find($voucher->created_by)->name ?? 'System' }}</span>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted small d-block mb-1">Created At</span>
                    <span class="fw-bold">{{ $voucher->created_at->format('d-M-Y h:i A') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .navbar, .sidebar, .btn {
            display: none !important;
        }
        .container-fluid {
            padding: 0 !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>
@endsection
