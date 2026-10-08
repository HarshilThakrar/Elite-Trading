@extends('layouts.app')
@section('title', 'Edit Product Sub Group - Demo ERP')
@section('header_title', 'Edit Product Sub Group')
@section('content')
<div class="card card-custom">
    <div class="card-body">
        <form action="{{ route('product-subgroups.update', $subgroup->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="form-label fw-semibold">Group <span class="text-danger">*</span></label>
                <select name="product_group_id" class="form-select" required>
                    <option value="">Select Group</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" {{ $subgroup->product_group_id == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ $subgroup->name }}" required>
            </div>
            <div class="d-flex justify-content-end">
                <a href="{{ route('product-subgroups.index') }}" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary-custom">Update Sub Group</button>
            </div>
        </form>
    </div>
</div>
@endsection
