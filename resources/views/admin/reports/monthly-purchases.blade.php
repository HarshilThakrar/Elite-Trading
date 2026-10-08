@extends('layouts.app')

@section('title', 'Monthly Purchases Report')
@section('header_title', 'Monthly Purchases')

@section('content')
<div class="card card-custom border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h3 class="card-title m-0 fw-semibold text-dark">Historical Monthly Purchases</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="py-3 px-4 border-bottom-0">Month & Year</th>
                        <th class="py-3 px-4 border-bottom-0">Items Purchased</th>
                        <th class="py-3 text-center border-bottom-0">Total Orders</th>
                        <th class="py-3 text-end px-4 border-bottom-0">Total Purchases (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monthlyData as $data)
                    <tr>
                        <td class="py-3 px-4 fw-medium text-dark">{{ $data['month_name'] }}</td>
                        <td class="py-3 px-4">
                            <ul class="mb-0 list-unstyled" style="font-size: 0.85rem;">
                                @foreach($data['items'] as $itemName => $qty)
                                    <li class="mb-1"><i class="ph-fill ph-package text-muted me-1"></i> {{ $itemName }}: <strong>{{ number_format($qty, 2) }}</strong></li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="py-3 text-center">
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                                {{ $data['total_orders'] }}
                            </span>
                        </td>
                        <td class="py-3 text-end px-4 fw-bold text-success">
                            ₹{{ number_format($data['total_purchases'], 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="ph ph-receipt mb-2 d-block text-black-50" style="font-size: 3rem;"></i>
                            No purchase data found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
