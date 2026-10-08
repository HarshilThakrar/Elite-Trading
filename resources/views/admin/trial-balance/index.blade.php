@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-0 text-gray-800">Trial Balance</h2>
            <p class="text-muted mb-0">Accounting Trial Balance Report</p>
        </div>
        <div class="btn-group shadow-sm">
            <a href="{{ route('trial-balance.export', request()->all()) }}" class="btn btn-light"><i class="ph ph-export"></i> Export</a>
            <a href="{{ route('trial-balance.pdf', request()->all()) }}" class="btn btn-light" target="_blank"><i class="ph ph-file-pdf"></i> PDF</a>
            <button class="btn btn-light" onclick="window.print()"><i class="ph ph-printer"></i> Print</button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4 print-hide">
        <div class="card-body">
            <form action="{{ route('trial-balance.index') }}" method="GET" class="row g-3 align-items-end">
                
                <div class="col-md-2">
                    <label class="form-label">Financial Year</label>
                    <select name="financial_year_id" class="form-select select2">
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy->id }}" {{ $financialYear && $fy->id == $financialYear->id ? 'selected' : '' }}>
                                {{ $fy->name }} ({{ $fy->start_date->format('M Y') }} - {{ $fy->end_date->format('M Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Account Group</label>
                    <select name="account_group_id" class="form-select select2">
                        <option value="">-- All Groups --</option>
                        @foreach($accountGroups as $grp)
                            <option value="{{ $grp->id }}" {{ $filters['account_group_id'] == $grp->id ? 'selected' : '' }}>
                                {{ $grp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Ledger</label>
                    <select name="ledger_id" class="form-select select2">
                        <option value="">-- All Ledgers --</option>
                        @foreach($ledgers as $ledger)
                            <option value="{{ $ledger->id }}" {{ $filters['ledger_id'] == $ledger->id ? 'selected' : '' }}>
                                {{ $ledger->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $toDate }}" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">View Mode</label>
                    <select name="view_mode" class="form-select">
                        <option value="detailed" {{ $filters['view_mode'] == 'detailed' ? 'selected' : '' }}>Detailed (Flat)</option>
                        <option value="grouped" {{ $filters['view_mode'] == 'grouped' ? 'selected' : '' }}>Grouped (Tally)</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search Ledgers..." value="{{ $filters['search'] }}">
                </div>
                
                <div class="col-md-2 d-flex align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="show_zero" value="1" id="showZero" {{ $filters['show_zero'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="showZero">
                            Show Zero Balances
                        </label>
                    </div>
                </div>

                <div class="col-md-3 ms-auto text-end">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('trial-balance.index') }}" class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    @if($report)
    
    @php
        $gt = $report['grand_totals'];
        $diff = $gt['difference'];
    @endphp

    @if(abs($diff) > 0.01)
        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center print-hide">
            <i class="ph ph-warning-octagon fs-3 me-3 text-danger"></i>
            <div>
                <strong>TRIAL BALANCE IMBALANCE:</strong> The Trial Balance is not balanced. There is a difference of ₹{{ number_format(abs($diff), 2) }} {{ $diff > 0 ? 'Dr' : 'Cr' }}. Please check for orphan journal entries or data integrity issues.
            </div>
        </div>
    @endif

    <!-- Trial Balance Table -->
    <div class="card shadow-sm border-0 mb-4 print-container">
        <div class="card-header bg-white py-3 border-bottom text-center">
            <h4 class="mb-1 text-uppercase">TRIAL BALANCE</h4>
            <p class="mb-0 text-muted">
                Financial Year: {{ $financialYear->name }} <br>
                Period: {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}
            </p>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="bg-light table-group-divider text-center">
                        <tr>
                            <th rowspan="2" class="align-middle text-start ps-4">Particulars</th>
                            <th colspan="2">Opening Balance</th>
                            <th colspan="2">Transactions</th>
                            <th colspan="2">Closing Balance</th>
                        </tr>
                        <tr>
                            <th class="text-end" style="width: 12%;">Debit (₹)</th>
                            <th class="text-end" style="width: 12%;">Credit (₹)</th>
                            <th class="text-end" style="width: 12%;">Debit (₹)</th>
                            <th class="text-end" style="width: 12%;">Credit (₹)</th>
                            <th class="text-end" style="width: 12%;">Debit (₹)</th>
                            <th class="text-end" style="width: 12%;">Credit (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($filters['view_mode'] == 'detailed')
                            <!-- DETAILED (FLAT) VIEW -->
                            @forelse($report['rows'] as $row)
                                <tr>
                                    <td class="ps-4">
                                        <a href="{{ route('ledgers.show', ['ledger' => $row['ledger']->id, 'financial_year_id' => $financialYear->id, 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="text-decoration-none fw-bold text-dark">
                                            {{ $row['ledger']->name }}
                                        </a>
                                        <br>
                                        <small class="text-muted">{{ $row['ledger']->accountGroup->name ?? 'Unknown Group' }}</small>
                                    </td>
                                    <td class="text-end {{ $row['opening_dr'] > 0 ? 'text-success' : 'text-muted' }}">{{ $row['opening_dr'] > 0 ? number_format($row['opening_dr'], 2) : '-' }}</td>
                                    <td class="text-end {{ $row['opening_cr'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $row['opening_cr'] > 0 ? number_format($row['opening_cr'], 2) : '-' }}</td>
                                    <td class="text-end {{ $row['period_dr'] > 0 ? 'text-success' : 'text-muted' }}">{{ $row['period_dr'] > 0 ? number_format($row['period_dr'], 2) : '-' }}</td>
                                    <td class="text-end {{ $row['period_cr'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $row['period_cr'] > 0 ? number_format($row['period_cr'], 2) : '-' }}</td>
                                    <td class="text-end {{ $row['closing_dr'] > 0 ? 'text-success fw-bold' : 'text-muted' }}">{{ $row['closing_dr'] > 0 ? number_format($row['closing_dr'], 2) : '-' }}</td>
                                    <td class="text-end {{ $row['closing_cr'] > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $row['closing_cr'] > 0 ? number_format($row['closing_cr'], 2) : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        No accounting records found for the selected criteria.
                                    </td>
                                </tr>
                            @endforelse
                        @else
                            <!-- GROUPED (TALLY) VIEW -->
                            @if(empty($report['grouped']['tree']))
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        No accounting records found for the selected criteria.
                                    </td>
                                </tr>
                            @else
                                @php
                                    $renderGroup = function($node, $groupId, $level) use (&$renderGroup, $report, $financialYear, $fromDate, $toDate) {
                                        $group = $report['grouped']['allGroups']->get($groupId);
                                        $padding = $level * 20;
                                        
                                        // Display Group Row
                                        echo '<tr class="bg-light">';
                                        echo '<td class="fw-bold" style="padding-left: ' . (15 + $padding) . 'px;">' . strtoupper($group->name ?? 'Unknown') . '</td>';
                                        echo '<td class="text-end fw-bold text-success">' . ($node['opening_dr'] > 0 ? number_format($node['opening_dr'], 2) : '-') . '</td>';
                                        echo '<td class="text-end fw-bold text-danger">' . ($node['opening_cr'] > 0 ? number_format($node['opening_cr'], 2) : '-') . '</td>';
                                        echo '<td class="text-end fw-bold text-success">' . ($node['period_dr'] > 0 ? number_format($node['period_dr'], 2) : '-') . '</td>';
                                        echo '<td class="text-end fw-bold text-danger">' . ($node['period_cr'] > 0 ? number_format($node['period_cr'], 2) : '-') . '</td>';
                                        echo '<td class="text-end fw-bold text-success">' . ($node['closing_dr'] > 0 ? number_format($node['closing_dr'], 2) : '-') . '</td>';
                                        echo '<td class="text-end fw-bold text-danger">' . ($node['closing_cr'] > 0 ? number_format($node['closing_cr'], 2) : '-') . '</td>';
                                        echo '</tr>';

                                        // Display Ledgers
                                        foreach ($node['ledgers'] as $row) {
                                            $ledgerUrl = route('ledgers.show', ['ledger' => $row['ledger']->id, 'financial_year_id' => $financialYear->id, 'from_date' => $fromDate, 'to_date' => $toDate]);
                                            echo '<tr>';
                                            echo '<td style="padding-left: ' . (30 + $padding) . 'px;">';
                                            echo '<a href="'.$ledgerUrl.'" class="text-decoration-none text-dark">'.$row['ledger']->name.'</a>';
                                            echo '</td>';
                                            echo '<td class="text-end ' . ($row['opening_dr'] > 0 ? 'text-success' : 'text-muted') . '">' . ($row['opening_dr'] > 0 ? number_format($row['opening_dr'], 2) : '-') . '</td>';
                                            echo '<td class="text-end ' . ($row['opening_cr'] > 0 ? 'text-danger' : 'text-muted') . '">' . ($row['opening_cr'] > 0 ? number_format($row['opening_cr'], 2) : '-') . '</td>';
                                            echo '<td class="text-end ' . ($row['period_dr'] > 0 ? 'text-success' : 'text-muted') . '">' . ($row['period_dr'] > 0 ? number_format($row['period_dr'], 2) : '-') . '</td>';
                                            echo '<td class="text-end ' . ($row['period_cr'] > 0 ? 'text-danger' : 'text-muted') . '">' . ($row['period_cr'] > 0 ? number_format($row['period_cr'], 2) : '-') . '</td>';
                                            echo '<td class="text-end ' . ($row['closing_dr'] > 0 ? 'text-success' : 'text-muted') . '">' . ($row['closing_dr'] > 0 ? number_format($row['closing_dr'], 2) : '-') . '</td>';
                                            echo '<td class="text-end ' . ($row['closing_cr'] > 0 ? 'text-danger' : 'text-muted') . '">' . ($row['closing_cr'] > 0 ? number_format($row['closing_cr'], 2) : '-') . '</td>';
                                            echo '</tr>';
                                        }

                                        // Display Children
                                        foreach ($node['children'] as $childId => $childNode) {
                                            $renderGroup($childNode, $childId, $level + 1);
                                        }
                                    };
                                @endphp

                                @foreach($report['grouped']['tree'] as $groupId => $node)
                                    @php $renderGroup($node, $groupId, 0); @endphp
                                @endforeach
                            @endif
                        @endif
                    </tbody>
                    <tfoot class="table-group-divider bg-dark text-white fw-bold">
                        <tr>
                            <td class="text-end pe-4 fs-5">GRAND TOTAL:</td>
                            <td class="text-end fs-6">{{ number_format($gt['opening_dr'], 2) }}</td>
                            <td class="text-end fs-6">{{ number_format($gt['opening_cr'], 2) }}</td>
                            <td class="text-end fs-6">{{ number_format($gt['period_dr'], 2) }}</td>
                            <td class="text-end fs-6">{{ number_format($gt['period_cr'], 2) }}</td>
                            <td class="text-end fs-5 text-success">{{ number_format($gt['closing_dr'], 2) }}</td>
                            <td class="text-end fs-5 text-danger">{{ number_format($gt['closing_cr'], 2) }}</td>
                        </tr>
                        @if(abs($diff) > 0.01)
                        <tr class="bg-danger text-white">
                            <td class="text-end pe-4">DIFFERENCE:</td>
                            <td colspan="4" class="text-center">Trial Balance is out of balance by ₹{{ number_format(abs($diff), 2) }}</td>
                            <td class="text-end">{{ $diff > 0 ? number_format($diff, 2) : '-' }}</td>
                            <td class="text-end">{{ $diff < 0 ? number_format(abs($diff), 2) : '-' }}</td>
                        </tr>
                        @endif
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
    .card-footer { display: none; }
}
</style>
@endsection
