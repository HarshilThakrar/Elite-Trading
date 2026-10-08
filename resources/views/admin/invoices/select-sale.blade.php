@extends('layouts.app')

@section('title', 'Select Sales Order to Invoice')
@section('header_title', 'Create Invoice')

@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-6">
        <div class="card card-custom border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h3 class="card-title m-0 fw-semibold text-dark">Create New Invoice</h3>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-4">Please select an Approved Sales Order to generate an invoice for. Invoices are automatically generated when a Dispatch Note is created.</p>
                
                <form id="quickInvoiceForm" class="d-flex flex-column gap-3">
                    <div>
                        <label for="saleSelect" class="form-label fw-medium">Select Approved Sales Order:</label>
                        <select id="saleSelect" class="form-select" size="5">
                            @forelse($approvedSalesOrders as $order)
                                <option value="{{ $order->id }}" class="py-2 px-3 border-bottom">
                                    {{ $order->order_number }} - {{ $order->customer->company_name ?? 'Unknown Customer' }}
                                </option>
                            @empty
                                <option value="" disabled>No Approved Sales Orders found.</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <a href="{{ route('dashboard') }}" class="btn btn-light me-2">Cancel</a>
                        <button type="button" class="btn btn-primary px-4" onclick="generateInvoice()">
                            Proceed to Dispatch/Invoice <i class="ph-fill ph-arrow-right ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function generateInvoice() {
    var saleId = document.getElementById('saleSelect').value;
    if (!saleId) {
        alert('Please select a Sales Order from the list first.');
        return;
    }
    // Redirect to the dispatch creation page which generates the invoice
    window.location.href = '/sales/' + saleId + '/dispatch/create';
}
</script>
@endpush
@endsection
