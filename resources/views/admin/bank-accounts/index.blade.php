@extends('layouts.app')

@section('title', 'Bank Accounts - Demo ERP')
@section('header_title', 'Banking')

@section('content')
<div class="row">
    <!-- Side Tabs -->
    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="list-group list-group-flush rounded">
                <a href="{{ route('bank-accounts.index') }}" class="list-group-item list-group-item-action {{ $activeTab == 'accounts' ? 'active' : '' }} border-0 py-3">
                    <i class="ph ph-bank me-2"></i> Bank Accounts
                </a>
                <a href="{{ route('bank-accounts.feeds') }}" class="list-group-item list-group-item-action {{ $activeTab == 'feeds' ? 'active' : '' }} border-0 py-3">
                    <i class="ph ph-arrows-left-right me-2"></i> Banking Feeds
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="col-md-9">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="d-flex align-items-center">
                            <span class="me-2 text-muted">Active</span>
                            <select class="form-select form-select-sm w-auto">
                                <option>Yes</option>
                                <option>No</option>
                            </select>
                        </div>
                    </div>
                    <a href="{{ route('bank-accounts.create') }}" class="btn btn-primary fw-semibold rounded-pill px-4">Add bank account</a>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm w-auto">
                            <option>25</option>
                            <option>50</option>
                        </select>
                        <button class="btn btn-sm btn-light border">Export</button>
                        <button class="btn btn-sm btn-light border">Bulk actions</button>
                        <button class="btn btn-sm btn-light border"><i class="ph ph-arrows-clockwise"></i></button>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success py-2">{{ session('success') }}</div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover align-middle border">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;"><input class="form-check-input" type="checkbox"></th>
                                <th>Name</th>
                                <th>Parent account</th>
                                <th>Type</th>
                                <th>Detail type</th>
                                <th>Primary balance</th>
                                <th>Bank balance</th>
                                <th>Active</th>
                                <th>Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $account)
                                <tr>
                                    <td><input class="form-check-input" type="checkbox"></td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $account->bank_name }}</div>
                                        @if($account->account_number)
                                            <div class="small text-muted">{{ $account->account_number }} - {{ $account->account_name }}</div>
                                        @endif
                                    </td>
                                    <td class="text-muted">None</td>
                                    <td class="text-muted">Bank</td>
                                    <td class="text-muted">Bank</td>
                                    <td>₹{{ number_format($account->opening_balance, 2) }}</td>
                                    <td>₹{{ number_format($account->opening_balance, 2) }}</td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch" data-id="{{ $account->id }}" {{ $account->is_active ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <a href="{{ route('bank-accounts.history', $account) }}" class="btn btn-sm btn-light border" title="Account History">
                                                <i class="ph ph-clock-counter-clockwise"></i>
                                            </a>
                                            <a href="{{ route('bank-accounts.edit', $account) }}" class="btn btn-sm btn-light border" title="Edit">
                                                <i class="ph ph-pencil-simple"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No bank accounts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <span class="small text-muted">Showing 1 to {{ $accounts->count() }} of {{ $accounts->count() }} entries</span>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item disabled"><a class="page-link" href="#">Next</a></li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.status-toggle');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const accountId = this.dataset.id;
            
            fetch(`/bank-accounts/${accountId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if(!data.success) {
                    alert('Failed to update status');
                    this.checked = !this.checked; // Revert visually
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
                this.checked = !this.checked; // Revert visually
            });
        });
    });
});
</script>
@endpush
@endsection
