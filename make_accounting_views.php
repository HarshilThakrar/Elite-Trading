<?php

$resources = [
    'financial-years' => 'Financial Year',
    'account-groups' => 'Account Group',
    'ledgers' => 'Ledger',
];

$baseDir = __DIR__ . '/resources/views/admin/';

foreach ($resources as $dir => $title) {
    $resourceDir = $baseDir . $dir;
    if (!is_dir($resourceDir)) {
        mkdir($resourceDir, 0777, true);
    }
    
    // Index
    file_put_contents($resourceDir . '/index.blade.php', "@extends('layouts.app')\n@section('title', '{$title}s - Demo ERP')\n@section('header_title', '{$title}s')\n@section('content')\n<div class='card'>\n    <div class='card-header d-flex justify-content-between align-items-center'>\n        <h5 class='mb-0'>Manage {$title}s</h5>\n        <a href='{{ route(\"{$dir}.create\") }}' class='btn btn-primary'>Add {$title}</a>\n    </div>\n    <div class='card-body'>\n        <div class='table-responsive'>\n            <table class='table'>\n                <thead>\n                    <tr>\n                        <th>Name</th>\n                        <th>Actions</th>\n                    </tr>\n                </thead>\n                <tbody>\n                    @foreach(\${$dir} ?? [] as \$item)\n                    <tr>\n                        <td>{{ \$item->name ?? \$item->id }}</td>\n                        <td>\n                            <a href='{{ route(\"{$dir}.edit\", \$item) }}' class='btn btn-sm btn-primary'>Edit</a>\n                        </td>\n                    </tr>\n                    @endforeach\n                </tbody>\n            </table>\n        </div>\n    </div>\n</div>\n@endsection");
    
    // Create
    file_put_contents($resourceDir . '/create.blade.php', "@extends('layouts.app')\n@section('title', 'Add {$title} - Demo ERP')\n@section('header_title', 'Add {$title}')\n@section('content')\n<div class='card'>\n    <div class='card-body'>\n        <form action='{{ route(\"{$dir}.store\") }}' method='POST'>\n            @csrf\n            <div class='mb-3'>\n                <label class='form-label'>Name</label>\n                <input type='text' name='name' class='form-control' required>\n            </div>\n            <button type='submit' class='btn btn-primary'>Save</button>\n        </form>\n    </div>\n</div>\n@endsection");
    
    // Edit
    file_put_contents($resourceDir . '/edit.blade.php', "@extends('layouts.app')\n@section('title', 'Edit {$title} - Demo ERP')\n@section('header_title', 'Edit {$title}')\n@section('content')\n<div class='card'>\n    <div class='card-body'>\n        <form action='{{ route(\"{$dir}.update\", \$item->id) }}' method='POST'>\n            @csrf\n            @method('PUT')\n            <div class='mb-3'>\n                <label class='form-label'>Name</label>\n                <input type='text' name='name' value='{{ \$item->name ?? \"\" }}' class='form-control' required>\n            </div>\n            <button type='submit' class='btn btn-primary'>Update</button>\n        </form>\n    </div>\n</div>\n@endsection");
}

echo "Views scaffolded successfully.\n";
