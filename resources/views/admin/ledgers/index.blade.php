@extends('layouts.app')

@section('title', 'Ledger Management')
@section('header_title', 'Ledger Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Ledgers</h2>
    <a href="{{ route('ledgers.create') }}" class="btn btn-primary">
        <i class="ph ph-plus"></i> Create Ledger
    </a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body bg-light rounded">
        <form action="{{ route('ledgers.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name or Code...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Ledger Group</label>
                <select name="account_group_id" class="form-select select2">
                    <option value="">All Groups</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" {{ request('account_group_id') == $group->id ? 'selected' : '' }}>
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Ledger Type</label>
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="customer" {{ request('type') == 'customer' ? 'selected' : '' }}>Customer</option>
                    <option value="vendor" {{ request('type') == 'vendor' ? 'selected' : '' }}>Vendor</option>
                    <option value="bank" {{ request('type') == 'bank' ? 'selected' : '' }}>Bank</option>
                    <option value="cash" {{ request('type') == 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="general" {{ request('type') == 'general' ? 'selected' : '' }}>General</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <a href="{{ route('ledgers.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Ledger Name</th>
                        <th>Code</th>
                        <th>Group</th>
                        <th>Type</th>
                        <th>Opening Balance</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgers as $ledger)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-dark">{{ $ledger->name }}</span>
                                @if($ledger->is_system)
                                    <span class="badge bg-secondary text-white ms-2" style="font-size: 10px;">SYSTEM</span>
                                @endif
                            </td>
                            <td>{{ $ledger->ledger_code ?? '-' }}</td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary">
                                    {{ $ledger->accountGroup ? $ledger->accountGroup->name : 'None' }}
                                </span>
                            </td>
                            <td>
                                @if(strtolower($ledger->type) == 'customer')
                                    <span class="badge bg-info-subtle text-info"><i class="ph ph-users"></i> Customer</span>
                                @elseif(strtolower($ledger->type) == 'vendor')
                                    <span class="badge bg-warning-subtle text-warning"><i class="ph ph-storefront"></i> Vendor</span>
                                @elseif(in_array(strtolower($ledger->type), ['bank', 'bank_account']))
                                    <span class="badge bg-success-subtle text-success"><i class="ph ph-bank"></i> Bank</span>
                                @else
                                    <span class="badge bg-light text-dark text-capitalize">{{ str_replace('_', ' ', $ledger->type) ?: 'General' }}</span>
                                @endif
                            </td>
                            <td>
                                @if($ledger->opening_balance > 0)
                                    <span class="fw-bold {{ $ledger->opening_balance_type == 'Dr' ? 'text-success' : 'text-danger' }}">
                                        ₹{{ number_format($ledger->opening_balance, 2) }} {{ $ledger->opening_balance_type }}
                                    </span>
                                @else
                                    <span class="text-muted">₹0.00</span>
                                @endif
                            </td>
                            <td>
                                @if($ledger->is_active)
                                    <span class="badge bg-success text-white">Active</span>
                                @else
                                    <span class="badge bg-danger text-white">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('ledgers.show', $ledger->id) }}" class="btn btn-sm btn-outline-info" title="View"><i class="ph ph-eye"></i></a>
                                <a href="{{ route('ledgers.edit', $ledger->id) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="ph ph-pencil"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="ph ph-file-text d-block mb-2" style="font-size: 32px; color: #cbd5e1;"></i>
                                No ledgers found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($ledgers->hasPages())
    <div class="card-footer bg-white border-top">
        {{ $ledgers->links() }}
    </div>
    @endif
</div>
@endsection
