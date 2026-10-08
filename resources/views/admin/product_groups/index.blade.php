@extends('layouts.app')
@section('title', 'Product Groups - Demo ERP')
@section('header_title', 'Product Groups')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Product Groups</h5>
        <a href="{{ route('product-groups.create') }}" class="btn btn-primary-custom">Add Group</a>
    </div>
    <div class="card-body">
        <table class="table">
            <thead><tr><th>ID</th><th>Name</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach($groups as $group)
                <tr>
                    <td>{{ $group->id }}</td>
                    <td><a href="{{ route('product-groups.edit', $group->id) }}" class="fw-semibold text-primary-custom text-decoration-none">{{ $group->name }}</a></td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="{{ route('product-groups.edit', $group->id) }}" class="btn btn-sm btn-primary">Edit</a>
                            <form action="{{ route('product-groups.destroy', $group->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product group?');">
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
