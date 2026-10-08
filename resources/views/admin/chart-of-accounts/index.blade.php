@extends('layouts.app')

@section('title', 'Chart of Accounts')
@section('header_title', 'Chart of Accounts')

@section('content')
<style>
    .coa-tree {
        list-style-type: none;
        padding-left: 0;
    }
    .coa-tree ul {
        list-style-type: none;
        padding-left: 2rem;
        position: relative;
    }
    .coa-tree ul::before {
        content: "";
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0.75rem;
        border-left: 1px dashed #cbd5e1;
    }
    .coa-node {
        position: relative;
        padding: 0.5rem 0;
        display: flex;
        align-items: center;
    }
    .coa-node::before {
        content: "";
        position: absolute;
        top: 1.25rem;
        left: -1.25rem;
        width: 1rem;
        border-top: 1px dashed #cbd5e1;
    }
    .coa-root > .coa-node::before {
        display: none;
    }
    .coa-node-icon {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        margin-right: 0.75rem;
        font-size: 14px;
        cursor: pointer;
        z-index: 1;
        background: white;
    }
    .coa-group-icon {
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    .coa-group-icon:hover {
        background-color: #e2e8f0;
    }
    .coa-ledger-icon {
        background-color: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
        cursor: default;
    }
    .coa-node-content {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 1rem;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        transition: all 0.2s;
    }
    .coa-node-content:hover {
        background-color: #fff;
        border-color: #cbd5e1;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .coa-node-title {
        font-weight: 600;
        color: #334155;
    }
    .coa-ledger-title {
        font-weight: 500;
        color: #0f172a;
    }
    .coa-actions {
        opacity: 0;
        transition: opacity 0.2s;
    }
    .coa-node-content:hover .coa-actions {
        opacity: 1;
    }
    .badge-nature {
        font-size: 11px;
        padding: 0.35em 0.65em;
        font-weight: 500;
    }
    .loading-spinner {
        display: none;
        width: 1rem;
        height: 1rem;
        border: 2px solid #e2e8f0;
        border-top-color: #3b82f6;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-left: 10px;
    }
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>

<div class="card mb-4 shadow-sm border-0">
    <div class="card-body">
        <form action="{{ route('chart-of-accounts.index') }}" method="GET" class="row g-3">
            <div class="col-md-9">
                <label class="form-label text-muted" style="font-size: 12px; font-weight: 600; text-transform: uppercase;">Search Chart of Accounts</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="ph ph-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search by Group Name, Ledger Name...">
                </div>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary px-4">Search</button>
                <a href="{{ route('chart-of-accounts.index') }}" class="btn btn-outline-secondary px-3">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        @if($isSearch)
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                <h5 class="mb-0 fw-bold">Search Results for "<span class="text-primary">{{ $searchTerm }}</span>"</h5>
                <span class="badge bg-secondary">{{ $results->count() }} found</span>
            </div>

            @if($results->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="ph ph-magnifying-glass d-block mb-2" style="font-size: 32px; color: #cbd5e1;"></i>
                    No accounts found matching your search.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Account Name</th>
                                <th>Accounting Path</th>
                                <th>Root Nature</th>
                                <th>Balance</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                                <tr>
                                    <td>
                                        @if($result->is_ledger)
                                            <span class="badge bg-success-subtle text-success"><i class="ph ph-file-text me-1"></i> Ledger</span>
                                        @else
                                            <span class="badge bg-primary-subtle text-primary"><i class="ph ph-folder me-1"></i> Group</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-dark">{{ $result->name }}</td>
                                    <td class="text-muted" style="font-size: 13px;">{!! $result->full_path !!}</td>
                                    <td>
                                        <span class="badge text-white" style="background-color: 
                                            {{ $result->nature == 'Assets' ? '#3b82f6' : 
                                              ($result->nature == 'Liabilities' ? '#ef4444' : 
                                              ($result->nature == 'Income' ? '#10b981' : 
                                              ($result->nature == 'Expenses' ? '#f59e0b' : '#6366f1'))) }};">
                                            {{ $result->nature }}
                                        </span>
                                    </td>
                                    <td class="fw-bold" style="color: {{ $result->normal_balance == 'Dr' ? '#16a34a' : '#dc2626' }}">{{ $result->normal_balance }}</td>
                                    <td class="text-end">
                                        @if($result->is_ledger)
                                            <a href="{{ route('inventory.ledger.index', ['ledger_id' => $result->id]) }}" class="btn btn-sm btn-outline-info" title="View Ledger"><i class="ph ph-eye"></i></a>
                                        @else
                                            <a href="{{ route('account-groups.show', $result->id) }}" class="btn btn-sm btn-outline-primary" title="View Group"><i class="ph ph-eye"></i></a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        @else
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                <h5 class="mb-0 fw-bold">Chart of Accounts Hierarchy</h5>
            </div>

            <ul class="coa-tree" id="coa-root">
                @foreach($roots as $root)
                    <li class="coa-root" data-id="{{ $root->id }}" data-loaded="false">
                        <div class="coa-node">
                            @if(($root->children_count + $root->ledgers_count) > 0)
                                <div class="coa-node-icon coa-group-icon toggle-node" onclick="toggleNode(event, this, {{ $root->id }})">
                                    <i class="ph ph-plus"></i>
                                </div>
                            @else
                                <div class="coa-node-icon" style="background: transparent; border: none; cursor: default;"></div>
                            @endif
                            <div class="coa-node-content">
                                <div class="d-flex align-items-center">
                                    <span class="coa-node-title fs-5 me-3">{{ $root->name }}</span>
                                    <span class="badge text-white badge-nature" style="background-color: 
                                        {{ $root->nature == 'Assets' ? '#3b82f6' : 
                                          ($root->nature == 'Liabilities' ? '#ef4444' : 
                                          ($root->nature == 'Income' ? '#10b981' : 
                                          ($root->nature == 'Expenses' ? '#f59e0b' : '#6366f1'))) }};">
                                        {{ $root->nature }}
                                    </span>
                                    <div class="loading-spinner"></div>
                                </div>
                                <div class="coa-actions">
                                    <span class="text-muted fw-medium me-3" style="font-size: 13px;">{{ $root->normal_balance == 'Dr' ? 'Debit (Dr)' : 'Credit (Cr)' }}</span>
                                    <a href="{{ route('account-groups.show', $root->id) }}" class="btn btn-sm btn-light border" title="View Group Details"><i class="ph ph-arrow-square-out"></i> View</a>
                                </div>
                            </div>
                        </div>
                        <ul class="d-none" id="children-{{ $root->id }}"></ul>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleNode(event, iconElement, parentId) {
        event.stopPropagation();
        const li = $(iconElement).closest('li');
        const ul = $('#children-' + parentId);
        const icon = $(iconElement).find('i');
        const isLoaded = li.attr('data-loaded') === 'true';

        if (ul.hasClass('d-none')) {
            // Expanding
            if (!isLoaded) {
                loadChildren(parentId, li, ul, icon);
            } else {
                ul.removeClass('d-none');
                icon.removeClass('ph-plus').addClass('ph-minus');
            }
        } else {
            // Collapsing
            ul.addClass('d-none');
            icon.removeClass('ph-minus').addClass('ph-plus');
        }
    }

    function loadChildren(parentId, li, ul, icon) {
        const spinner = li.find('.loading-spinner').first();
        spinner.show();
        icon.removeClass('ph-plus').addClass('ph-spinner ph-spin');

        $.ajax({
            url: '{{ route("chart-of-accounts.index") }}',
            type: 'GET',
            data: { parent_id: parentId },
            success: function(response) {
                let html = '';
                
                // Render Groups
                if (response.groups && response.groups.length > 0) {
                    response.groups.forEach(group => {
                        let hasChildren = (group.children_count + group.ledgers_count) > 0;
                        let iconHtml = hasChildren 
                            ? `<div class="coa-node-icon coa-group-icon toggle-node" onclick="toggleNode(event, this, ${group.id})"><i class="ph ph-plus"></i></div>`
                            : `<div class="coa-node-icon" style="background: transparent; border: none; cursor: default;"></div>`;
                        
                        html += `
                            <li data-id="${group.id}" data-loaded="false">
                                <div class="coa-node">
                                    ${iconHtml}
                                    <div class="coa-node-content">
                                        <div class="d-flex align-items-center">
                                            <i class="ph ph-folder text-primary me-2"></i>
                                            <span class="coa-node-title">${group.name}</span>
                                            ${group.is_system ? '<span class="badge bg-secondary text-white ms-2" style="font-size: 10px;">SYSTEM</span>' : ''}
                                            <div class="loading-spinner"></div>
                                        </div>
                                        <div class="coa-actions">
                                            <span class="fw-bold me-3" style="font-size: 12px; color: ${group.normal_balance == 'Dr' ? '#16a34a' : '#dc2626'}">${group.normal_balance}</span>
                                            <a href="/account-groups/${group.id}" class="btn btn-sm btn-light border" title="View Group Details"><i class="ph ph-eye"></i></a>
                                        </div>
                                    </div>
                                </div>
                                <ul class="d-none" id="children-${group.id}"></ul>
                            </li>
                        `;
                    });
                }
                
                // Render Ledgers
                if (response.ledgers && response.ledgers.length > 0) {
                    response.ledgers.forEach(ledger => {
                        html += `
                            <li>
                                <div class="coa-node">
                                    <div class="coa-node-icon coa-ledger-icon">
                                        <i class="ph ph-file-text"></i>
                                    </div>
                                    <div class="coa-node-content" style="background-color: #fff;">
                                        <div class="d-flex align-items-center">
                                            <span class="coa-ledger-title">${ledger.name}</span>
                                        </div>
                                        <div class="coa-actions">
                                            <a href="/inventory/ledger?ledger_id=${ledger.id}" class="btn btn-sm btn-outline-info" title="View Ledger"><i class="ph ph-chart-line-up"></i> Ledger Report</a>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        `;
                    });
                }
                
                if (html === '') {
                    html = `
                        <li>
                            <div class="coa-node">
                                <div class="coa-node-icon" style="background: transparent; border: none; cursor: default;"></div>
                                <div class="coa-node-content" style="background: transparent; border: none;">
                                    <span class="text-muted fst-italic" style="font-size: 13px;">No child groups or ledgers.</span>
                                </div>
                            </div>
                        </li>
                    `;
                }
                
                ul.html(html);
                ul.removeClass('d-none');
                li.attr('data-loaded', 'true');
            },
            error: function() {
                toastr.error('Failed to load child accounts.');
            },
            complete: function() {
                spinner.hide();
                icon.removeClass('ph-spinner ph-spin').addClass('ph-minus');
            }
        });
    }


</script>
@endpush
