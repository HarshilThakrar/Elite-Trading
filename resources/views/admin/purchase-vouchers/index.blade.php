@extends('layouts.app')

@section('title', 'Purchase Vouchers')
@section('header_title', 'Purchase Vouchers')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Purchase Vouchers</h2>
        <a href="{{ route('purchase-vouchers.select-po') }}" class="btn btn-primary shadow-sm">
            <i class="ph ph-plus-circle me-1"></i> Create Purchase Voucher
        </a>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('purchase-vouchers.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Voucher No / Vendor Invoice No">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vendor</label>
                    <select name="vendor_id" class="form-select select2">
                        <option value="">All Vendors</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}" {{ request('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary"><i class="ph ph-funnel me-1"></i> Filter</button>
                    <a href="{{ route('purchase-vouchers.index') }}" class="btn btn-secondary">Clear</a>
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
                            <th>Voucher No</th>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th>Vendor Invoice No</th>
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
                                @if($voucher->reference && $voucher->reference->vendor)
                                    {{ $voucher->reference->vendor->company_name }}
                                @elseif($voucher->metadata && isset($voucher->metadata['vendor_id']))
                                    @php $vData = \App\Models\Vendor::find($voucher->metadata['vendor_id']); @endphp
                                    {{ $vData->company_name ?? 'N/A' }}
                                @endif
                            </td>
                            <td>{{ $voucher->metadata['vendor_invoice_number'] ?? 'N/A' }}</td>
                            <td>₹{{ number_format($voucher->metadata['grand_total'] ?? 0, 2) }}</td>
                            <td>
                                @if($voucher->status === 'Posted')
                                    <span class="badge bg-success">Posted</span>
                                @elseif($voucher->status === 'Draft')
                                    <span class="badge bg-warning">Draft</span>
                                @else
                                    <span class="badge bg-danger">{{ $voucher->status }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('purchase-vouchers.show', $voucher->id) }}" class="btn btn-sm btn-info text-white" title="View">
                                    <i class="ph ph-eye"></i>
                                </a>
                                @if($voucher->status === 'Posted')
                                    <a href="{{ route('purchase-vouchers.pdf', $voucher->id) }}" class="btn btn-sm btn-secondary" title="Download PDF">
                                        <i class="ph ph-download-simple"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">No purchase vouchers found.</td>
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
