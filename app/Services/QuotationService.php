<?php

namespace App\Services;

use App\Models\Quotation;

class QuotationService
{
    public function generateQuotationNumber()
    {
        $prefix = 'QT-' . date('Ymd') . '-';
        $lastQuotation = Quotation::where('quotation_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastQuotation) {
            return $prefix . '0001';
        }

        $lastNumber = intval(substr($lastQuotation->quotation_number, -4));
        return $prefix . sprintf('%04d', $lastNumber + 1);
    }

    public function calculateItemRates($list_price, $purchase_discount, $customer_discount, $quantity)
    {
        $list_price = floatval($list_price);
        $purchase_discount = floatval($purchase_discount);
        $customer_discount = floatval($customer_discount);
        $quantity = floatval($quantity);

        $purchase_rate = $list_price * (100 - $purchase_discount) / 100;
        $customer_rate = $list_price * (100 - $customer_discount) / 100;

        $profit_amount = $customer_rate - $purchase_rate;
        
        $profit_percentage = 0;
        if ($purchase_rate > 0) {
            $profit_percentage = ($profit_amount / $purchase_rate) * 100;
        }

        $line_total = $customer_rate * $quantity;

        return [
            'purchase_rate' => round($purchase_rate, 2),
            'customer_rate' => round($customer_rate, 2),
            'profit_amount' => round($profit_amount, 2),
            'profit_percentage' => round($profit_percentage, 2),
            'line_total' => round($line_total, 2),
            'unit_price' => round($customer_rate, 2), // Maps to existing field
            'total_price' => round($line_total, 2) // Maps to existing field
        ];
    }
}
