@extends('layouts.app')

@section('title', 'Products - Demo ERP')
@section('header_title', 'Products')

@section('content')
<div class="table-container mb-4">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center p-3 border-bottom">
        <h5 class="mb-0 fw-bold text-primary-custom me-3">Products List</h5>
        
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <form action="{{ route('products.index') }}" method="GET" class="d-flex align-items-center m-0 gap-2">
                <select name="group_id" class="form-select form-select-sm select2" onchange="this.form.submit()" style="min-width: 150px;">
                    <option value="">All Groups</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" {{ (isset($groupId) && $groupId == $group->id) ? 'selected' : '' }}>
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>
                
                @if(isset($groupId) && $groupId)
                    <select name="subgroup_id" class="form-select form-select-sm select2" onchange="this.form.submit()" style="min-width: 150px;">
                        <option value="">All Sub Groups</option>
                        @foreach($subgroups as $subgroup)
                            <option value="{{ $subgroup->id }}" {{ (isset($subgroupId) && $subgroupId == $subgroup->id) ? 'selected' : '' }}>
                                {{ $subgroup->name }}
                            </option>
                        @endforeach
                    </select>
                @endif
                
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Part Code or Item Name..." value="{{ $search ?? '' }}">
                    <button class="btn btn-outline-primary" type="button"><i class="ph ph-magnifying-glass"></i></button>
                </div>
                
                @if(isset($groupId) || isset($subgroupId) || isset($search))
                    <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary" title="Clear Filters"><i class="ph ph-x"></i></a>
                @endif
            </form>
            
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#importExcelModal">
                <i class="ph ph-file-xls"></i> Import Excel
            </button>
            <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">
                <i class="ph ph-plus"></i> Add New Product
            </a>
            <form action="{{ route('products.truncate') }}" method="POST" class="d-inline" onsubmit="return confirm('Are you absolutely sure you want to delete ALL products? This action cannot be undone.');">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm" title="Clear all items">
                    <i class="ph ph-trash"></i> Delete All
                </button>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="productsTable" class="data-table">
                <thead>
                    <tr>
                        <th>Part Code</th>
                        <th>Item Name</th>
                        <th>Available Stock</th>
                        <th>Unit</th>
                        <th>LP Price (₹)</th>
                        <th>Avg Pur. Rate (₹)</th>
                        <th>Pur. Disc (%)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td>
                            <span class="badge" style="border: 1px solid var(--border-color); color: var(--text-secondary);">{{ $product->part_code }}</span>
                            @if($product->abc_category)
                                @if($product->abc_category == 'A')
                                    <span class="badge ms-1" style="background-color: #d1fae5; color: #065f46; border: 1px solid #6ee7b7;" title="Fast Moving">A</span>
                                @elseif($product->abc_category == 'B')
                                    <span class="badge ms-1" style="background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d;" title="Medium Moving">B</span>
                                @else
                                    <span class="badge ms-1" style="background-color: #f3f4f6; color: #374151; border: 1px solid #d1d5db;" title="Slow Moving">C</span>
                                @endif
                            @endif
                        </td>
                        <td class="fw-bold">
                            <a href="{{ route('products.show', $product->id) }}" class="text-primary-custom text-decoration-none copyable-item"
                               data-clipboard="Item Name: {{ $product->item_name }} | Part Code: {{ $product->part_code }} | LP Price: ₹{{ number_format($product->lp_price, 2) }} | Avg Pur Rate: ₹{{ number_format($product->average_purchase_rate, 2) }} | Pur Disc: {{ number_format($product->average_purchase_discount, 2) }}% | Stock: {{ $product->available_stock }} {{ $product->unit }}">
                                {{ $product->item_name }}
                            </a>
                        </td>
                            <td class="text-center">
                                @php
                                    $physical = $product->available_stock + $product->reserved_stock;
                                @endphp
                                <span class="d-block fw-bold text-success" title="Available to Sell">{{ $product->available_stock }}</span>
                                <span class="d-block text-muted small" title="Reserved for Orders">{{ $product->reserved_stock }} Res.</span>
                            </td>
                        <td>{{ $product->unit }}</td>
                        <td>{{ number_format($product->lp_price, 2) }}</td>
                        <td>{{ number_format($product->average_purchase_rate, 2) }}</td>
                        <td>{{ number_format($product->average_purchase_discount, 2) }}%</td>
                        <td>
                            @if($product->status)
                                <span class="status-badge in-stock">Active</span>
                            @else
                                <span class="status-badge out-stock">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('products.show', $product->id) }}" class="icon-btn btn-sm" title="View"><i class="ph ph-eye"></i></a>
                                <a href="{{ route('products.edit', $product->id) }}" class="icon-btn btn-sm" title="Edit"><i class="ph ph-pencil-simple"></i></a>
                                <form action="{{ route('products.toggleStatus', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to {{ $product->status ? 'mark this product as inactive' : 'activate this product' }}?');">
                                    @csrf
                                    <button type="submit" class="icon-btn btn-sm {{ $product->status ? 'text-danger' : 'text-success' }}" title="{{ $product->status ? 'Deactivate' : 'Activate' }}">
                                        <i class="ph {{ $product->status ? 'ph-x-circle' : 'ph-check-circle' }}"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Import Excel Modal -->
<div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importExcelModalLabel">Import Products</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">Upload Excel File (.xlsx, .xls, .csv)</label>
                        <input class="form-control" type="file" id="excel_file" name="file" accept=".xlsx, .xls, .csv" required>
                        <div class="form-text">
                            Please ensure your Excel file contains headers like: <code>part_code, item_name, group, subgroup, unit, lp_price, gst_rate, hsn_code</code>.
                            <br>
                            <a href="{{ route('products.import.sample') }}" class="text-primary text-decoration-none mt-1 d-inline-block">
                                <i class="ph ph-download-simple"></i> Download Sample Template
                            </a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import Products</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Autofocus the appropriate filter on page load for faster keyboard navigation
    $(document).ready(function() {
        setTimeout(function() {
            @if(isset($subgroupId) && $subgroupId)
                // If subgroup is already selected, focus the search box
                $('input[name="search"]').focus();
            @elseif(isset($groupId) && $groupId)
                // If group is selected, focus subgroup
                $('select[name="subgroup_id"]').select2('focus');
            @else
                // Default: focus group
                $('select[name="group_id"]').select2('focus');
            @endif
        }, 100); // Small timeout to ensure select2 is fully initialized
    });

    $(document).ready(function() {
        var table = $('#productsTable').DataTable({
            "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        });
        
        // Link custom search box to DataTables for instant "as you type" filtering
        $('input[name="search"]').on('keyup', function() {
            table.search(this.value).draw();
        });
        
        // Prevent form submission when pressing Enter in search box
        $('input[name="search"]').on('keypress', function(e) {
            if (e.which == 13) {
                e.preventDefault();
            }
        });
        
        $('[data-bs-toggle="tooltip"]').tooltip();
        
        // Right-click to copy product details
        $(document).on('contextmenu', '.copyable-item', function(e) {
            e.preventDefault();
            var textToCopy = $(this).attr('data-clipboard');
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(textToCopy).then(function() {
                    toastr.success("Product details copied to clipboard!");
                }).catch(function(err) {
                    toastr.error("Failed to copy details.");
                });
            } else {
                // Fallback for older browsers
                var $temp = $("<textarea>");
                $("body").append($temp);
                $temp.val(textToCopy).select();
                document.execCommand("copy");
                $temp.remove();
                toastr.success("Product details copied to clipboard!");
            }
        });
    });
</script>
@endpush
