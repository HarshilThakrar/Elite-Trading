@extends('layouts.app')

@section('title', 'Credit Notes')
@section('header_title', 'Credit Notes (Sales Returns)')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Credit Notes</h2>
        <a href="{{ route('credit-notes.select') }}" class="btn btn-primary shadow-sm">
            <i class="ph ph-plus-circle me-1"></i> Create Credit Note
        </a>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('credit-notes.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Credit Note No">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select select2">
                        <option value="">All Customers</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary"><i class="ph ph-funnel me-1"></i> Filter</button>
                    <a href="{{ route('credit-notes.index') }}" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Credit Note No</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Original Invoice</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $voucher)
                        <tr>
                            <td><strong>{{ $voucher->voucher_number }}</strong></td>
                            <td>{{ \Carbon\Carbon::parse($voucher->date)->format('d M, Y') }}</td>
                            <td>
                                @if($voucher->metadata && isset($voucher->metadata['customer_id']))
                                    @php $cData = \App\Models\Customer::find($voucher->metadata['customer_id']); @endphp
                                    {{ $cData->company_name ?? 'N/A' }}
                                @endif
                            </td>
                            <td>{{ $voucher->metadata['original_invoice_number'] ?? 'N/A' }}</td>
                            <td>₹{{ number_format($voucher->metadata['grand_total'] ?? 0, 2) }}</td>
                            <td>
                                @if($voucher->status === 'Posted')
                                    <span class="badge bg-success text-white">Posted</span>
                                @elseif($voucher->status === 'Draft')
                                    <span class="badge bg-warning text-white">Draft</span>
                                @else
                                    <span class="badge bg-danger text-white">{{ $voucher->status }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('credit-notes.show', $voucher->id) }}" class="btn btn-sm btn-info text-white" title="View">
                                    <i class="ph ph-eye"></i>
                                </a>
                                @if($voucher->status === 'Posted')
                                    <a href="{{ route('credit-notes.pdf', $voucher->id) }}" class="btn btn-sm btn-secondary" title="Download PDF">
                                        <i class="ph ph-download-simple"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">No credit notes found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $vouchers->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
