@extends('layouts.app')

@section('title', 'Edit Product - Demo ERP')
@section('header_title', 'Edit Product')

@section('content')
<div class="card card-custom">
    <div class="card-body">
        <form action="{{ route('products.update', $product->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Part Code <span class="text-danger">*</span></label>
                    <input type="text" name="part_code" class="form-control @error('part_code') is-invalid @enderror" value="{{ old('part_code', $product->part_code) }}" required>
                    @error('part_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Item Name <span class="text-danger">*</span></label>
                    <input type="text" name="item_name" class="form-control @error('item_name') is-invalid @enderror" value="{{ old('item_name', $product->item_name) }}" required>
                    @error('item_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Group <span class="text-danger">*</span></label>
                    <select name="product_group_id" id="product_group_id" class="form-select select2 @error('product_group_id') is-invalid @enderror" required>
                        <option value="">Select Group</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ old('product_group_id', $product->product_group_id) == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                    @error('product_group_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Sub Group</label>
                    <select name="product_subgroup_id" id="product_subgroup_id" class="form-select select2 @error('product_subgroup_id') is-invalid @enderror">
                        <option value="">Select Sub Group</option>
                        @foreach($subgroups as $subgroup)
                            <option value="{{ $subgroup->id }}" {{ old('product_subgroup_id', $product->product_subgroup_id) == $subgroup->id ? 'selected' : '' }}>{{ $subgroup->name }}</option>
                        @endforeach
                    </select>
                    @error('product_subgroup_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">HSN Code</label>
                    <input type="text" name="hsn_code" class="form-control @error('hsn_code') is-invalid @enderror" value="{{ old('hsn_code', $product->hsn_code) }}" placeholder="e.g. 84733099">
                    @error('hsn_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">GST Rate (%)</label>
                    <select name="gst_rate" class="form-select @error('gst_rate') is-invalid @enderror">
                        <option value="0" {{ old('gst_rate', $product->gst_rate) == '0' ? 'selected' : '' }}>0% (Exempt)</option>
                        <option value="5" {{ old('gst_rate', $product->gst_rate) == '5' ? 'selected' : '' }}>5%</option>
                        <option value="12" {{ old('gst_rate', $product->gst_rate) == '12' ? 'selected' : '' }}>12%</option>
                        <option value="18" {{ old('gst_rate', $product->gst_rate) == '18' ? 'selected' : '' }}>18%</option>
                        <option value="28" {{ old('gst_rate', $product->gst_rate) == '28' ? 'selected' : '' }}>28%</option>
                    </select>
                    @error('gst_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>




            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $product->description) }}</textarea>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Unit <span class="text-danger">*</span></label>
                    <input type="text" name="unit" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit', $product->unit) }}" required>
                    @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">LP Price (₹) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="lp_price" class="form-control @error('lp_price') is-invalid @enderror" value="{{ old('lp_price', $product->lp_price) }}" required>
                    @error('lp_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Avail. Stock <span class="text-danger">*</span></label>
                    <input type="number" name="available_stock" class="form-control @error('available_stock') is-invalid @enderror" value="{{ old('available_stock', $product->available_stock) }}" required>
                    @error('available_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Min Stock</label>
                    <input type="number" name="minimum_stock" class="form-control" value="{{ old('minimum_stock', $product->minimum_stock) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Reorder Lvl</label>
                    <input type="number" name="reorder_level" class="form-control" value="{{ old('reorder_level', $product->reorder_level) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Max Stock</label>
                    <input type="number" name="maximum_stock" class="form-control @error('maximum_stock') is-invalid @enderror" value="{{ old('maximum_stock', $product->maximum_stock) }}">
                    @error('maximum_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            
            <div class="mb-4 form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="statusSwitch" name="status" {{ $product->status ? 'checked' : '' }}>
                <label class="form-check-label" for="statusSwitch">Active Product</label>
            </div>

            <div class="d-flex justify-content-end">
                <a href="{{ route('products.index') }}" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary-custom">Update Product</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#product_group_id').on('change', function() {
        const groupId = $(this).val();
        const subgroupSelect = $('#product_subgroup_id');
        
        subgroupSelect.empty().append('<option value="">Select Sub Group</option>');
        
        if (groupId) {
            subgroupSelect.empty().append('<option value="">Loading...</option>');
            subgroupSelect.trigger('change');
            
            $.get(`/api/product-subgroups/${groupId}`, function(data) {
                subgroupSelect.empty().append('<option value="">Select Sub Group</option>');
                data.forEach(subgroup => {
                    let option = new Option(subgroup.name, subgroup.id);
                    if (subgroup.id == '{{ old('product_subgroup_id', $product->product_subgroup_id) }}') {
                        option.selected = true;
                    }
                    subgroupSelect.append(option);
                });
                // trigger select2 update
                subgroupSelect.trigger('change');
            }).fail(function() {
                subgroupSelect.empty().append('<option value="">Error loading subgroups</option>');
                subgroupSelect.trigger('change');
            });
        } else {
            subgroupSelect.empty().append('<option value="">Select Sub Group</option>');
            subgroupSelect.trigger('change');
        }
    });

    // Enter key to move to next input like Tab
    $('input, select').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var inputs = $(this).closest('form').find(':input:visible:not([disabled]):not([readonly])');
            var index = inputs.index(this);
            if (index > -1 && (index + 1) < inputs.length) {
                var nextInput = inputs.eq(index + 1);
                nextInput.focus();
                if (nextInput.hasClass('select2-hidden-accessible')) {
                    nextInput.select2('open');
                }
            } else {
                $(this).closest('form').submit();
            }
        }
    });

    // Move to next input after selecting from Select2
    $('.select2').on('select2:select', function(e) {
        var inputs = $(this).closest('form').find(':input:visible:not([disabled]):not([readonly])');
        var index = inputs.index(this);
        if (index > -1 && (index + 1) < inputs.length) {
            var nextInput = inputs.eq(index + 1);
            setTimeout(function() {
                nextInput.focus();
                if (nextInput.hasClass('select2-hidden-accessible')) {
                    nextInput.select2('open');
                }
            }, 50);
        }
    });
});
</script>
@endpush
