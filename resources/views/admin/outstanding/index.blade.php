@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-0 text-gray-800">Outstanding Analysis</h2>
            <p class="text-muted mb-0">Aging and Unpaid Invoices</p>
        </div>
        <div class="btn-group shadow-sm">
            <a href="{{ route('outstanding.export', request()->all()) }}" class="btn btn-light"><i class="ph ph-export"></i> Export</a>
            <a href="{{ route('outstanding.pdf', request()->all()) }}" class="btn btn-light" target="_blank"><i class="ph ph-file-pdf"></i> PDF</a>
            <button class="btn btn-light" onclick="window.print()"><i class="ph ph-printer"></i> Print</button>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4 print-hide">
        <li class="nav-item">
            <a class="nav-link {{ $type == 'receivables' ? 'active fw-bold bg-white' : '' }}" href="{{ route('outstanding.index', ['type' => 'receivables', 'financial_year_id' => request('financial_year_id'), 'as_of_date' => request('as_of_date')]) }}">
                <i class="ph ph-users me-1"></i> Receivables (Debtors)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $type == 'payables' ? 'active fw-bold bg-white' : '' }}" href="{{ route('outstanding.index', ['type' => 'payables', 'financial_year_id' => request('financial_year_id'), 'as_of_date' => request('as_of_date')]) }}">
                <i class="ph ph-storefront me-1"></i> Payables (Creditors)
            </a>
        </li>
    </ul>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4 print-hide">
        <div class="card-body">
            <form action="{{ route('outstanding.index') }}" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="type" value="{{ $type }}">
                
                <div class="col-md-3">
                    <label class="form-label">Financial Year (Opening Balances)</label>
                    <select name="financial_year_id" class="form-select">
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy->id }}" {{ $financialYear && $fy->id == $financialYear->id ? 'selected' : '' }}>
                                {{ $fy->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">As Of Date</label>
                    <input type="date" name="as_of_date" class="form-control" value="{{ $asOfDate }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Search Party</label>
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Customer or Vendor name...">
                </div>

                <div class="col-md-2 text-end">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    @if($report)
    
    <!-- Aging Summary Cards -->
    <div class="row mb-4 print-hide">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm border-0 bg-primary text-white h-100">
                <div class="card-body text-center">
                    <h6 class="text-uppercase mb-1" style="opacity: 0.8">Total {{ ucfirst($type) }}</h6>
                    <h3 class="mb-0">₹{{ number_format($report['summary']['total_outstanding'], 2) }}</h3>
                    <small style="opacity: 0.8">From {{ $report['summary']['party_count'] }} parties</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-9 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-0">
                    <div class="row g-0 h-100 align-items-center text-center">
                        <div class="col border-end p-2">
                            <small class="text-muted d-block text-uppercase">Not Due</small>
                            <strong>₹{{ number_format($report['summary']['aging']['not_due'], 2) }}</strong>
                        </div>
                        <div class="col border-end p-2 bg-light">
                            <small class="text-muted d-block text-uppercase">0-30 Days</small>
                            <strong>₹{{ number_format($report['summary']['aging']['0_30'], 2) }}</strong>
                        </div>
                        <div class="col border-end p-2 bg-light">
                            <small class="text-muted d-block text-uppercase">31-60 Days</small>
                            <strong>₹{{ number_format($report['summary']['aging']['31_60'], 2) }}</strong>
                        </div>
                        <div class="col border-end p-2 bg-light">
                            <small class="text-muted d-block text-uppercase">61-90 Days</small>
                            <strong>₹{{ number_format($report['summary']['aging']['61_90'], 2) }}</strong>
                        </div>
                        <div class="col border-end p-2">
                            <small class="text-muted d-block text-uppercase">> 90 Days</small>
                            <strong class="text-danger">
                                ₹{{ number_format($report['summary']['aging']['91_180'] + $report['summary']['aging']['181_365'] + $report['summary']['aging']['above_365'], 2) }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Outstanding Table -->
    <div class="card shadow-sm border-0 mb-4 print-container">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 text-uppercase">{{ ucfirst($type) }} AGING</h5>
                <p class="mb-0 text-muted small">As Of {{ \Carbon\Carbon::parse($asOfDate)->format('d-M-Y') }}</p>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Party / Invoice Ref</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th class="text-end">Invoice Amount</th>
                            <th class="text-end">Paid/Adj</th>
                            <th class="text-end">Outstanding</th>
                            <th class="text-center">Overdue Days</th>
                            <th class="text-center">Bucket</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['data'] as $party)
                            <!-- Party Header -->
                            <tr class="table-secondary">
                                <td colspan="5" class="fw-bold">
                                    <a href="{{ route('ledgers.show', ['ledger' => $party['ledger']->id, 'financial_year_id' => $financialYear->id, 'as_of_date' => $asOfDate]) }}" class="text-decoration-none text-dark">
                                        <i class="ph ph-folder-open me-2 text-muted"></i>{{ $party['ledger']->name }}
                                    </a>
                                </td>
                                <td class="text-end fw-bold text-primary">₹{{ number_format($party['total_outstanding'], 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                            
                            <!-- Invoices -->
                            @foreach($party['invoices'] as $inv)
                                <tr>
                                    <td class="ps-5">
                                        @if($inv['is_opening'])
                                            <span class="text-muted fw-bold">{{ $inv['voucher_number'] }}</span>
                                        @else
                                            @php
                                                // Try to route voucher
                                                $route = '#';
                                                if(isset($inv['type']) && isset($inv['voucher_id'])) {
                                                    $vtype = strtolower(str_replace(' ', '-', $inv['type']));
                                                    if (Route::has($vtype . '-vouchers.show')) $route = route($vtype . '-vouchers.show', $inv['voucher_id']);
                                                    elseif (Route::has($vtype . 's.show')) $route = route($vtype . 's.show', $inv['voucher_id']);
                                                    elseif ($inv['type'] === 'Debit Note' && Route::has('debit-notes.show')) $route = route('debit-notes.show', $inv['voucher_id']);
                                                    elseif ($inv['type'] === 'Credit Note' && Route::has('credit-notes.show')) $route = route('credit-notes.show', $inv['voucher_id']);
                                                }
                                            @endphp
                                            <a href="{{ $route }}" class="text-decoration-none">{{ $inv['voucher_number'] }}</a>
                                        @endif
                                    </td>
                                    <td>{{ $inv['date']->format('d-M-Y') }}</td>
                                    <td>{{ $inv['due_date']->format('d-M-Y') }}</td>
                                    <td class="text-end">{{ $inv['amount'] > 0 ? '₹'.number_format($inv['amount'], 2) : '-' }}</td>
                                    <td class="text-end">{{ $inv['paid'] > 0 ? '₹'.number_format($inv['paid'], 2) : '-' }}</td>
                                    <td class="text-end fw-bold {{ $inv['outstanding'] < 0 ? 'text-success' : '' }}">
                                        ₹{{ number_format($inv['outstanding'], 2) }}
                                        @if($inv['outstanding'] < 0) <small>(Adv)</small> @endif
                                    </td>
                                    <td class="text-center">
                                        @if($inv['days'] > 0)
                                            <span class="badge bg-danger rounded-pill">{{ $inv['days'] }} days</span>
                                        @else
                                            <span class="badge bg-success rounded-pill">Not Due</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted text-uppercase">{{ str_replace('_', '-', $inv['bucket']) }}</small>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="ph ph-check-circle display-4 text-success mb-3"></i>
                                    <p>No outstanding balances found for the selected criteria.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <td colspan="5" class="text-end fw-bold">TOTAL OUTSTANDING</td>
                            <td class="text-end fw-bold fs-5 text-warning">₹{{ number_format($report['summary']['total_outstanding'], 2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    @endif
</div>

<style>
@media print {
    body * { visibility: hidden; }
    .print-hide { display: none !important; }
    .print-container, .print-container * { visibility: visible; }
    .print-container { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>
@endsection
