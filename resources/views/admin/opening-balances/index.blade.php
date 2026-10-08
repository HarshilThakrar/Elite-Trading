@extends('layouts.app')

@section('title', 'Opening Balances')
@section('header_title', 'Opening Balances')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Opening Balances</h2>
        <a href="{{ route('opening-balances.create', ['financial_year_id' => $selectedFyId]) }}" class="btn btn-primary shadow-sm">
            <i class="ph ph-plus"></i> Add Opening Balance
        </a>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('opening-balances.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Financial Year</label>
                    <select name="financial_year_id" class="form-select select2" onchange="this.form.submit()">
                        <option value="">-- Select FY --</option>
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy->id }}" {{ $selectedFyId == $fy->id ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($fy->start_date)->format('Y') }}-{{ \Carbon\Carbon::parse($fy->end_date)->format('y') }}
                                @if($fy->is_closed) (Closed) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search Ledger</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name or code...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ph ph-funnel me-1"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    @if($selectedFy)
        @if($selectedFy->is_closed)
            <div class="alert alert-warning">
                <i class="ph ph-warning-circle"></i> This financial year is closed. You cannot add, edit, or delete opening balances for this year.
            </div>
        @endif

        <div class="row">
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Debit Balances</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($totalDr, 2) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="ph ph-trend-up fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Credit Balances</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($totalCr, 2) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="ph ph-trend-down fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-left-{{ $isBalanced ? 'info' : 'danger' }} shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-{{ $isBalanced ? 'info' : 'danger' }} text-uppercase mb-1">
                                    {{ $isBalanced ? 'Balances are MATCHED' : 'UNBALANCED Difference' }}
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">₹{{ number_format($difference, 2) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="ph ph-scales fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ledger</th>
                                <th>Group</th>
                                <th>Date</th>
                                <th class="text-end">Amount</th>
                                <th>Dr/Cr</th>
                                <th>Narration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($openingBalances as $ob)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $ob->ledger->name }}</div>
                                        <small class="text-muted">{{ $ob->ledger->ledger_code }}</small>
                                    </td>
                                    <td>{{ $ob->ledger->accountGroup->name ?? 'N/A' }}</td>
                                    <td>{{ $ob->opening_date->format('d-m-Y') }}</td>
                                    <td class="text-end fw-bold {{ $ob->type == 'Dr' ? 'text-primary' : 'text-success' }}">
                                        {{ number_format($ob->amount, 2) }}
                                    </td>
                                    <td>
                                        <span class="badge text-white {{ $ob->type == 'Dr' ? 'bg-primary' : 'bg-success' }}">{{ $ob->type }}</span>
                                    </td>
                                    <td>{{ Str::limit($ob->narration, 30) }}</td>
                                    <td>
                                        @if(!$selectedFy->is_closed)
                                            <a href="{{ route('opening-balances.edit', $ob->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="ph ph-pencil-simple"></i>
                                            </a>
                                            <form action="{{ route('opening-balances.destroy', $ob->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                                    <i class="ph ph-trash"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="badge bg-secondary">Locked</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No opening balances defined for this Financial Year.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($openingBalances->hasPages())
                <div class="card-footer bg-white border-0 py-3">
                    {{ $openingBalances->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="alert alert-info">Please select a Financial Year to view opening balances.</div>
    @endif
</div>
@endsection
