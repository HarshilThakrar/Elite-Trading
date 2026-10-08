@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Financial Year Management</h2>
        <a href="{{ route('financial-years.create') }}" class="btn btn-primary">
            <i class="ph ph-plus"></i> Create Financial Year
        </a>
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

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Financial Year</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Current</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($financial_years as $fy)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $fy->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($fy->start_date)->format('d M Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($fy->end_date)->format('d M Y') }}</td>
                                <td>
                                    @if($fy->is_closed)
                                        <span class="badge bg-danger text-white">Closed</span>
                                    @elseif($fy->is_active)
                                        <span class="badge bg-success text-white">Active</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Future / Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($fy->is_active && !$fy->is_closed)
                                        <span class="badge bg-primary text-white"><i class="ph ph-check-circle"></i> Current</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('financial-years.show', $fy->id) }}" class="btn btn-sm btn-outline-info rounded-pill" title="View Details">
                                        <i class="ph ph-eye"></i>
                                    </a>
                                    <a href="{{ route('financial-years.edit', $fy->id) }}" class="btn btn-sm btn-outline-primary rounded-pill" title="Edit">
                                        <i class="ph ph-pencil-simple"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No financial years found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection