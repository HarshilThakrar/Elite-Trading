<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            ['name' => 'Current Assets', 'nature' => 'Assets', 'is_system' => true],
            ['name' => 'Current Liabilities', 'nature' => 'Liabilities', 'is_system' => true],
            ['name' => 'Direct Income', 'nature' => 'Income', 'is_system' => true],
            ['name' => 'Direct Expenses', 'nature' => 'Expenses', 'is_system' => true],
            ['name' => 'Indirect Income', 'nature' => 'Income', 'is_system' => true],
            ['name' => 'Indirect Expenses', 'nature' => 'Expenses', 'is_system' => true],
        ];

        foreach ($groups as $group) {
            \App\Models\AccountGroup::updateOrCreate(['name' => $group['name']], $group);
        }

        $subGroups = [
            ['name' => 'Sundry Debtors', 'parent' => 'Current Assets', 'nature' => 'Assets', 'is_system' => true],
            ['name' => 'Cash-in-Hand', 'parent' => 'Current Assets', 'nature' => 'Assets', 'is_system' => true],
            ['name' => 'Bank Accounts', 'parent' => 'Current Assets', 'nature' => 'Assets', 'is_system' => true],
            ['name' => 'Sundry Creditors', 'parent' => 'Current Liabilities', 'nature' => 'Liabilities', 'is_system' => true],
            ['name' => 'Duties & Taxes', 'parent' => 'Current Liabilities', 'nature' => 'Liabilities', 'is_system' => true],
            ['name' => 'Sales Accounts', 'parent' => 'Direct Income', 'nature' => 'Income', 'is_system' => true],
            ['name' => 'Purchase Accounts', 'parent' => 'Direct Expenses', 'nature' => 'Expenses', 'is_system' => true],
        ];

        foreach ($subGroups as $sg) {
            $parent = \App\Models\AccountGroup::where('name', $sg['parent'])->first();
            \App\Models\AccountGroup::updateOrCreate(
                ['name' => $sg['name']],
                ['parent_id' => $parent->id, 'nature' => $sg['nature'], 'is_system' => $sg['is_system']]
            );
        }

        // Create Default Ledgers
        $cashGroup = \App\Models\AccountGroup::where('name', 'Cash-in-Hand')->first();
        \App\Models\Ledger::updateOrCreate(
            ['name' => 'Cash'],
            ['account_group_id' => $cashGroup->id, 'is_system' => true]
        );

        $salesGroup = \App\Models\AccountGroup::where('name', 'Sales Accounts')->first();
        \App\Models\Ledger::updateOrCreate(
            ['name' => 'Sales A/c'],
            ['account_group_id' => $salesGroup->id, 'is_system' => true]
        );

        $purchaseGroup = \App\Models\AccountGroup::where('name', 'Purchase Accounts')->first();
        \App\Models\Ledger::updateOrCreate(
            ['name' => 'Purchase A/c'],
            ['account_group_id' => $purchaseGroup->id, 'is_system' => true]
        );
    }
}
