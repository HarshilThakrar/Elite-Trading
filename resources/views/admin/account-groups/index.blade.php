@extends('layouts.app')

@section('title', 'Ledger Groups')
@section('header_title', 'Ledger Groups')

@section('content')
<div class="d-flex justify-content-end align-items-center mb-4">
    <a href="{{ route('account-groups.create') }}" class="btn btn-primary">
        <i class="ph ph-plus"></i> Create Group
    </a>
</div>

<div class="card mb-4 shadow-sm border-0">
    <div class="card-body">
        <form action="{{ route('account-groups.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label text-muted" style="font-size: 12px; font-weight: 600; text-transform: uppercase;">Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Group Name...">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted" style="font-size: 12px; font-weight: 600; text-transform: uppercase;">Root Nature</label>
                <select name="nature" class="form-select">
                    <option value="">All Natures</option>
                    @foreach($rootNatures as $n)
                        <option value="{{ $n }}" {{ request('nature') == $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted" style="font-size: 12px; font-weight: 600; text-transform: uppercase;">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="ph ph-magnifying-glass me-1"></i> Search</button>
                <a href="{{ route('account-groups.index') }}" class="btn btn-outline-secondary px-3">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Group Name</th>
                        <th>Parent Group</th>
                        <th>Root Nature</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $group)
                    <tr>
                        <td class="ps-4 fw-medium text-dark">
                            {{ $group->name }}
                            @if($group->is_system)
                                <span class="badge bg-secondary text-white ms-2" style="font-size: 10px;">SYSTEM</span>
                            @endif
                        </td>
                        <td class="text-muted">
                            {{ $group->parent ? $group->parent->name : '-' }}
                        </td>
                        <td>
                            <span class="badge text-white" style="background-color: 
                                {{ $group->nature == 'Assets' ? '#3b82f6' : 
                                  ($group->nature == 'Liabilities' ? '#ef4444' : 
                                  ($group->nature == 'Income' ? '#10b981' : 
                                  ($group->nature == 'Expenses' ? '#f59e0b' : '#6366f1'))) }}; font-weight: 500;">
                                {{ $group->nature }}
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold" style="color: {{ $group->normal_balance == 'Dr' ? '#16a34a' : '#dc2626' }}">{{ $group->normal_balance }}</span>
                        </td>
                        <td>
                            @if($group->is_active)
                                <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('account-groups.show', $group->id) }}" class="btn btn-sm btn-outline-info" title="View"><i class="ph ph-eye"></i></a>
                            <a href="{{ route('account-groups.edit', $group->id) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="ph ph-pencil"></i></a>
                            @if(!$group->is_system && $group->children()->count() == 0 && $group->ledgers_count == 0)
                                <form action="{{ route('account-groups.destroy', $group->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this Ledger Group?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="ph ph-trash"></i></button>
                                </form>
                            @else
                                <button type="button" class="btn btn-sm btn-outline-secondary disabled" title="Cannot delete"><i class="ph ph-trash"></i></button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            No Ledger Groups found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($groups->hasPages())
    <div class="card-footer bg-white border-top-0 pt-3 pb-3 px-4">
        {{ $groups->links() }}
    </div>
    @endif
</div>
@endsection
