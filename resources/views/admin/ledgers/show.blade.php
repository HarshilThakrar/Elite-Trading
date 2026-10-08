@extends('layouts.app')

@section('title', 'Ledger Details - ' . $ledger->name)
@section('header_title', 'Ledger Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Ledger: <span class="text-primary">{{ $ledger->name }}</span></h2>
    <div>
        <a href="{{ route('ledgers.index') }}" class="btn btn-secondary me-2">
            <i class="ph ph-arrow-left"></i> Back to List
        </a>
        <a href="{{ route('ledgers.edit', $ledger->id) }}" class="btn btn-primary">
            <i class="ph ph-pencil"></i> Edit
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold">Basic Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted w-25">Ledger Name</td>
                            <td class="fw-bold">{{ $ledger->name }}
                                @if($ledger->is_system) <span class="badge bg-secondary text-white ms-2">SYSTEM</span> @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Ledger Code</td>
                            <td>{{ $ledger->ledger_code ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Account Group</td>
                            <td><span class="badge bg-primary-subtle text-primary">{{ $ledger->accountGroup ? $ledger->accountGroup->name : 'N/A' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Ledger Type</td>
                            <td><span class="badge bg-light text-dark text-capitalize">{{ str_replace('_', ' ', $ledger->type) ?: 'General' }}</span></td>
                        </tr>
                        @if(in_array(strtolower($ledger->type), ['bank', 'bank_account']) && !empty($ledger->metadata))
                            <tr>
                                <td class="text-muted">Bank Name</td>
                                <td>{{ $ledger->metadata['bank_name'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Account No.</td>
                                <td>{{ $ledger->metadata['account_number'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">IFSC / Branch</td>
                                <td>{{ $ledger->metadata['ifsc'] ?? '-' }} / {{ $ledger->metadata['branch'] ?? '-' }}</td>
                            </tr>
                        @endif
                        @if(strtolower($ledger->type) == 'customer')
                            <tr>
                                <td class="text-muted">Linked Customer</td>
                                <td><a href="{{ route('customers.show', $ledger->reference_id ?? 0) }}" target="_blank">View Customer <i class="ph ph-arrow-up-right"></i></a></td>
                            </tr>
                        @endif
                        @if($ledger->type == 'vendor')
                            <tr>
                                <td class="text-muted">Linked Vendor</td>
                                <td><a href="{{ route('vendors.show', $ledger->reference_id ?? 0) }}" target="_blank">View Vendor <i class="ph ph-arrow-up-right"></i></a></td>
                            </tr>
                        @endif
                        <tr>
                            <td class="text-muted">Status</td>
                            <td>
                                @if($ledger->is_active)
                                    <span class="badge bg-success text-white">Active</span>
                                @else
                                    <span class="badge bg-danger text-white">Inactive</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Description</td>
                            <td>{{ $ledger->description ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-5">
        <div class="card shadow-sm border-0 mb-4 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold">Balance Summary</h5>
            </div>
            <div class="card-body">
                
                <div class="p-3 bg-light rounded mb-3">
                    <div class="text-muted small text-uppercase fw-bold mb-1">Opening Balance</div>
                    <div class="fs-4 fw-bold {{ $ledger->opening_balance_type == 'Dr' ? 'text-success' : 'text-danger' }}">
                        ₹{{ number_format($ledger->opening_balance ?? 0, 2) }} {{ $ledger->opening_balance_type }}
                    </div>
                    @if($ledger->opening_balance_date)
                        <div class="text-muted small mt-1">As of {{ $ledger->opening_balance_date->format('d M, Y') }}</div>
                    @endif
                </div>

                <div class="p-3 rounded mb-3 {{ $currentBalanceType == 'Dr' ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                    <div class="text-muted small text-uppercase fw-bold mb-1">Current Balance</div>
                    <div class="fs-2 fw-bold {{ $currentBalanceType == 'Dr' ? 'text-success' : 'text-danger' }}">
                        ₹{{ number_format($currentBalanceAmount, 2) }} {{ $currentBalanceType }}
                    </div>
                    <div class="text-muted small mt-1">Derived from {{ $entries->total() }} transactions</div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0 fw-bold">Transaction History</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Voucher Type</th>
                        <th>Voucher Number</th>
                        <th>Narration</th>
                        <th class="text-end text-success">Debit (Dr)</th>
                        <th class="text-end text-danger">Credit (Cr)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td class="ps-4">{{ $entry->bank_date ? \Carbon\Carbon::parse($entry->bank_date)->format('d M, Y') : '-' }}</td>
                            <td><span class="badge bg-secondary">{{ $entry->voucher->type ?? 'System' }}</span></td>
                            <td>{{ $entry->voucher->voucher_number ?? '-' }}</td>
                            <td class="text-muted" style="max-width: 250px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="{{ $entry->narration }}">{{ $entry->narration ?? '-' }}</td>
                            <td class="text-end fw-bold text-success">
                                {{ $entry->type == 'Dr' ? '₹'.number_format($entry->amount, 2) : '-' }}
                            </td>
                            <td class="text-end fw-bold text-danger">
                                {{ $entry->type == 'Cr' ? '₹'.number_format($entry->amount, 2) : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="ph ph-receipt d-block mb-2" style="font-size: 32px; color: #cbd5e1;"></i>
                                No transactions found for this ledger.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($entries->hasPages())
    <div class="card-footer bg-white border-top">
        {{ $entries->links() }}
    </div>
    @endif
</div>
@endsection
