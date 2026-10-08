@extends('layouts.app')

@section('title', 'Account History - Demo ERP')
@section('header_title', 'Account History')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        
        <div class="mb-4">
            <h5 class="fw-bold mb-1">Account history</h5>
            <a href="{{ route('bank-accounts.index') }}" class="text-decoration-none small"><i class="ph ph-arrow-left me-1"></i> Back to report list</a>
        </div>

        <div class="row mb-5 align-items-end border-bottom pb-4">
            <div class="col-md-3">
                <label class="small text-muted mb-1">Account</label>
                <select class="form-select">
                    <option>{{ $bankAccount->bank_name }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="small text-muted mb-1"><span class="text-danger">*</span> From date</label>
                <div class="input-group">
                    <input type="date" class="form-control" value="{{ date('Y-m-d', strtotime('-20 days')) }}">
                    <span class="input-group-text bg-white"><i class="ph ph-calendar"></i></span>
                </div>
            </div>
            <div class="col-md-3">
                <label class="small text-muted mb-1"><span class="text-danger">*</span> To date</label>
                <div class="input-group">
                    <input type="date" class="form-control" value="{{ date('Y-m-d') }}">
                    <span class="input-group-text bg-white"><i class="ph ph-calendar"></i></span>
                </div>
            </div>
            <div class="col-md-3 d-flex justify-content-between align-items-end">
                <button class="btn btn-primary px-4">Filter</button>
                <button class="btn btn-light border"><i class="ph ph-printer"></i></button>
            </div>
        </div>

        <!-- Ledger Table -->
        <div class="px-4">
            <div class="text-center mb-4">
                <h4 class="fw-bold mb-1">GTSSolution</h4>
                <h6 class="mb-1">Account history</h6>
                <div class="small text-muted">{{ date('d/m/Y', strtotime('-20 days')) }} - {{ date('d/m/Y') }}</div>
            </div>

            <div class="table-responsive">
                <table class="table table-borderless border-bottom">
                    <thead class="border-bottom border-dark border-2 text-dark fw-bold">
                        <tr>
                            <th>Date</th>
                            <th>Transaction type</th>
                            <th>Split</th>
                            <th>Description</th>
                            <th class="text-end">Decrease</th>
                            <th class="text-end">Increase</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $runningBalance = 0;
                        @endphp
                        @forelse($entries as $entry)
                            @php
                                $increase = $entry->type == 'Dr' ? $entry->amount : 0;
                                $decrease = $entry->type == 'Cr' ? $entry->amount : 0;
                                $runningBalance += ($increase - $decrease);
                            @endphp
                            <tr class="border-bottom">
                                <td>{{ \Carbon\Carbon::parse($entry->created_at)->format('d/m/Y') }}</td>
                                <td>{{ $entry->voucher ? $entry->voucher->type : 'Journal' }}</td>
                                <td>Accounts Payable (A/P)</td>
                                <td>{{ $entry->narration ?: '-' }}</td>
                                <td class="text-end text-danger">{{ $decrease > 0 ? '₹'.number_format($decrease, 2) : '₹0' }}</td>
                                <td class="text-end text-success">{{ $increase > 0 ? '₹'.number_format($increase, 2) : '₹0' }}</td>
                                <td class="text-end fw-semibold">₹{{ number_format($runningBalance, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted border-bottom">No transaction history found for the selected dates.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection