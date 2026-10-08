@extends('layouts.app')
@section('title', 'Product Sub Groups - Demo ERP')
@section('header_title', 'Product Sub Groups')
@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Product Sub Groups</h5>
            <a href="{{ route('product-subgroups.create') }}" class="btn btn-primary-custom">Add Sub Group</a>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Group</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subgroups as $subgroup)
                        <tr>
                            <td>{{ $subgroup->id }}</td>
                            <td>{{ $subgroup->group->name ?? 'N/A' }}</td>
                            <td><a href="{{ route('product-subgroups.edit', $subgroup->id) }}"
                                    class="fw-semibold text-primary-custom text-decoration-none">{{ $subgroup->name }}</a></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('product-subgroups.edit', $subgroup->id) }}"
                                        class="btn btn-sm btn-primary">Edit</a>
                                    <form action="{{ route('product-subgroups.destroy', $subgroup->id) }}" method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this product subgroup?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection