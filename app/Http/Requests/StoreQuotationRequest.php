<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quotation_date',
            'subtotal' => 'required|numeric|min:0',
            'freight_charges' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'gst_amount' => 'nullable|numeric|min:0',
            'grand_total_with_gst' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.list_price' => 'required|numeric|min:0',
            'items.*.purchase_discount' => 'required|numeric|min:0|max:100',
            'items.*.customer_discount' => 'required|numeric|min:0|max:100',
            'items.*.hsn_code' => 'nullable|string|max:20',
            'items.*.gst_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.gst_amount' => 'nullable|numeric|min:0',
            'items.*.line_total_with_gst' => 'nullable|numeric|min:0',
        ];
    }
}
