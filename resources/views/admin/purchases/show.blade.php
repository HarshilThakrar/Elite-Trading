@extends('layouts.app')

@section('title', 'Purchase Order ' . $purchase->po_number)
@section('header_title', 'Purchase Order Details')

@section('content')
<div class="row">
    <div class="col-md-9">
        <div class="card card-custom mb-4" id="printArea">
            <div class="card-body p-5">
                <div class="d-flex justify-content-between mb-5 border-bottom pb-4">
                    <div>
                        <h2 class="fw-bold text-primary-custom mb-0">PURCHASE ORDER</h2>
                        <h5 class="text-muted">#{{ $purchase->po_number }}</h5>
                    </div>
                    <div class="text-end">
                        <h4 class="fw-bold">Demo ERP</h4>
                        <p class="text-muted mb-0">123 Business Park, Metro City</p>
                        <p class="text-muted">Email: admin@demoerp.com | Phone: +91 70690 52046</p>
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-sm-6">
                        <h6 class="text-muted text-uppercase fw-bold mb-3">Vendor Details:</h6>
                        <h5 class="fw-bold mb-1">{{ $purchase->vendor->company_name }}</h5>
                        <p class="mb-0">{{ $purchase->vendor->contact_person }}</p>
                        <p class="mb-0">Email: {{ $purchase->vendor->email }}</p>
                        <p class="mb-0">Phone: {{ $purchase->vendor->mobile }}</p>
                        <p class="mb-0">GST No: <span class="fw-bold">{{ $purchase->vendor->gst_no ?? 'N/A' }}</span></p>
                    </div>
                    <div class="col-sm-6 text-end">
                        <h6 class="text-muted text-uppercase fw-bold mb-3">Order Details:</h6>
                        <table class="table table-sm table-borderless ms-auto" style="width: auto;">
                            <tr>
                                <td class="text-muted text-end pe-3">PO Date:</td>
                                <td class="fw-bold text-start">{{ \Carbon\Carbon::parse($purchase->po_date)->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted text-end pe-3">Status:</td>
                                <td class="text-start">
                                    @if($purchase->status == 'Draft')
                                        <span class="fw-bold text-secondary">Draft</span>
                                    @elseif($purchase->status == 'Approved')
                                        <span class="fw-bold text-primary">Approved</span>
                                    @elseif($purchase->status == 'Received')
                                        <span class="fw-bold text-success">Received</span>
                                    @else
                                        <span class="fw-bold text-danger">Cancelled</span>
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
                            @foreach($purchase->items as $index => $item)
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
                                <td class="text-end fw-bold fs-5 text-primary-custom">₹ {{ number_format($purchase->total_amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($purchase->notes)
                <div class="mt-4">
                    <p class="text-muted fw-bold mb-1">Notes / Instructions:</p>
                    <p class="border p-3 bg-light rounded">{{ $purchase->notes }}</p>
                </div>
                @endif

                @php
                    $grns = \App\Models\GoodsReceiptNote::where('purchase_id', $purchase->id)->get();
                @endphp
                
                @if($grns->count() > 0)
                <div class="mt-5">
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Goods Receipt Notes (GRN)</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>GRN Number</th>
                                    <th>Received Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grns as $grn)
                                <tr>
                                    <td class="fw-bold">{{ $grn->grn_number }}</td>
                                    <td>{{ \Carbon\Carbon::parse($grn->received_date)->format('d M Y') }}</td>
                                    <td><span class="badge bg-success">{{ $grn->status }}</span></td>
                                    <td>
                                        <a href="{{ route('grn.show', $grn->id) }}" class="btn btn-sm btn-info text-white"><i class="bi bi-eye"></i> View GRN</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-3 print-hide">
        <div class="card card-custom sticky-top" style="top: 20px;">
            <div class="card-header bg-white p-3 border-bottom">
                <h6 class="mb-0 fw-bold">Actions</h6>
            </div>
            <div class="card-body">
                <button class="btn btn-light w-100 mb-3 text-start" onclick="window.print()"><i class="bi bi-printer me-2"></i> Print PO</button>
                <a href="{{ route('purchases.pdf', $purchase->id) }}" class="btn btn-secondary w-100 mb-3 text-start"><i class="bi bi-file-earmark-pdf me-2"></i> Download PDF</a>
                
                <form action="{{ route('purchases.reorder', $purchase->id) }}" method="POST" class="mb-3">
                    @csrf
                    <button type="submit" class="btn btn-warning w-100 text-start"><i class="bi bi-files me-2"></i> Reorder (Clone PO)</button>
                </form>
                
                @if($purchase->status == 'Draft')
                    <form action="{{ route('purchases.updateStatus', $purchase->id) }}" method="POST" class="mb-3">
                        @csrf
                        <input type="hidden" name="status" value="Approved">
                        <button type="submit" class="btn btn-primary w-100 text-start"><i class="bi bi-check-circle me-2"></i> Approve PO</button>
                    </form>
                @endif

                @if($purchase->status == 'Approved')
                    <a href="{{ route('purchases.grn.create', $purchase->id) }}" class="btn btn-success w-100 mb-3 text-start"><i class="bi bi-box-arrow-in-down me-2"></i> Create GRN</a>
                @endif

                @if(in_array($purchase->status, ['Draft', 'Approved']))
                    <form action="{{ route('purchases.updateStatus', $purchase->id) }}" method="POST" class="mb-3" onsubmit="return confirm('Are you sure you want to cancel this PO?');">
                        @csrf
                        <input type="hidden" name="status" value="Cancelled">
                        <button type="submit" class="btn btn-danger w-100 text-start"><i class="bi bi-x-circle me-2"></i> Cancel PO</button>
                    </form>
                @endif
                
                @if($purchase->status == 'Received')
                    <div class="alert alert-success text-center">
                        <i class="bi bi-check-circle-fill fs-3 d-block mb-2"></i>
                        Inventory updated successfully.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printArea, #printArea * {
        visibility: visible;
    }
    #printArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .col-md-3 {
        display: none;
    }
}
</style>
@endsection
