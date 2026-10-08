@extends('layouts.app')

@section('title', 'Inventory Ledger - Demo ERP')
@section('header_title', 'Inventory Ledger')

@section('content')
<div class="card card-custom">
    <div class="card-header bg-white p-3 border-bottom">
        <h5 class="mb-0 fw-bold text-primary-custom">Inventory Movement Ledger</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('inventory.ledger.index') }}" class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label fw-bold">Filter by Product</label>
                <select name="product_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Products</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->part_code }} - {{ $product->item_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if(request('product_id'))
                <div class="col-md-4 d-flex align-items-end">
                    <a href="{{ route('inventory.ledger.index') }}" class="btn btn-secondary">Clear Filter</a>
                </div>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Date & Time</th>
                        <th>Product</th>
                        <th class="text-center">Type</th>
                        <th class="text-center">Quantity Moved</th>
                        <th class="text-center">Balance After</th>
                        <th>Reference / Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgers as $ledger)
                    <tr>
                        <td>{{ $ledger->created_at->format('d M Y, h:i A') }}</td>
                        <td>
                            <span class="fw-bold">{{ optional($ledger->product)->part_code }}</span><br>
                            <small class="text-muted">{{ optional($ledger->product)->item_name }}</small>
                        </td>
                        <td class="text-center">
                            @if($ledger->type == 'IN')
                                <span class="badge bg-success text-white">IN</span>
                            @elseif($ledger->type == 'OUT')
                                <span class="badge bg-danger text-white">OUT</span>
                            @else
                                <span class="badge bg-warning text-white">ADJ</span>
                            @endif
                        </td>
                        <td class="text-center fw-bold {{ $ledger->type == 'IN' ? 'text-success' : ($ledger->type == 'OUT' ? 'text-danger' : 'text-warning') }}">
                            {{ $ledger->type == 'IN' ? '+' : ($ledger->type == 'OUT' ? '-' : '') }}{{ number_format($ledger->quantity, 2) }}
                        </td>
                        <td class="text-center fw-bold">{{ number_format($ledger->balance_after, 2) }}</td>
                        <td>
                            <span class="text-muted">{{ $ledger->notes }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No inventory movements recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end mt-3">
            {{ $ledgers->links() }}
        </div>
    </div>
</div>
@endsection
