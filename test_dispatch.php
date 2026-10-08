<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$requestData = [
    'dispatch_date' => '2026-08-22',
    'items' => [
        0 => [
            'dispatched_qty' => '20',
            'batch_no' => 'BATCH-001'
        ]
    ],
    'notes' => 'Test'
];

$request = Illuminate\Http\Request::create('/sales/1/dispatch', 'POST', $requestData);
$controller = app(\App\Http\Controllers\Admin\DispatchController::class);

try {
    $response = $controller->store($request, 1);
    if ($response instanceof \Illuminate\Http\RedirectResponse) {
        echo "Redirect: " . $response->getTargetUrl() . "\n";
        echo "Session Flash: " . json_encode(session()->all()) . "\n";
    } else {
        echo "Response: " . get_class($response) . "\n";
    }
} catch (\Exception $e) {
    echo "Uncaught Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
