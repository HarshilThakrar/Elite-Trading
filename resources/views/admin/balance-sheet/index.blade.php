@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-0 text-gray-800">Balance Sheet</h2>
            <p class="text-muted mb-0">Accounting Financial Position</p>
        </div>
        <div class="btn-group shadow-sm">
            <a href="{{ route('balance-sheet.export', request()->all()) }}" class="btn btn-light"><i class="ph ph-export"></i> Export</a>
            <a href="{{ route('balance-sheet.pdf', request()->all()) }}" class="btn btn-light" target="_blank"><i class="ph ph-file-pdf"></i> PDF</a>
            <button class="btn btn-light" onclick="window.print()"><i class="ph ph-printer"></i> Print</button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4 print-hide">
        <div class="card-body">
            <form action="{{ route('balance-sheet.index') }}" method="GET" class="row g-3 align-items-end">
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
                    <label class="form-label">As On Date</label>
                    <input type="date" name="as_on_date" class="form-control" value="{{ $asOnDate }}" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">View Mode</label>
                    <select name="view_mode" class="form-select">
                        <option value="grouped" {{ $filters['view_mode'] == 'grouped' ? 'selected' : '' }}>Grouped (Tally)</option>
                        <option value="detailed" {{ $filters['view_mode'] == 'detailed' ? 'selected' : '' }}>Detailed (Flat)</option>
                    </select>
                </div>
                
                <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="show_zero" value="1" id="showZero" {{ $filters['show_zero'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="showZero">
                            Show Zero Balances
                        </label>
                    </div>
                </div>

                <div class="col-md-3 ms-auto text-end">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    @if($report)
    
    @php
        $pl = $report['current_profit'];
        $diff = $report['difference'];
        $fromDate = $financialYear->start_date->format('Y-m-d');
    @endphp

    @if(!$report['is_balanced'])
        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center print-hide">
            <i class="ph ph-warning-octagon fs-3 me-3 text-danger"></i>
            <div>
                <strong>BALANCE SHEET MISMATCH:</strong> The Balance Sheet is out of balance by ₹{{ number_format(abs($diff), 2) }} {{ $diff > 0 ? 'Assets > Liabilities' : 'Liabilities > Assets' }}. Please investigate trial balance discrepancies.
            </div>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="row mb-4 print-hide">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1" style="opacity: 0.8">Total Assets</h6>
                    <h3 class="mb-0">₹{{ number_format($report['total_assets'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-secondary text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1" style="opacity: 0.8">Liabilities & Equity</h6>
                    <h3 class="mb-0">₹{{ number_format($report['total_liabilities'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 {{ $pl > 0 ? 'bg-success' : ($pl < 0 ? 'bg-warning text-dark' : 'bg-light text-dark') }} text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1" style="opacity: 0.8">Current Year {{ $pl >= 0 ? 'Profit' : 'Loss' }}</h6>
                    <h3 class="mb-0">₹{{ number_format(abs($pl), 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 {{ $report['is_balanced'] ? 'bg-success' : 'bg-danger' }} text-white">
                <div class="card-body">
                    <h6 class="text-uppercase mb-1" style="opacity: 0.8">Difference</h6>
                    <h3 class="mb-0">₹{{ number_format(abs($diff), 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Balance Sheet Table -->
    <div class="card shadow-sm border-0 mb-4 print-container">
        <div class="card-header bg-white py-3 border-bottom text-center">
            <h4 class="mb-1 text-uppercase">BALANCE SHEET</h4>
            <p class="mb-0 text-muted">
                As On {{ \Carbon\Carbon::parse($asOnDate)->format('d-M-Y') }}<br>
                Financial Year: {{ $financialYear->name }}
            </p>
        </div>
        <div class="card-body p-0">
            <div class="row g-0">
                
                <!-- Liabilities Side -->
                <div class="col-md-6 border-end">
                    <h5 class="p-3 mb-0 bg-light border-bottom text-center">LIABILITIES & EQUITY</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <tbody>
                                @if($filters['view_mode'] == 'detailed')
                                    @foreach($report['liability_rows'] as $row)
                                    <tr>
                                        <td class="ps-4">
                                            <a href="{{ route('ledgers.show', ['ledger' => $row['ledger']->id, 'financial_year_id' => $financialYear->id, 'from_date' => $fromDate, 'to_date' => $asOnDate]) }}" class="text-decoration-none fw-bold text-dark">
                                                {{ $row['ledger']->name }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $row['ledger']->accountGroup->name ?? 'Unknown Group' }}</small>
                                        </td>
                                        <td class="text-end fw-bold pe-4">₹{{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                @else
                                    @php
                                        $renderGroup = function($node, $groupId, $level, $allGroups) use (&$renderGroup, $financialYear, $fromDate, $asOnDate) {
                                            $group = $allGroups->get($groupId);
                                            $padding = $level * 20;
                                            
                                            echo '<tr class="bg-light">';
                                            echo '<td class="fw-bold" style="padding-left: ' . (15 + $padding) . 'px;">' . strtoupper($group->name ?? 'Unknown') . '</td>';
                                            echo '<td class="text-end fw-bold pe-4">' . number_format($node['amount'], 2) . '</td>';
                                            echo '</tr>';

                                            foreach ($node['ledgers'] as $row) {
                                                $ledgerUrl = route('ledgers.show', ['ledger' => $row['ledger']->id, 'financial_year_id' => $financialYear->id, 'from_date' => $fromDate, 'to_date' => $asOnDate]);
                                                echo '<tr>';
                                                echo '<td style="padding-left: ' . (30 + $padding) . 'px;">';
                                                echo '<a href="'.$ledgerUrl.'" class="text-decoration-none text-dark">'.$row['ledger']->name.'</a>';
                                                echo '</td>';
                                                echo '<td class="text-end pe-4">' . number_format($row['amount'], 2) . '</td>';
                                                echo '</tr>';
                                            }

                                            foreach ($node['children'] as $childId => $childNode) {
                                                $renderGroup($childNode, $childId, $level + 1, $allGroups);
                                            }
                                        };
                                    @endphp

                                    @foreach($report['grouped_liabilities']['tree'] as $groupId => $node)
                                        @php $renderGroup($node, $groupId, 0, $report['grouped_liabilities']['allGroups']); @endphp
                                    @endforeach
                                @endif
                                
                                <tr>
                                    <td class="fw-bold text-primary ps-4">CURRENT YEAR {{ $pl >= 0 ? 'PROFIT' : 'LOSS' }} (P&L)</td>
                                    <td class="text-end fw-bold text-primary pe-4">₹{{ number_format($pl, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Assets Side -->
                <div class="col-md-6">
                    <h5 class="p-3 mb-0 bg-light border-bottom text-center">ASSETS</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <tbody>
                                @if($filters['view_mode'] == 'detailed')
                                    @foreach($report['asset_rows'] as $row)
                                    <tr>
                                        <td class="ps-4">
                                            <a href="{{ route('ledgers.show', ['ledger' => $row['ledger']->id, 'financial_year_id' => $financialYear->id, 'from_date' => $fromDate, 'to_date' => $asOnDate]) }}" class="text-decoration-none fw-bold text-dark">
                                                {{ $row['ledger']->name }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $row['ledger']->accountGroup->name ?? 'Unknown Group' }}</small>
                                        </td>
                                        <td class="text-end fw-bold pe-4">₹{{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                @else
                                    @foreach($report['grouped_assets']['tree'] as $groupId => $node)
                                        @php $renderGroup($node, $groupId, 0, $report['grouped_assets']['allGroups']); @endphp
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            
            <div class="row g-0 border-top bg-dark text-white fw-bold">
                <div class="col-6 p-3 d-flex justify-content-between border-end">
                    <span>TOTAL LIABILITIES & EQUITY</span>
                    <span>₹{{ number_format($report['total_liabilities'], 2) }}</span>
                </div>
                <div class="col-6 p-3 d-flex justify-content-between">
                    <span>TOTAL ASSETS</span>
                    <span>₹{{ number_format($report['total_assets'], 2) }}</span>
                </div>
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
