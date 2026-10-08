@extends('layouts.app')

@section('title', 'Edit Role - Demo ERP')
@section('header_title', 'Edit Role: ' . $role->name)

@section('content')
<div class="card card-custom">
    <div class="card-header bg-white p-3 border-bottom">
        <h5 class="m-0 fw-bold text-primary-custom">Update Details</h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('roles.update', $role->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="form-label fw-bold">Role Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $role->name) }}" required>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold mb-3 border-bottom pb-2 w-100">Assign Permissions</label>
                
                @if($permissions->count() > 0)
                    <div class="row">
                        @foreach($permissions->chunk(ceil($permissions->count() / 3)) as $chunk)
                            <div class="col-md-4">
                                @foreach($chunk as $permission)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm_{{ $permission->id }}"
                                        {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="perm_{{ $permission->id }}">
                                        {{ $permission->name }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-warning">No permissions found in the database.</div>
                @endif
            </div>

            <div class="text-end mt-4 pt-3 border-top">
                <a href="{{ route('roles.index') }}" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary-custom">Update Role</button>
            </div>
        </form>
    </div>
</div>
@endsection
