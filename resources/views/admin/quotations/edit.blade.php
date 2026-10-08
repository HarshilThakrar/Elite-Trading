@extends('layouts.app')

@section('title', 'Edit Quotation - Demo ERP')
@section('header_title', 'Edit Quotation')

@section('content')
<div class="card card-custom">
    <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold text-primary-custom">Edit Quotation: {{ $quotation->quotation_number }}</h5>
        <div class="d-flex align-items-center gap-2">
            @if($quotation->status === 'Converted')
                <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill d-inline-flex align-items-center" style="font-size: 0.85rem;">
                    <i class="bi bi-check-circle-fill me-1"></i> Converted to Sales Order
                </span>
            @else
                <form action="{{ route('quotations.convertToSale', $quotation->id) }}" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('Are you sure you want to convert Quotation {{ $quotation->quotation_number }} to a Sales Order?');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success text-white shadow-sm d-inline-flex align-items-center gap-1" title="Convert to Sales Order">
                        <i class="bi bi-cart-plus"></i> Convert to Sales Order
                    </button>
                </form>
            @endif
            <a href="{{ route('quotations.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('quotations.update', $quotation->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Customer</label>
                    <select name="customer_id" class="form-select select2" required>
                        <option value="">Select Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ $quotation->customer_id == $customer->id ? 'selected' : '' }}>
                                {{ $customer->company_name ?? $customer->customer_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Quotation Date</label>
                    <input type="date" name="quotation_date" class="form-control" value="{{ \Carbon\Carbon::parse($quotation->quotation_date)->format('Y-m-d') }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Valid Until</label>
                    <input type="date" name="valid_until" class="form-control" value="{{ \Carbon\Carbon::parse($quotation->valid_until)->format('Y-m-d') }}" required>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered" id="quotation-items-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%">Product</th>
                            <th style="width: 8%">HSN</th>
                            <th style="width: 8%">Qty</th>
                            <th style="width: 12%">LP</th>
@php
    $canViewProfit = auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('View Profit'));
    $canViewPurDisc = auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Admin']);
@endphp
                            <th style="width: 10%" class="{{ !$canViewPurDisc ? 'd-none' : '' }}">Pur. Disc %</th>
                            <th style="width: 10%">Cust. Disc %</th>
                            <th style="width: 8%">CGST/SGST%</th>
                            <th style="width: 15%">Line Total</th>
                            @if($canViewProfit)
                            <th style="width: 15%">Profit</th>
                            @endif
                            <th style="width: 8%">Stock</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quotation->items as $index => $item)
                        <tr>
                            <td>
                                <select name="items[{{ $index }}][product_id]" class="form-select select2" required>
                                    <option value="">Select Product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-lp="{{ $product->lp_price }}" data-stock="{{ $product->available_stock }}" data-hsn="{{ $product->hsn_code }}" data-gst="{{ $product->gst_rate }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>{{ $product->item_name }} ({{ $product->part_code }})</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="items[{{ $index }}][hsn_code]" class="form-control hsn_code" readonly value="{{ $item->product->hsn_code ?? '' }}" placeholder="--">
                            </td>
                            <td><input type="number" name="items[{{ $index }}][quantity]" class="form-control calc-trigger quantity" value="{{ $item->quantity }}" min="1" required></td>
                            <td><input type="number" name="items[{{ $index }}][list_price]" class="form-control calc-trigger list_price" value="{{ $item->list_price }}" min="0" step="0.01" required></td>
                            <td class="{{ !$canViewPurDisc ? 'd-none' : '' }}"><input type="number" name="items[{{ $index }}][purchase_discount]" class="form-control calc-trigger purchase_discount" value="{{ $item->purchase_discount }}" min="0" max="100" step="0.01" required></td>
                            <td><input type="number" name="items[{{ $index }}][customer_discount]" class="form-control calc-trigger customer_discount" value="{{ $item->customer_discount }}" min="0" max="100" step="0.01" required></td>
                            <td class="text-center">
                                <div class="text-info fw-bold" style="font-size: 0.85rem">
                                    <span class="cgst_rate_display">{{ ($item->gst_rate ?? 0) / 2 }}%</span> CGST<br>
                                    <span class="sgst_rate_display">{{ ($item->gst_rate ?? 0) / 2 }}%</span> SGST
                                </div>
                                <input type="hidden" name="items[{{ $index }}][gst_rate]" class="gst_rate_input" value="{{ $item->gst_rate ?? 0 }}">
                            </td>
                            <td>
                                <span class="line_total_display">{{ number_format($item->line_total, 2) }}</span>
                                <input type="hidden" name="items[{{ $index }}][line_total]" class="line_total_input" value="{{ $item->line_total }}">
                                <div class="text-muted small mt-1 fw-bold">Net Rate: ₹<span class="customer_rate_display">{{ number_format($item->list_price * (100 - $item->customer_discount) / 100, 2) }}</span></div>
                                <div class="text-primary small mt-1">+CGST: ₹<span class="cgst_amount_display">{{ number_format(($item->gst_amount ?? 0) / 2, 2) }}</span></div>
                                <div class="text-primary small mt-1">+SGST: ₹<span class="sgst_amount_display">{{ number_format(($item->gst_amount ?? 0) / 2, 2) }}</span></div>
                                <input type="hidden" name="items[{{ $index }}][gst_amount]" class="gst_amount_input" value="{{ $item->gst_amount ?? 0 }}">
                                <input type="hidden" name="items[{{ $index }}][line_total_with_gst]" class="line_total_with_gst_input" value="{{ $item->line_total_with_gst ?? 0 }}">
                            </td>
                            @if($canViewProfit)
                            <td>
                                <div class="text-success small">
                                    <span class="profit_rs_display">{{ number_format($item->profit_amount, 2) }}</span> 
                                    (<span class="profit_pct_display">{{ number_format($item->profit_percentage, 2) }}%</span>)
                                </div>
                            </td>
                            @endif
                            <td class="text-center align-middle">
                                <div class="stock-indicator" data-bs-toggle="tooltip" title="Current Stock">
                                    <span class="badge bg-secondary rounded-pill stock-badge text-white">
                                        {{ $item->product->available_stock ?? '--' }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group">
                                    @if($canViewPurDisc)
                                    <button type="button" class="btn btn-sm btn-info view-history" title="View Purchase History"><i class="bi bi-clock-history"></i></button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-danger remove-row"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn btn-sm btn-secondary" id="add-item-row"><i class="bi bi-plus"></i> Add Item</button>
                    <button type="button" class="btn btn-sm btn-success" id="skip-to-freight"><i class="bi bi-arrow-down-circle"></i> Go to Freight (Finalize)</button>
                </div>
            </div>

            <div class="row justify-content-end mb-4">
                <div class="col-md-4">
                    <table class="table table-bordered">
                        <tr>
                            <th>Subtotal (Ex-GST)</th>
                            <td class="text-end">
                                <span id="subtotal_display">{{ number_format($quotation->subtotal, 2) }}</span>
                                <input type="hidden" name="subtotal" id="subtotal_input" value="{{ $quotation->subtotal }}">
                            </td>
                        </tr>
                        <tr>
                            <th>Total Net Rate</th>
                            <td class="text-end text-muted fw-bold">
                                <span id="total_purchase_display">0.00</span>
                            </td>
                        </tr>
                        @if($canViewProfit)
                        <tr>
                            <th>Average Profit</th>
                            <td class="text-end text-success fw-bold">
                                <span id="avg_profit_pct_display">0%</span>
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th>Freight Charges</th>
                            <td><input type="number" name="freight_charges" id="freight_charges" class="form-control text-end calc-trigger" value="{{ $quotation->freight_charges }}" min="0" step="0.01"></td>
                        </tr>
                        <tr>
                            <th>Grand Total (Ex-GST)</th>
                            <td class="text-end fw-bold fs-5">
                                <span id="grand_total_display">{{ number_format($quotation->grand_total, 2) }}</span>
                                <input type="hidden" name="grand_total" id="grand_total_input" value="{{ $quotation->grand_total }}">
                            </td>
                        </tr>
                        <tr class="table-warning">
                            <th>GST Amount</th>
                            <td class="text-end fw-bold text-warning">
                                ₹<span id="gst_amount_display">{{ number_format($quotation->gst_amount ?? 0, 2) }}</span>
                                <input type="hidden" name="gst_amount" id="gst_amount_input" value="{{ $quotation->gst_amount ?? 0 }}">
                            </td>
                        </tr>
                        <tr class="table-success">
                            <th>Grand Total (With GST)</th>
                            <td class="text-end fw-bold fs-5 text-success">
                                ₹<span id="grand_total_with_gst_display">{{ number_format($quotation->grand_total_with_gst ?? $quotation->grand_total, 2) }}</span>
                                <input type="hidden" name="grand_total_with_gst" id="grand_total_with_gst_input" value="{{ $quotation->grand_total_with_gst ?? $quotation->grand_total }}">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Terms & Conditions</label>
                    <textarea name="terms_and_conditions" class="form-control" rows="3">{{ $quotation->terms_and_conditions }}</textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('quotations.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary-custom">Update Quotation</button>
            </div>
        </form>
    </div>
</div>

<!-- Purchase History Modal -->
<div class="modal fade" id="purchaseHistoryModal" tabindex="-1" aria-labelledby="purchaseHistoryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="purchaseHistoryModalLabel">Recent Purchase History</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm" id="history-table">
                <thead class="table-light">
                    <tr>
                        <th>PO Number</th>
                        <th>PO Date</th>
                        <th>Vendor</th>
                        <th>Quantity</th>
                        <th>Net Rate (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Populated via JS -->
                </tbody>
            </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/quotation.js') }}?v={{ time() }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let rowIndex = {{ count($quotation->items) }};
        document.getElementById('add-item-row').addEventListener('click', function() {
            const tbody = document.querySelector('#quotation-items-table tbody');
            const newRow = `
                <tr>
                    <td>
                        <select name="items[${rowIndex}][product_id]" class="form-select select2" required>
                            <option value="">Select Product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-lp="{{ $product->lp_price }}" data-stock="{{ $product->available_stock }}" data-hsn="{{ $product->hsn_code }}" data-gst="{{ $product->gst_rate }}">{{ $product->item_name }} ({{ $product->part_code }})</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" name="items[${rowIndex}][quantity]" class="form-control calc-trigger quantity" min="1" required></td>
                    <td><input type="number" name="items[${rowIndex}][list_price]" class="form-control calc-trigger list_price" min="0" step="0.01" required></td>
                    <td class="{{ !$canViewPurDisc ? 'd-none' : '' }}"><input type="number" name="items[${rowIndex}][purchase_discount]" class="form-control calc-trigger purchase_discount" min="0" max="100" step="0.01" required></td>
                    <td><input type="number" name="items[${rowIndex}][customer_discount]" class="form-control calc-trigger customer_discount" min="0" max="100" step="0.01" required></td>
                    <td class="text-center">
                        <div class="text-info fw-bold" style="font-size: 0.85rem">
                            <span class="cgst_rate_display">0%</span> CGST<br>
                            <span class="sgst_rate_display">0%</span> SGST
                        </div>
                        <input type="hidden" name="items[${rowIndex}][gst_rate]" class="gst_rate_input" value="0">
                    </td>
                    <td>
                        <span class="line_total_display">0.00</span>
                        <input type="hidden" name="items[${rowIndex}][line_total]" class="line_total_input" value="0">
                        <div class="text-muted small mt-1 fw-bold">Net Rate: ₹<span class="customer_rate_display">0.00</span></div>
                        <div class="text-primary small mt-1">+CGST: ₹<span class="cgst_amount_display">0.00</span></div>
                        <div class="text-primary small mt-1">+SGST: ₹<span class="sgst_amount_display">0.00</span></div>
                    </td>
                    @if($canViewProfit)
                    <td>
                        <div class="text-success small">
                            <span class="profit_rs_display">0.00</span> 
                            (<span class="profit_pct_display">0%</span>)
                        </div>
                    </td>
                    @endif
                    <td class="text-center align-middle">
                        <div class="stock-indicator" data-bs-toggle="tooltip" title="Select a product">
                            <span class="badge bg-secondary rounded-pill stock-badge text-white">--</span>
                        </div>
                    </td>
                    <td>
                        <div class="btn-group">
                            @if($canViewPurDisc)
                            <button type="button" class="btn btn-sm btn-info view-history" title="View Purchase History"><i class="bi bi-clock-history"></i></button>
                            @endif
                            <button type="button" class="btn btn-sm btn-danger remove-row"><i class="bi bi-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', newRow);
            let newSelect = $(tbody.lastElementChild.querySelector('.select2'));
            newSelect.select2({ theme: 'bootstrap-5', width: '100%' });
            newSelect.select2('open');
            rowIndex++;
        });


    });
</script>
@endpush
