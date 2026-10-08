@extends('layouts.app')

@section('title', 'Edit User - Demo ERP')
@section('header_title', 'Edit User: ' . $user->name)

@section('content')
<div class="card card-custom">
    <div class="card-header bg-white p-3 border-bottom">
        <h5 class="m-0 fw-bold text-primary-custom">Update Details</h5>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Email Address</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Password (Leave blank to keep current)</label>
                    <input type="password" name="password" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Assign Roles</label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($roles as $role)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}" id="role_{{ $role->id }}" 
                            {{ in_array($role->name, $userRoles) ? 'checked' : '' }}>
                        <label class="form-check-label" for="role_{{ $role->id }}">
                            {{ $role->name }}
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="text-end mt-4 pt-3 border-top">
                <a href="{{ route('users.index') }}" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary-custom">Update User</button>
            </div>
        </form>
    </div>
</div>
@endsection
