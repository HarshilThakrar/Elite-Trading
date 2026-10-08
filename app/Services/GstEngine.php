<?php
namespace App\Services;

class GstEngine
{
    /**
     * Calculate GST for an amount
     */
    public function calculate(float $amount, float $rate, bool $isInterState = false)
    {
        if ($isInterState) {
            return [
                'cgst' => 0,
                'sgst' => 0,
                'igst' => round(($amount * $rate) / 100, 2),
                'total_tax' => round(($amount * $rate) / 100, 2),
            ];
        } else {
            $halfRate = $rate / 2;
            $cgst = round(($amount * $halfRate) / 100, 2);
            $sgst = round(($amount * $halfRate) / 100, 2);
            return [
                'cgst' => $cgst,
                'sgst' => $sgst,
                'igst' => 0,
                'total_tax' => $cgst + $sgst,
            ];
        }
    }
}
