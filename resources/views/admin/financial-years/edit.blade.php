@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('financial-years.index') }}" class="btn btn-outline-secondary me-3"><i class="ph ph-arrow-left"></i> Back</a>
        <h2 class="h3 mb-0 text-gray-800">Edit Financial Year</h2>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-x-circle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card card-custom shadow-sm border-0">
        <div class="card-body p-4">
            <form action="{{ route('financial-years.update', $financialYear->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="name" class="form-label fw-bold">Financial Year Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $financialYear->name) }}" placeholder="e.g. 2024-25 or FY 2024-2025" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Descriptive identifier for this accounting period (e.g., 2024-25).</small>
                    </div>

                    <div class="col-md-6">
                        <label for="start_date" class="form-label fw-bold">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $financialYear->start_date ? \Carbon\Carbon::parse($financialYear->start_date)->format('Y-m-d') : '') }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="end_date" class="form-label fw-bold">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $financialYear->end_date ? \Carbon\Carbon::parse($financialYear->end_date)->format('Y-m-d') : '') }}" required>
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        @if($financialYear->is_closed)
                            <span class="badge bg-danger text-white px-3 py-2 rounded-pill"><i class="bi bi-lock-fill me-1"></i> Status: Closed</span>
                        @elseif($financialYear->is_active)
                            <span class="badge bg-success text-white px-3 py-2 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Status: Active (Current Year)</span>
                        @else
                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="bi bi-clock-history me-1"></i> Status: Inactive / Future</span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('financial-years.index') }}" class="btn btn-light border px-3">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm fw-semibold" style="background-color: #2563eb !important; border-color: #2563eb !important; color: #ffffff !important;">
                            <i class="ph ph-floppy-disk me-1"></i> Update Financial Year
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection