<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\CustomerRepositoryInterface::class,
            \App\Repositories\CustomerRepository::class
        );
        $this->app->bind(
            \App\Repositories\VendorRepositoryInterface::class,
            \App\Repositories\VendorRepository::class
        );
        $this->app->bind(
            \App\Repositories\BrandRepositoryInterface::class,
            \App\Repositories\BrandRepository::class
        );
        $this->app->bind(
            \App\Repositories\CategoryRepositoryInterface::class,
            \App\Repositories\CategoryRepository::class
        );
        $this->app->bind(
            \App\Repositories\ProductRepositoryInterface::class,
            \App\Repositories\ProductRepository::class
        );
        $this->app->bind(
            \App\Repositories\StockRepositoryInterface::class,
            \App\Repositories\StockRepository::class
        );
        $this->app->bind(
            \App\Repositories\StockLedgerRepositoryInterface::class,
            \App\Repositories\StockLedgerRepository::class
        );
        $this->app->bind(
            \App\Repositories\PurchaseRepositoryInterface::class,
            \App\Repositories\PurchaseRepository::class
        );
        $this->app->bind(
            \App\Repositories\PurchaseItemRepositoryInterface::class,
            \App\Repositories\PurchaseItemRepository::class
        );
        $this->app->bind(
            \App\Repositories\SaleRepositoryInterface::class,
            \App\Repositories\SaleRepository::class
        );
        $this->app->bind(
            \App\Repositories\SaleItemRepositoryInterface::class,
            \App\Repositories\SaleItemRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            $lowStockCount = \App\Models\Product::select('id', 'reorder_level', 'available_stock')
                ->get()
                ->filter(function ($product) {
                    return $product->available_stock <= $product->reorder_level;
                })->count();
            
            $pendingSalesCount = \App\Models\Sale::where('status', 'Approved')->count();
            
            $totalNotifications = $lowStockCount + $pendingSalesCount;
            
            $view->with([
                'notificationCount' => $totalNotifications,
                'lowStockCount' => $lowStockCount,
                'pendingSalesCount' => $pendingSalesCount
            ]);
        });
    }
}

if (!function_exists('activity')) {
    function activity()
    {
        return new class {
            public function log($description)
            {
                try {
                    \Illuminate\Support\Facades\Log::info("Activity Log: " . $description);
                    if (\Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
                        \App\Models\AuditLog::create([
                            'user_id' => auth()->id() ?? 1,
                            'action' => 'activity',
                            'model_type' => 'System',
                            'model_id' => 0,
                            'new_values' => ['message' => $description],
                        ]);
                    }
                } catch (\Throwable $e) {
                    // Ignore activity logging errors
                }
            }
        };
    }
}
