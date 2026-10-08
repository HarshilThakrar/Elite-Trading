@extends('layouts.app')

@section('title', 'Create Quotation - Demo ERP')
@section('header_title', 'Create Quotation')

@section('content')
    <div class="card card-custom">
        <div class="card-header bg-white p-3">
            <h5 class="mb-0 fw-bold text-primary-custom">New Quotation</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('quotations.store') }}" method="POST">
                @csrf
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Customer</label>
                        <div class="d-flex gap-2">
                            <div class="flex-grow-1">
                                <select name="customer_id" class="form-select select2" required>
                                    <option value="">Select Customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->company_name ?? $customer->customer_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <a href="{{ route('customers.create') }}" target="_blank" class="btn btn-outline-primary" type="button" title="Add New Customer">
                                <i class="bi bi-plus-lg"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Quotation Date</label>
                        <input type="date" name="quotation_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Valid Until</label>
                        <input type="date" name="valid_until" class="form-control"
                            value="{{ date('Y-m-d', strtotime('+15 days')) }}" required>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered" id="quotation-items-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%">Product</th>
                                <th style="width: 12%">HSN</th>
                                <th style="width: 12%">Qty</th>
                                <th style="width: 12%">LP</th>
                                @php
                                    $canViewProfit = auth()->check() && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can('View Profit'));
                                    $canViewPurDisc = auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Admin']);
                                @endphp
                                <th style="width: 10%" class="{{ !$canViewPurDisc ? 'd-none' : '' }}">Pur. Disc %</th>
                                <th style="width: 10%">Cust. Disc %</th>
                                <th style="width: 15%">Line Total</th>
                                @if($canViewProfit)
                                    <th style="width: 15%">Profit</th>
                                @endif
                                <th style="width: 8%">Stock</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select name="items[0][product_id]" class="form-select select2" required>
                                        <option value="">Select Product</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" data-lp="{{ $product->lp_price }}" data-stock="{{ $product->available_stock }}" data-hsn="{{ $product->hsn_code }}" data-gst="{{ $product->gst_rate }}">{{ $product->item_name }} ({{ $product->part_code }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="items[0][hsn_code]" class="form-control hsn_code" readonly placeholder="--">
                                </td>
                                <td><input type="number" name="items[0][quantity]"
                                        class="form-control calc-trigger quantity" min="1" required></td>
                                <td><input type="number" name="items[0][list_price]"
                                        class="form-control calc-trigger list_price" min="0" step="0.01" required></td>
                                <td class="{{ !$canViewPurDisc ? 'd-none' : '' }}"><input type="number"
                                        name="items[0][purchase_discount]"
                                        class="form-control calc-trigger purchase_discount" min="0" max="100" step="0.01"
                                        required></td>
                                <td><input type="number" name="items[0][customer_discount]"
                                        class="form-control calc-trigger customer_discount" min="0" max="100" step="0.01"
                                        required>
                                    <input type="hidden" name="items[0][gst_rate]" class="gst_rate_input" value="0">
                                </td>
                                <td>
                                    <span class="line_total_display">0.00</span>
                                    <input type="hidden" name="items[0][line_total]" class="line_total_input" value="0">
                                    <div class="text-muted small mt-1 fw-bold">Net Rate: ₹<span
                                            class="customer_rate_display">0.00</span></div>
                                    <input type="hidden" name="items[0][gst_amount]" class="gst_amount_input" value="0">
                                    <input type="hidden" name="items[0][line_total_with_gst]" class="line_total_with_gst_input" value="0">
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
                                            <button type="button" class="btn btn-sm btn-info view-history"
                                                title="View Purchase History"><i class="bi bi-clock-history"></i></button>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-danger remove-row"><i
                                                class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
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
                                    <span id="subtotal_display">0.00</span>
                                    <input type="hidden" name="subtotal" id="subtotal_input" value="0">
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
                                <td><input type="number" name="freight_charges" id="freight_charges"
                                        class="form-control text-end calc-trigger" value="0" min="0" step="0.01"></td>
                            </tr>
                            <tr>
                                <th>Grand Total (Ex-GST)</th>
                                <td class="text-end fw-bold fs-5">
                                    <span id="grand_total_display">0.00</span>
                                    <input type="hidden" name="grand_total" id="grand_total_input" value="0">
                                </td>
                            </tr>
                            <tr class="table-warning">
                                <th>GST Amount</th>
                                <td class="text-end fw-bold text-warning">
                                    ₹<span id="gst_amount_display">0.00</span>
                                    <input type="hidden" name="gst_amount" id="gst_amount_input" value="0">
                                </td>
                            </tr>
                            <tr class="table-success">
                                <th>Grand Total (With GST)</th>
                                <td class="text-end fw-bold fs-5 text-success">
                                    ₹<span id="grand_total_with_gst_display">0.00</span>
                                    <input type="hidden" name="grand_total_with_gst" id="grand_total_with_gst_input" value="0">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Terms & Conditions</label>
                        <textarea name="terms_and_conditions" class="form-control" rows="3">1. GST EXTRA.</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('quotations.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-custom">Save Quotation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Purchase History Modal -->
    <div class="modal fade" id="purchaseHistoryModal" tabindex="-1" aria-labelledby="purchaseHistoryModalLabel"
        aria-hidden="true">
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
        document.addEventListener('DOMContentLoaded', function () {
            let rowIndex = 1;
            document.getElementById('add-item-row').addEventListener('click', function () {
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
                                <td><input type="text" name="items[${rowIndex}][hsn_code]" class="form-control hsn_code" readonly placeholder="--"></td>
                                <td><input type="number" name="items[${rowIndex}][quantity]" class="form-control calc-trigger quantity" min="1" required></td>
                                <td><input type="number" name="items[${rowIndex}][list_price]" class="form-control calc-trigger list_price" min="0" step="0.01" required></td>
                                <td class="{{ !$canViewPurDisc ? 'd-none' : '' }}"><input type="number" name="items[${rowIndex}][purchase_discount]" class="form-control calc-trigger purchase_discount" min="0" max="100" step="0.01" required></td>
                                <td>
                                    <input type="number" name="items[${rowIndex}][customer_discount]" class="form-control calc-trigger customer_discount" min="0" max="100" step="0.01" required>
                                    <input type="hidden" name="items[${rowIndex}][gst_rate]" class="gst_rate_input" value="0">
                                </td>
                                <td>
                                    <span class="line_total_display">0.00</span>
                                    <input type="hidden" name="items[${rowIndex}][line_total]" class="line_total_input" value="0">
                                    <div class="text-muted small mt-1 fw-bold">Net Rate: ₹<span class="customer_rate_display">0.00</span></div>
                                    <input type="hidden" name="items[${rowIndex}][gst_amount]" class="gst_amount_input" value="0">
                                    <input type="hidden" name="items[${rowIndex}][line_total_with_gst]" class="line_total_with_gst_input" value="0">
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



            // Escape key to redirect back to quotations list
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    window.location.href = "{{ route('quotations.index') }}";
                }
            });
        });
    </script>
@endpush