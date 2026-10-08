@extends('layouts.app')

@section('title', 'Edit Ledger Group - ' . $accountGroup->name)
@section('header_title', 'Ledger Groups')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Ledger Group: <span class="text-primary">{{ $accountGroup->name }}</span></h2>
    <div>
        <a href="{{ route('account-groups.index') }}" class="btn btn-secondary me-2">
            <i class="ph ph-arrow-left"></i> Back to List
        </a>
        <a href="{{ route('account-groups.show', $accountGroup->id) }}" class="btn btn-info text-white">
            <i class="ph ph-eye"></i> View Details
        </a>
    </div>
</div>

@if($accountGroup->is_system)
<div class="alert alert-warning mb-4">
    <i class="ph ph-warning-circle me-2"></i> <strong>System Group:</strong> This is a core accounting group. You cannot change its parent hierarchy or accounting nature to prevent breaking the application's financial integrity.
</div>
@endif

<div class="card shadow-sm border-0" style="max-width: 800px;">
    <div class="card-body p-4">
        <form action="{{ route('account-groups.update', $accountGroup->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label fw-bold">Group Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $accountGroup->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="parent_id" class="form-label fw-bold">Parent Group</label>
                <select class="form-select @error('parent_id') is-invalid @enderror select2" id="parent_id" name="parent_id" {{ $accountGroup->is_system ? 'disabled' : '' }}>
                    <option value="">-- None (Root Group) --</option>
                    @foreach($parentGroups as $pGroup)
                        <option value="{{ $pGroup->id }}" data-nature="{{ $pGroup->nature }}" {{ old('parent_id', $accountGroup->parent_id) == $pGroup->id ? 'selected' : '' }}>
                            {{ $pGroup->name }} ({{ $pGroup->nature }})
                        </option>
                    @endforeach
                </select>
                @if(!$accountGroup->is_system)
                <div class="form-text">Select a parent group to create a hierarchy. Note: A group cannot be a parent of itself or its ancestors.</div>
                @endif
                @error('parent_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @if($accountGroup->is_system && $accountGroup->parent_id)
                    <input type="hidden" name="parent_id" value="{{ $accountGroup->parent_id }}">
                @endif
            </div>

            <div class="mb-3">
                <label for="nature" class="form-label fw-bold">Root Accounting Nature <span class="text-danger">*</span></label>
                <select class="form-select @error('nature') is-invalid @enderror" id="nature" name="nature" {{ $accountGroup->is_system ? 'disabled' : '' }} required>
                    <option value="">-- Select Root Nature --</option>
                    @foreach($rootNatures as $nature)
                        <option value="{{ $nature }}" {{ old('nature', $accountGroup->nature) == $nature ? 'selected' : '' }}>{{ $nature }}</option>
                    @endforeach
                </select>
                <div class="form-text" id="nature_help">This determines the fundamental accounting classification (e.g. Asset, Liability) and Normal Balance.</div>
                @error('nature')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @if($accountGroup->is_system)
                    <input type="hidden" name="nature" value="{{ $accountGroup->nature }}">
                @endif
            </div>

            <div class="mb-3">
                <label for="description" class="form-label fw-bold">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $accountGroup->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $accountGroup->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="is_active">Active</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="sort_order" class="form-label fw-bold">Sort Order</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" value="{{ old('sort_order', $accountGroup->sort_order) }}">
                </div>
            </div>

            <div class="d-flex justify-content-end border-top pt-3">
                <button type="submit" class="btn btn-primary px-4">Update Ledger Group</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        @if(!$accountGroup->is_system)
        // Auto-select nature based on parent group
        $('#parent_id').change(function() {
            var selectedOption = $(this).find('option:selected');
            var nature = selectedOption.data('nature');
            
            if (nature) {
                $('#nature').val(nature);
                // Highlight to user that nature is forced by parent
                $('#nature').addClass('border-success bg-success-subtle');
                $('#nature_help').html('<span class="text-success fw-bold">Nature is inherited from the selected parent group.</span>');
            } else {
                $('#nature').removeClass('border-success bg-success-subtle');
                $('#nature_help').html('This determines the fundamental accounting classification (e.g. Asset, Liability) and Normal Balance.');
            }
        });

        // Trigger on load if old value exists
        if($('#parent_id').val() && !{{ old('nature') ? 'true' : 'false' }}) {
            $('#parent_id').trigger('change');
        }
        @endif
    });
</script>
@endsection
