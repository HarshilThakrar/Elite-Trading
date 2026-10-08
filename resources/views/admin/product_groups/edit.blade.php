@extends('layouts.app')
@section('title', 'Edit Product Group - DEMO ERP')
@section('header_title', 'Edit Product Group')
@section('content')
<div class="card card-custom">
    <div class="card-body">
        <form action="{{ route('product-groups.update', $group->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ $group->name }}" required>
            </div>
            <div class="d-flex justify-content-end">
                <a href="{{ route('product-groups.index') }}" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary-custom">Update Group</button>
            </div>
        </form>
    </div>
</div>
@endsection
