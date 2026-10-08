@extends('layouts.app')

@section('title', 'Role Management - Demo ERP')
@section('header_title', 'Role Management')

@section('content')
<div class="card card-custom mb-4">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="m-0 fw-bold text-primary-custom"><i class="ph ph-shield-check me-2"></i> Roles List</h5>
        <div>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary me-2"><i class="ph ph-users"></i> Back to Users</a>
            <a href="{{ route('roles.create') }}" class="btn btn-primary-custom"><i class="ph ph-plus"></i> Add New Role</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4">#</th>
                        <th>Role Name</th>
                        <th>Permissions</th>
                        <th class="text-center px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                    <tr>
                        <td class="px-4">{{ $loop->iteration }}</td>
                        <td class="fw-bold">{{ $role->name }}</td>
                        <td>
                            @if($role->name === 'Super Admin')
                                <span class="badge bg-success text-white">All Permissions</span>
                            @else
                                @forelse($role->permissions->take(5) as $permission)
                                    <span class="badge bg-secondary text-white">{{ $permission->name }}</span>
                                @empty
                                    <span class="text-muted small">No permissions</span>
                                @endforelse
                                @if($role->permissions->count() > 5)
                                    <span class="badge bg-light text-dark">+{{ $role->permissions->count() - 5 }} more</span>
                                @endif
                            @endif
                        </td>
                        <td class="text-center px-4">
                            @if($role->name !== 'Super Admin')
                            <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this role?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
