@extends('layouts.app')

@section('title', 'User Management - Demo ERP')
@section('header_title', 'User Management')

@section('content')
<div class="card card-custom mb-4">
    <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="m-0 fw-bold text-primary-custom"><i class="ph ph-users me-2"></i> Users List</h5>
        <div>
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary me-2"><i class="ph ph-shield-check"></i> Manage Roles</a>
            <a href="{{ route('users.create') }}" class="btn btn-primary-custom"><i class="ph ph-plus"></i> Add New User</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4">#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th class="text-center px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td class="px-4">{{ $loop->iteration }}</td>
                        <td class="fw-bold">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @forelse($user->roles as $role)
                                <span class="badge bg-info text-white">{{ $role->name }}</span>
                            @empty
                                <span class="badge bg-secondary text-white">No Role</span>
                            @endforelse
                        </td>
                        <td class="text-center px-4">
                            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
