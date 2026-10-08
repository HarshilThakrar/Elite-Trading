@extends('layouts.app')

@section('title', 'ABC Analysis - Demo ERP')
@section('header_title', 'ABC Analysis (Fast/Slow Moving)')

@section('content')
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card card-abc abc-bg-a">
            <div class="card-body text-center">
                <h6 class="text-uppercase fw-bold opacity-75">Category A (Fast Moving)</h6>
                <h2 class="display-5 fw-bold mb-0">{{ $countA }}</h2>
                <div class="mt-2 small opacity-75">Top 20% of catalog by volume</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-abc abc-bg-b">
            <div class="card-body text-center">
                <h6 class="text-uppercase fw-bold opacity-75">Category B (Medium)</h6>
                <h2 class="display-5 fw-bold mb-0">{{ $countB }}</h2>
                <div class="mt-2 small opacity-75">Next 30% of catalog by volume</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-abc abc-bg-c">
            <div class="card-body text-center">
                <h6 class="text-uppercase fw-bold opacity-75">Category C (Slow Moving)</h6>
                <h2 class="display-5 fw-bold mb-0">{{ $countC }}</h2>
                <div class="mt-2 small opacity-75">Bottom 50% of catalog by volume</div>
            </div>
        </div>
    </div>
</div>

<!-- Print-Only Header -->
<div class="print-only mb-3">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
        <div>
            <h3 class="fw-bold mb-0 text-dark">Demo ERP System</h3>
            <div class="text-muted small">ABC Inventory Analysis Report (Fast/Medium/Slow Moving)</div>
        </div>
        <div class="text-end small">
            <div><strong>Generated on:</strong> {{ now()->format('d M Y, h:i A') }}</div>
        </div>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="m-0 fw-bold text-primary-custom"><i class="bi bi-bar-chart-fill me-2"></i> ABC Analysis Based on 90-Day Dispatches</h5>
        <div class="d-flex gap-2 align-items-center print-hide">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
            <button type="button" class="btn btn-danger btn-sm text-white" onclick="window.print()">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Rank</th>
                        <th>Product Details</th>
                        <th class="text-center">90-Day Dispatch Volume</th>
                        <th class="text-center">90-Day Revenue Value (₹)</th>
                        <th class="text-center">ABC Category</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($abcData as $index => $data)
                    <tr>
                        <td class="text-center fw-bold text-muted">#{{ $index + 1 }}</td>
                        <td>
                            <span class="fw-bold">{{ $data['product']->part_code }}</span><br>
                            <small class="text-muted">{{ $data['product']->item_name }}</small>
                        </td>
                        <td class="text-center fw-bold fs-5">{{ $data['out_qty'] }}</td>
                        <td class="text-center text-muted">
                            {{ number_format($data['revenue'], 2) }}
                        </td>
                        <td class="text-center">
                            @if($data['category'] == 'A')
                                <span class="badge abc-badge-a fs-6 px-3">Class A</span>
                            @elseif($data['category'] == 'B')
                                <span class="badge abc-badge-b fs-6 px-3">Class B</span>
                            @else
                                <span class="badge abc-badge-c fs-6 px-3">Class C</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('products.show', $data['product']->id) }}" class="btn btn-sm btn-outline-primary">View Product</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Premium Soft ABC Colors */
.abc-bg-a { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #064e3b; border: 1px solid #6ee7b7; }
.abc-bg-b { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #78350f; border: 1px solid #fcd34d; }
.abc-bg-c { background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); color: #374151; border: 1px solid #d1d5db; }

.abc-badge-a { background-color: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
.abc-badge-b { background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
.abc-badge-c { background-color: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }

.card-abc {
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.card-abc:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}
</style>
@endsection
