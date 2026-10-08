<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InventoryAnalyticsService;
use App\Models\User;
use App\Notifications\InventoryAlertNotification;

class SendInventoryAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-inventory-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for dead stock and low stock and sends notifications to admin.';

    /**
     * Execute the console command.
     */
    public function handle(InventoryAnalyticsService $inventoryAnalytics)
    {
        $this->info('Checking inventory for alerts...');

        $admins = User::role('Admin')->get();

        if ($admins->isEmpty()) {
            $this->error('No admin users found to send alerts to.');
            return;
        }

        // 1. Low Stock Alerts
        $lowStockItems = $inventoryAnalytics->getSmartReorderSuggestions();
        if ($lowStockItems->isNotEmpty()) {
            foreach ($admins as $admin) {
                $admin->notify(new InventoryAlertNotification(
                    'low_stock',
                    'You have ' . $lowStockItems->count() . ' items that reached their reorder level.',
                    $lowStockItems
                ));
            }
            $this->info('Low stock alerts sent.');
        }

        // 2. Dead Stock Alerts
        $deadStockItems = $inventoryAnalytics->getDeadStock();
        if ($deadStockItems->isNotEmpty()) {
            foreach ($admins as $admin) {
                $admin->notify(new InventoryAlertNotification(
                    'dead_stock',
                    'You have ' . $deadStockItems->count() . ' items marked as dead stock.',
                    $deadStockItems
                ));
            }
            $this->info('Dead stock alerts sent.');
        }

        $this->info('Inventory alerts check completed.');
    }
}
