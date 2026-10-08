@extends('layouts.app')

@section('title', 'Vendor Analysis - Demo ERP')
@section('header_title', 'Vendor Analysis Report')

@section('content')
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card card-custom bg-white border-start border-4 border-primary">
            <div class="card-body">
                <div class="text-muted small fw-bold text-uppercase mb-1">Total Vendors Analyzed</div>
                <h3 class="fw-bold mb-0">{{ count($analysisData) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom bg-white border-start border-4 border-info">
            <div class="card-body">
                <div class="text-muted small fw-bold text-uppercase mb-1">Total POs Placed</div>
                <h3 class="fw-bold mb-0 text-info">
                    {{ number_format($totalOverallOrders) }}
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom bg-white border-start border-4 border-warning">
            <div class="card-body">
                <div class="text-muted small fw-bold text-uppercase mb-1">Total Items Ordered</div>
                <h3 class="fw-bold mb-0 text-warning">
                    {{ number_format($totalOverallMaterial) }}
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom bg-white border-start border-4 border-success">
            <div class="card-body">
                <div class="text-muted small fw-bold text-uppercase mb-1">Total Amount Spent</div>
                <h3 class="fw-bold mb-0 text-success">
                    ₹{{ number_format($totalOverallAmount, 2) }}
                </h3>
            </div>
        </div>
    </div>
</div>

<!-- Print-Only Header -->
<div class="print-only mb-3">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
        <div>
            <h3 class="fw-bold mb-0 text-dark">Demo ERP System</h3>
            <div class="text-muted small">Vendor Material Volume & Expenditure Analysis Report</div>
        </div>
        <div class="text-end small">
            <div><strong>Generated on:</strong> {{ now()->format('d M Y, h:i A') }}</div>
        </div>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="m-0 fw-bold text-primary-custom"><i class="bi bi-truck me-2"></i> Material Volume by Vendor</h5>
        <div class="d-flex gap-2 align-items-center print-hide">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
            <button type="button" class="btn btn-danger btn-sm text-white" onclick="window.print()">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="vendor-analysis-table">
                <thead class="table-light">
                    <tr>
                        <th class="px-4">Rank</th>
                        <th>Vendor</th>
                        <th class="text-end">Total POs</th>
                        <th class="text-end">Material Quantity (Items)</th>
                        <th class="text-end px-4">Total Amount Spent (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($analysisData as $index => $data)
                    <tr>
                        <td class="px-4 text-muted fw-bold">#{{ $index + 1 }}</td>
                        <td>
                            <h6 class="mb-0 fw-bold">{{ $data['vendor']->company_name }}</h6>
                            <small class="text-muted">{{ $data['vendor']->contact_person }} | {{ $data['vendor']->vendor_code }}</small>
                        </td>
                        <td class="text-end">{{ $data['total_orders'] }}</td>
                        <td class="text-end fw-bold text-primary">{{ number_format($data['total_material_qty']) }}</td>
                        <td class="text-end fw-bold text-success px-4">{{ number_format($data['total_amount'], 2) }}</td>
                    </tr>
                    @empty
                    <!-- We use datatables so it shouldn't show this if empty, but good to have a fallback -->
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#vendor-analysis-table').DataTable({
            "paging": false,       
            "info": false,         
            "searching": true,     
            "ordering": true,      
            "dom": '<"d-flex justify-content-between align-items-center mb-3 p-3"f>rt', 
            "language": {
                "search": "",
                "searchPlaceholder": "Search vendors...",
                "emptyTable": "<div class='text-center py-5 text-muted'><i class='bi bi-inbox text-secondary fs-1 d-block mb-3 opacity-25'></i><h5>No Purchase Data Found</h5><p>Once you have approved purchase orders, vendor analysis metrics will appear here.</p></div>"
            }
        });
        
        $('.dataTables_filter input').addClass('form-control shadow-sm border-0').css('min-width', '300px');
    });
</script>
@endpush
