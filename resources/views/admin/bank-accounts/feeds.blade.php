@extends('layouts.app')

@section('title', 'Banking Feeds - Demo ERP')
@section('header_title', 'Banking')

@section('content')
<div class="row">
    <!-- Side Tabs -->
    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="list-group list-group-flush rounded">
                <a href="{{ route('bank-accounts.index') }}" class="list-group-item list-group-item-action {{ $activeTab == 'accounts' ? 'active' : '' }} border-0 py-3">
                    <i class="ph ph-bank me-2"></i> Bank Accounts
                </a>
                <a href="{{ route('bank-accounts.feeds') }}" class="list-group-item list-group-item-action {{ $activeTab == 'feeds' ? 'active' : '' }} border-0 py-3">
                    <i class="ph ph-arrows-left-right me-2"></i> Banking Feeds
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="col-md-9">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                
                <div class="row mb-4 align-items-end">
                    <div class="col-md-3">
                        <label class="small text-muted mb-1">Select Bank Account</label>
                        <select class="form-select">
                            <option value="">Select Bank Account</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->bank_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted mb-1">From date</label>
                        <div class="input-group">
                            <input type="date" class="form-control">
                            <span class="input-group-text bg-white"><i class="ph ph-calendar"></i></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted mb-1">To date</label>
                        <div class="input-group">
                            <input type="date" class="form-control">
                            <span class="input-group-text bg-white"><i class="ph ph-calendar"></i></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted mb-1">Status</label>
                        <select class="form-select">
                            <option>Uncleared</option>
                            <option>Cleared</option>
                            <option>All</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-start align-items-center mb-3">
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm w-auto">
                            <option>25</option>
                            <option>50</option>
                        </select>
                        <button class="btn btn-sm btn-light border">Export</button>
                        <button class="btn btn-sm btn-light border"><i class="ph ph-arrows-clockwise"></i></button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle border text-nowrap">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th>Date</th>
                                <th>Payee</th>
                                <th>Description</th>
                                <th>Withdrawals</th>
                                <th>Deposits</th>
                                <th>Banking rule</th>
                                <th>Cleared</th>
                                <th>Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No entries found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
