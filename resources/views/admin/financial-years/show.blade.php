@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('financial-years.index') }}" class="btn btn-outline-secondary me-3"><i class="ph ph-arrow-left"></i> Back</a>
        <h2 class="h3 mb-0 text-gray-800">Financial Year Details: {{ $financialYear->name }}</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Overview</h5>
                    <div>
                        @if($financialYear->is_closed)
                            <span class="badge bg-danger text-white px-3 py-2">Closed</span>
                        @elseif($financialYear->is_active)
                            <span class="badge bg-success text-white px-3 py-2">Active</span>
                        @else
                            <span class="badge bg-warning text-dark px-3 py-2">Future / Inactive</span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-borderless table-striped mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted w-25 ps-4 py-3">Financial Year Name</td>
                                <td class="fw-bold py-3">{{ $financialYear->name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted w-25 ps-4 py-3">Start Date</td>
                                <td class="py-3">{{ \Carbon\Carbon::parse($financialYear->start_date)->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted w-25 ps-4 py-3">End Date</td>
                                <td class="py-3">{{ \Carbon\Carbon::parse($financialYear->end_date)->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted w-25 ps-4 py-3">Total Vouchers</td>
                                <td class="py-3">{{ \App\Models\Voucher::where('financial_year_id', $financialYear->id)->count() }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted w-25 ps-4 py-3">Draft Vouchers</td>
                                <td class="py-3">
                                    <span class="badge bg-secondary">
                                        {{ \App\Models\Voucher::where('financial_year_id', $financialYear->id)->where('status', 'Draft')->count() }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="{{ route('financial-years.edit', $financialYear->id) }}" class="btn btn-outline-primary">
                            <i class="ph ph-pencil-simple"></i> Edit Financial Year
                        </a>

                        @if(!$financialYear->is_active && !$financialYear->is_closed)
                            <!-- Activate Action -->
                            <form action="{{ route('financial-years.activate', $financialYear->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100" onclick="return confirm('Activate this Financial Year? This will deactivate the currently active year.')">
                                    <i class="ph ph-check-circle"></i> Set as Active Year
                                </button>
                            </form>
                        @endif

                        @if($financialYear->is_active && !$financialYear->is_closed)
                            <!-- Close Action -->
                            <form action="{{ route('financial-years.close', $financialYear->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to CLOSE this financial year? This will block all future accounting entries to this year.')">
                                    <i class="ph ph-lock-key"></i> Close Financial Year
                                </button>
                            </form>
                        @endif

                        @if($financialYear->is_closed)
                            <!-- Reopen Action -->
                            <form action="{{ route('financial-years.reopen', $financialYear->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Reopening a closed financial year is an admin action and may affect reporting. Continue?')">
                                    <i class="ph ph-lock-key-open"></i> Reopen Financial Year
                                </button>
                            </form>
                        @endif

                        <hr>
                        
                        <!-- Delete Action -->
                        <form action="{{ route('financial-years.destroy', $financialYear->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Are you sure you want to delete this financial year? This cannot be undone.')">
                                <i class="ph ph-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
