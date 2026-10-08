<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/admin/customers', 'POST', [
    'company_name' => 'Test Company',
    'mobile' => '1234567890',
    'status' => 'on'
]);

try {
    $controller = app(App\Http\Controllers\Admin\CustomerController::class);
    $response = $controller->store($request);
    echo 'SUCCESS: ' . get_class($response);
} catch (\Illuminate\Validation\ValidationException $e) {
    echo 'VALIDATION FAILED: ' . json_encode($e->errors());
} catch (\Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
}
