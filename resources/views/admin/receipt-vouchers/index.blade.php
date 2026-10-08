@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Receipt Vouchers</h2>
        <a href="{{ route('receipt-vouchers.create') }}" class="btn btn-primary shadow-sm">
            <i class="ph ph-plus"></i> Create Receipt
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <form action="{{ route('receipt-vouchers.index') }}" method="GET" class="d-flex w-100 gap-2">
                <input type="text" name="search" class="form-control" placeholder="Search Voucher No, Reference, or Narration..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary px-4">Search</button>
                <a href="{{ route('receipt-vouchers.index') }}" class="btn btn-light px-4">Clear</a>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Voucher No</th>
                            <th>Date</th>
                            <th>Receipt Mode</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $voucher)
                            <tr>
                                <td class="ps-4 fw-bold text-primary">{{ $voucher->voucher_number }}</td>
                                <td>{{ $voucher->date->format('d-m-Y') }}</td>
                                <td>{{ $voucher->metadata['receipt_mode'] ?? '-' }}</td>
                                <td>{{ $voucher->metadata['reference_number'] ?? '-' }}</td>
                                <td>
                                    @if($voucher->status === 'Draft')
                                        <span class="badge bg-warning text-dark">Draft</span>
                                    @else
                                        <span class="badge bg-success text-white">Posted</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="{{ route('receipt-vouchers.show', $voucher->id) }}" class="btn btn-sm btn-light" title="View">
                                            <i class="ph ph-eye"></i>
                                        </a>
                                        @if($voucher->status === 'Draft')
                                            <a href="{{ route('receipt-vouchers.edit', $voucher->id) }}" class="btn btn-sm btn-light" title="Edit Draft">
                                                <i class="ph ph-pencil"></i>
                                            </a>
                                            <form action="{{ route('receipt-vouchers.cancel', $voucher->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this draft receipt?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-light text-danger" title="Delete Draft"><i class="ph ph-trash"></i></button>
                                            </form>
                                            <form action="{{ route('receipt-vouchers.post', $voucher->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Post this Receipt Voucher?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-light text-success" title="Post Voucher"><i class="ph ph-paper-plane-right"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="ph ph-receipt fs-1 d-block mb-2"></i>
                                    No Receipt Vouchers found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-3">
            {{ $vouchers->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
