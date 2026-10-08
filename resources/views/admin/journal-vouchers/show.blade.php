@extends('layouts.app')

@section('title', 'Journal Voucher - ' . $voucher->voucher_number)
@section('header_title', 'Journal Voucher')

@section('content')
<div class="container-fluid">
    <!-- Top Action Row (Hidden in Print) -->
    <div class="d-flex justify-content-between align-items-center mb-4 print-hide d-print-none">
        <div>
            <h2 class="h3 mb-0 text-gray-800">Journal Voucher Details</h2>
            <div class="text-muted small">Reference: {{ $voucher->voucher_number }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('journal-vouchers.index') }}" class="btn btn-light border shadow-sm">
                <i class="ph ph-arrow-left me-1"></i> Back to List
            </a>
            <button class="btn btn-primary shadow-sm" onclick="window.print()">
                <i class="ph ph-printer me-1"></i> Print / Save PDF
            </button>
        </div>
    </div>

    <!-- Alert Messages (Hidden in Print) -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show print-hide d-print-none" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show print-hide d-print-none" role="alert">
            <i class="bi bi-x-circle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Official Printable Header (Visible Only in Print) -->
    <div class="print-only mb-4 pb-3 border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-0 text-dark">DEMO ERP SYSTEM</h3>
                <div class="text-muted small">Journal Accounting Voucher</div>
            </div>
            <div class="text-end">
                <h4 class="fw-bold mb-0 text-primary">JOURNAL VOUCHER</h4>
                <div class="small">Voucher No: <strong class="text-dark">{{ $voucher->voucher_number }}</strong></div>
                <div class="small">Date: <strong class="text-dark">{{ $voucher->date->format('d-M-Y') }}</strong></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4 print-card">
        <div class="card-header bg-white py-3 border-bottom print-hide d-print-none">
            <h5 class="mb-0 fw-bold text-primary">{{ $voucher->voucher_number }}</h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-4 mb-4">
                <div class="col-md-3 col-6">
                    <span class="text-muted small d-block mb-1">Date</span>
                    <span class="fw-bold">{{ $voucher->date->format('d-M-Y') }}</span>
                </div>
                <div class="col-md-3 col-6">
                    <span class="text-muted small d-block mb-1">Financial Year</span>
                    <span class="fw-bold">{{ $voucher->financialYear->name ?? '-' }}</span>
                </div>
                <div class="col-md-3 col-6">
                    <span class="text-muted small d-block mb-1">Status</span>
                    <span class="fw-bold text-success">{{ $voucher->status }}</span>
                </div>
                <div class="col-md-3 col-6">
                    <span class="text-muted small d-block mb-1">Created At</span>
                    <span class="fw-bold">{{ $voucher->created_at->format('d-M-Y h:i A') }}</span>
                </div>
                <div class="col-md-12 col-12">
                    <span class="text-muted small d-block mb-1">Master Narration</span>
                    <span class="fw-bold">{{ $voucher->narration ?? '-' }}</span>
                </div>
            </div>

            <h5 class="fw-bold mb-3">Accounting Lines</h5>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle mb-0">
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

                            if ($voucher->status === 'Draft' && !empty($voucher->draft_data['lines'])) {
                                foreach($voucher->draft_data['lines'] as $line) {
                                    $ledger = \App\Models\Ledger::find($line['ledger_id']);
                                    $lines[] = [
                                        'ledger_name' => $ledger ? $ledger->getPath() : 'Unknown Ledger',
                                        'narration' => $line['narration'],
                                        'debit' => $line['debit'],
                                        'credit' => $line['credit']
                                    ];
                                    $totalDr += (float)$line['debit'];
                                    $totalCr += (float)$line['credit'];
                                }
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
                                <td class="text-end">{{ $line['debit'] > 0 ? '₹' . number_format($line['debit'], 2) : '-' }}</td>
                                <td class="text-end">{{ $line['credit'] > 0 ? '₹' . number_format($line['credit'], 2) : '-' }}</td>
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


            <!-- Printable Signatures (Visible Only in Print) -->
            <div class="print-only mt-5 pt-4">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="border-top pt-2">
                            <span class="fw-bold">Prepared By</span>
                            <div class="small text-muted">{{ \App\Models\User::find($voucher->created_by)->name ?? 'System' }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border-top pt-2">
                            <span class="fw-bold">Verified By</span>
                            <div class="small text-muted">Accounts Department</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border-top pt-2">
                            <span class="fw-bold">Authorized Signatory</span>
                            <div class="small text-muted">Demo ERP System</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .print-hide, .no-print, .d-print-none,
        .print-hide *, .no-print *, .d-print-none *,
        .navbar, .sidebar, .topbar, .topbar *, .btn, button, a.btn,
        .global-chat-widget, .global-chat-widget *, .chat-toggle-btn, .chat-toggle-btn *, #chatToggleBtn, #chatWindow, .chat-window, .chat-window *, [class*="chat-"],
        .alert, .alert-dismissible, #toast-container, #toast-container * {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            position: absolute !important;
            left: -9999px !important;
            top: -9999px !important;
            width: 0 !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .print-only {
            display: block !important;
            visibility: visible !important;
        }
        .container-fluid {
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
        }
        .card, .print-card {
            border: 1px solid #cbd5e1 !important;
            box-shadow: none !important;
        }
        .table-responsive {
            overflow: visible !important;
        }
        table {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        table th, table td {
            border: 1px solid #cbd5e1 !important;
            padding: 6px 10px !important;
        }
        @page {
            size: A4 portrait;
            margin: 15mm 12mm;
        }
    }
</style>
@endsection
