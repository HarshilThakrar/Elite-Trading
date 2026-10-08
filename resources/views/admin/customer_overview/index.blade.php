@extends('layouts.app')

@section('title', 'Customer Overview - Demo ERP')
@section('header_title', 'Customer Overview')

@section('content')
<div class="table-container mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center p-3 border-bottom">
        <h5 class="mb-0 fw-bold text-primary-custom">Customer Performance Overview</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="overviewTable" class="data-table table table-hover">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Customer Name</th>
                        <th class="text-end">Month Consumption (₹)</th>
                        <th class="text-end">Yearly Consumption (₹)</th>
                        <th class="text-end">Gross Profit (₹ / %)</th>
                        <th class="text-center">Avg Payment Terms</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $index => $customer)
                    <tr>
                        <td class="fw-bold text-center">{{ $index + 1 }}</td>
                        <td>
                            <a href="{{ route('customers.show', $customer->id) }}" class="fw-semibold text-primary-custom text-decoration-none">
                                {{ $customer->company_name ?? $customer->customer_name }}
                            </a>
                        </td>
                        <td class="text-end fw-semibold">
                            {{ number_format($customer->month_consumption, 2) }}
                        </td>
                        <td class="text-end fw-semibold text-primary">
                            {{ number_format($customer->yearly_consumption, 2) }}
                        </td>
                        <td class="text-end fw-bold {{ $customer->gross_profit >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($customer->gross_profit, 2) }} 
                            <br>
                            <small class="text-muted">({{ number_format($customer->gross_profit_percentage, 2) }}%)</small>
                        </td>
                        <td class="text-center">
                            @if($customer->payment_terms)
                                <span class="badge bg-info text-dark rounded-pill px-3">
                                    @if(str_starts_with($customer->payment_terms, 'Net'))
                                        {{ trim(str_replace('Net', '', $customer->payment_terms)) }} Days
                                    @else
                                        {{ $customer->payment_terms }}
                                    @endif
                                </span>
                            @else
                                <span class="text-muted small">Not Set</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if($.fn.DataTable) {
            $('#overviewTable').DataTable({
                "pageLength": 25,
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search overview..."
                }
            });
        }
    });
</script>
@endpush
