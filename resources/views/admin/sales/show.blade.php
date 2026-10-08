@extends('layouts.app')

@section('title', 'Sales Order ' . $sale->invoice_number)
@section('header_title', 'Sales Invoice Details')

@section('content')
<div class="row">
    <div class="col-md-9">
        <div class="card card-custom mb-4" id="printArea">
            <div class="card-body p-5">
                <!-- Manual Print Header (Right Aligned) -->
                <div class="d-none d-print-block text-end text-muted small mb-4">
                    Sales Order {{ $sale->invoice_number }}
                </div>
                
                <div class="d-flex justify-content-between mb-5 border-bottom pb-4">
                    <div>
                        <h2 class="fw-bold text-primary-custom mb-0">TAX INVOICE</h2>
                        <h5 class="text-muted">#{{ $sale->invoice_number }}</h5>
                    </div>
                    <div class="text-end">
                        <h4 class="fw-bold">Demo ERP Com.</h4>
                        <p class="text-muted mb-0">123 Business Park, Metro City</p>
                        <p class="text-muted">Email: admin@demoerp.com | Phone: +91 9876543210</p>
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-sm-6">
                        <h6 class="text-muted text-uppercase fw-bold mb-3">Billed To:</h6>
                        <h5 class="fw-bold mb-1">{{ $sale->customer->company_name }}</h5>
                        <p class="mb-0">{{ $sale->customer->contact_person }}</p>
                        <p class="mb-0">Email: {{ $sale->customer->email }}</p>
                        <p class="mb-0">Phone: {{ $sale->customer->mobile }}</p>
                        <p class="mb-0">GST No: <span class="fw-bold">{{ $sale->customer->gst_no ?? 'N/A' }}</span></p>
                    </div>
                    <div class="col-sm-6 text-end">
                        <h6 class="text-muted text-uppercase fw-bold mb-3">Order Details:</h6>
                        <table class="table table-sm table-borderless ms-auto" style="width: auto;">
                            <tr>
                                <td class="text-muted text-end pe-3">Date:</td>
                                <td class="fw-bold text-start">{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted text-end pe-3 align-middle">Status:</td>
                                <td class="text-start">
                                    @if($sale->status == 'Draft')
                                        <span class="badge bg-secondary text-white fs-6">Draft</span>
                                    @elseif($sale->status == 'Approved')
                                        <span class="badge bg-primary text-white fs-6">Approved</span>
                                    @elseif($sale->status == 'Dispatched')
                                        <span class="badge bg-success text-white fs-6">Dispatched</span>
                                    @else
                                        <span class="badge bg-danger text-white fs-6">Cancelled</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Part Code</th>
                                <th>Description</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Price (₹)</th>
                                <th class="text-end">Total (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->product->part_code }}</td>
                                <td>{{ $item->product->item_name }}</td>
                                <td class="text-center">{{ $item->quantity }} {{ $item->product->unit }}</td>
                                <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end fw-semibold">{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end fw-bold">Grand Total</td>
                                <td class="text-end fw-bold fs-5 text-primary-custom">₹ {{ number_format($sale->total_amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($sale->notes)
                <div class="mt-4">
                    <p class="text-muted fw-bold mb-1">Notes / Instructions:</p>
                    <p class="border p-3 bg-light rounded">{{ $sale->notes }}</p>
                </div>
                @endif

                @php
                    $dispatches = \App\Models\SalesDispatch::where('sale_id', $sale->id)->latest()->get();
                @endphp

                <div class="mt-5">
                    <h6 class="fw-bold mb-3 text-success border-bottom pb-2"><i class="bi bi-truck me-2"></i> Delivery Notes (Dispatches) / Invoices</h6>
                    @if($dispatches->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>DN Number</th>
                                    <th>Dispatch Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dispatches as $dispatch)
                                <tr>
                                    <td class="fw-bold">{{ $dispatch->dispatch_number }}</td>
                                    <td>{{ \Carbon\Carbon::parse($dispatch->dispatch_date)->format('d M Y') }}</td>
                                    <td><span class="badge bg-success text-white">{{ $dispatch->status }}</span></td>
                                    <td>
                                        <a href="{{ route('dispatch.show', $dispatch->id) }}" class="btn btn-sm btn-info text-white"><i class="bi bi-eye"></i> View DN</a>
                                        <a href="{{ route('dispatch.printSlip', $dispatch->id) }}" target="_blank" class="btn btn-sm btn-primary text-white ms-1"><i class="bi bi-printer"></i> Print Slip</a>
                                        @php
                                            $invNumber = str_replace('DN-', 'INV-', $dispatch->dispatch_number);
                                            $dispatchInvoice = \App\Models\Invoice::where('invoice_number', $invNumber)->first();
                                        @endphp
                                        @if($dispatchInvoice)
                                            <a href="{{ route('invoices.show', $dispatchInvoice->id) }}" target="_blank" class="btn btn-sm btn-primary text-white ms-1"><i class="bi bi-eye"></i> View Invoice</a>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="alert alert-secondary text-center py-4">
                        <i class="bi bi-inbox fs-3 d-block mb-2 text-muted"></i>
                        <p class="mb-0 text-muted">No dispatch notes or invoices have been generated for this order yet.</p>
                        <p class="small text-muted">Click the green <strong>Create Dispatch Note</strong> button on the right to start dispatching items.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-custom sticky-top" style="top: 20px;">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="mb-0 fw-bold">Actions</h6>
            </div>
            <div class="card-body">

                
                <button class="btn btn-light w-100 mb-3 text-start" onclick="window.print()"><i class="bi bi-printer me-2"></i> Print Invoice</button>
                <a href="{{ route('sales.pdf', $sale->id) }}" class="btn btn-danger w-100 mb-3 text-start"><i class="bi bi-file-pdf me-2"></i> Download PDF</a>
                <button type="button" id="downloadImageBtn" class="btn btn-info w-100 mb-3 text-start text-white"><i class="bi bi-image me-2"></i> Download Image</button>
                
                @if($sale->status == 'Draft')
                    <form action="{{ route('sales.updateStatus', $sale->id) }}" method="POST" class="mb-3">
                        @csrf
                        <input type="hidden" name="status" value="Approved">
                        <button type="submit" class="btn btn-primary w-100 text-start"><i class="bi bi-check-circle me-2"></i> Approve Sale</button>
                    </form>
                @endif

                @if($sale->status == 'Approved')
                    <a href="{{ route('sales.dispatch.create', $sale->id) }}" class="btn btn-success w-100 mb-3 text-start"><i class="bi bi-truck me-2"></i> Create Dispatch Note</a>
                @endif

                @if(in_array($sale->status, ['Draft', 'Approved']))
                    <form action="{{ route('sales.updateStatus', $sale->id) }}" method="POST" class="mb-3" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                        @csrf
                        <input type="hidden" name="status" value="Cancelled">
                        <button type="submit" class="btn btn-danger w-100 text-start"><i class="bi bi-x-circle me-2"></i> Cancel Order</button>
                    </form>
                @endif
                
                @if($sale->status == 'Dispatched')
                    <div class="alert alert-success text-center">
                        <i class="bi bi-check-circle-fill fs-3 d-block mb-2"></i>
                        Goods dispatched and inventory updated successfully.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    @page {
        margin: 0; /* Hides browser headers and footers */
    }
    body {
        margin: 1cm; /* Adds proper margin back to the print content */
    }
    body * {
        visibility: hidden;
    }
    #printArea, #printArea * {
        visibility: visible;
        color: #000 !important; /* Force black text for printing */
    }
    #printArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none !important;
        border: none !important;
    }
    .col-md-3 {
        display: none;
    }
    /* Fix table-dark header issue where bg disappears but text stays white */
    .table-dark th, .table-dark td {
        color: #000 !important;
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    /* Remove borders if desired, or keep them dark */
    .table-bordered th, .table-bordered td {
        border-color: #dee2e6 !important;
    }
}
</style>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
    document.getElementById('downloadImageBtn').addEventListener('click', function() {
        var printArea = document.getElementById('printArea');
        var btn = this;
        var originalText = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Generating...';
        btn.disabled = true;

        html2canvas(printArea, {
            scale: 2, // High resolution
            useCORS: true,
            backgroundColor: '#ffffff'
        }).then(function(canvas) {
            var imgData = canvas.toDataURL('image/png');
            var link = document.createElement('a');
            link.download = 'Invoice-{{ $sale->invoice_number }}.png';
            link.href = imgData;
            link.click();

            btn.innerHTML = originalText;
            btn.disabled = false;
        }).catch(function(error) {
            console.error("Error generating image:", error);
            alert("Could not generate image. Please try again.");
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
</script>
@endpush
@endsection
