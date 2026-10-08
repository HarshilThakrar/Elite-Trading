    @extends('layouts.app')

@section('title', 'Ledger Group Details - ' . $accountGroup->name)
@section('header_title', 'Ledger Groups')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Ledger Group: <span class="text-primary">{{ $accountGroup->name }}</span></h2>
    <div>
        <a href="{{ route('account-groups.index') }}" class="btn btn-secondary me-2">
            <i class="ph ph-arrow-left"></i> Back to List
        </a>
        <a href="{{ route('account-groups.edit', $accountGroup->id) }}" class="btn btn-primary">
            <i class="ph ph-pencil"></i> Edit Group
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Group Details</h5>
                @if($accountGroup->is_system)
                    <span class="badge bg-secondary text-white">SYSTEM GROUP</span>
                @endif
            </div>
            <div class="card-body p-0">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr class="border-bottom">
                            <th class="ps-4 py-3 text-muted w-40">Group Name</th>
                            <td class="py-3 fw-bold text-dark">{{ $accountGroup->name }}</td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="ps-4 py-3 text-muted">Parent Group</th>
                            <td class="py-3">
                                @if($accountGroup->parent)
                                    <a href="{{ route('account-groups.show', $accountGroup->parent_id) }}" class="text-decoration-none fw-medium">
                                        {{ $accountGroup->parent->name }}
                                    </a>
                                @else
                                    <span class="text-muted fst-italic">None (Root Group)</span>
                                @endif
                            </td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="ps-4 py-3 text-muted">Root Nature</th>
                            <td class="py-3">
                                <span class="badge text-white" style="background-color: 
                                    {{ $accountGroup->nature == 'Assets' ? '#3b82f6' : 
                                      ($accountGroup->nature == 'Liabilities' ? '#ef4444' : 
                                      ($accountGroup->nature == 'Income' ? '#10b981' : 
                                      ($accountGroup->nature == 'Expenses' ? '#f59e0b' : '#6366f1'))) }}; font-weight: 500;">
                                    {{ $accountGroup->nature }}
                                </span>
                            </td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="ps-4 py-3 text-muted">Normal Balance</th>
                            <td class="py-3 fw-bold" style="color: {{ $accountGroup->normal_balance == 'Dr' ? '#16a34a' : '#dc2626' }}">
                                {{ $accountGroup->normal_balance == 'Dr' ? 'Debit (Dr)' : 'Credit (Cr)' }}
                            </td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="ps-4 py-3 text-muted">Status</th>
                            <td class="py-3">
                                @if($accountGroup->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">Inactive</span>
                                @endif
                            </td>
                        </tr>
                        <tr class="border-bottom">
                            <th class="ps-4 py-3 text-muted">Sort Order</th>
                            <td class="py-3 text-dark">{{ $accountGroup->sort_order }}</td>
                        </tr>
                        <tr>
                            <th class="ps-4 py-3 text-muted">Description</th>
                            <td class="py-3 text-dark">{{ $accountGroup->description ?: '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Child Groups</h5>
                <span class="badge bg-primary text-white rounded-pill">{{ $accountGroup->children->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($accountGroup->children->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($accountGroup->children as $child)
                            <a href="{{ route('account-groups.show', $child->id) }}" class="list-group-item list-group-item-action py-3 px-4 d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="ph ph-tree-structure text-muted me-2"></i>
                                    <span class="fw-medium">{{ $child->name }}</span>
                                    @if($child->is_system)
                                        <span class="badge bg-secondary text-white ms-2" style="font-size: 10px;">SYSTEM</span>
                                    @endif
                                </div>
                                <i class="ph ph-caret-right text-muted"></i>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-5 text-center text-muted">
                        <i class="ph ph-folder-open d-block mb-2" style="font-size: 32px; color: #cbd5e1;"></i>
                        No child groups exist under this group.
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Assigned Ledgers</h5>
                <span class="badge bg-info text-white rounded-pill">{{ $accountGroup->ledgers_count }}</span>
            </div>
            <div class="card-body p-0">
                @if($accountGroup->ledgers_count > 0)
                    <div class="p-4 text-muted">
                        <i class="ph ph-info me-2 text-primary"></i> Ledger Management module will manage ledger creation, editing and assignment.
                    </div>
                @else
                    <div class="p-5 text-center text-muted">
                        <i class="ph ph-books d-block mb-2" style="font-size: 32px; color: #cbd5e1;"></i>
                        No ledgers are assigned directly to this group.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
