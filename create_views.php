<?php
$indexTemplate = <<<EOT
@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <h2>Returns</h2>
    <div class="card">
        <div class="card-body">
            <p>Module active. Returns are listed here.</p>
        </div>
    </div>
</div>
@endsection
EOT;

file_put_contents('resources/views/admin/sales_returns/index.blade.php', $indexTemplate);
file_put_contents('resources/views/admin/sales_returns/create.blade.php', $indexTemplate);
file_put_contents('resources/views/admin/purchase_returns/index.blade.php', $indexTemplate);
file_put_contents('resources/views/admin/purchase_returns/create.blade.php', $indexTemplate);

echo "Views created.";
