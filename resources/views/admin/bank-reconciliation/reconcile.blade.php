@extends('layouts.app')

@section('title', 'Reconcile Bank Transactions')
@section('header_title', 'Reconcile: ' . $ledger->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Reconcile: {{ $ledger->name }}</h2>
        <div>
            <form method="POST" action="{{ route('bank-reconciliation.autoMatch', $ledger->id) }}" class="d-inline">
                @csrf
                <input type="hidden" name="tolerance" value="3">
                <button type="submit" class="btn btn-success shadow-sm" onclick="return confirm('Run Auto-Match on unreconciled transactions?')">
                    <i class="ph ph-magic-wand me-1"></i> Auto Match (3 Days)
                </button>
            </form>
            <a href="{{ route('bank-reconciliation.index', ['bank_ledger_id' => $ledger->id, 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn btn-light border shadow-sm ms-2">
                <i class="ph ph-arrow-left me-1"></i> Back to Summary
            </a>
        </div>
    </div>

    <!-- Summary Bar -->
    <div class="card shadow mb-4 border-bottom-primary">
        <div class="card-body p-3">
            <div class="row text-center">
                <div class="col-md-3 border-end">
                    <div class="text-xs text-uppercase text-muted">Book Balance</div>
                    <div class="h5 font-weight-bold text-gray-800 mb-0">₹{{ number_format($summary['book_balance'], 2) }}</div>
                </div>
                <div class="col-md-3 border-end">
                    <div class="text-xs text-uppercase text-muted">Bank Statement Balance</div>
                    <div class="h5 font-weight-bold text-gray-800 mb-0">₹{{ number_format($summary['bank_balance'], 2) }}</div>
                </div>
                <div class="col-md-3 border-end">
                    <div class="text-xs text-uppercase text-muted">Reconciled Diff</div>
                    <div class="h5 font-weight-bold {{ abs($summary['difference']) > 0.01 ? 'text-danger' : 'text-success' }} mb-0">
                        ₹{{ number_format($summary['difference'], 2) }}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-xs text-uppercase text-muted">Unreconciled Items</div>
                    <div class="h5 font-weight-bold text-gray-800 mb-0">
                        {{ $bookTxns->whereNull('matched_journal_entry_id')->count() }} Book / {{ $bankTxns->where('reconciliation_status', 'Unreconciled')->count() }} Bank
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Split Screen UI -->
    <div class="row">
        <!-- ERP Transactions (Left) -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">ERP / Book Transactions</h6>
                    <span class="badge bg-primary rounded-pill">{{ $bookTxns->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 700px; overflow-y: auto;">
                        <table class="table table-hover table-sm mb-0 align-middle">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th width="40">Select</th>
                                    <th>Date</th>
                                    <th>Voucher / Ref</th>
                                    <th class="text-end">Debit (In)</th>
                                    <th class="text-end">Credit (Out)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bookTxns as $bookTxn)
                                    @php 
                                        // Check if this journal entry is matched in the bankTxns collection
                                        $matchedBankTxn = $bankTxns->firstWhere('matched_journal_entry_id', $bookTxn->id);
                                        $isMatched = $matchedBankTxn !== null;
                                    @endphp
                                    <tr class="{{ $isMatched ? 'table-success bg-opacity-25' : '' }}">
                                        <td class="text-center">
                                            @if(!$isMatched)
                                                <input type="radio" name="book_sel" class="book-radio form-check-input" value="{{ $bookTxn->id }}" data-amount="{{ $bookTxn->amount }}" data-type="{{ $bookTxn->type }}">
                                            @else
                                                <i class="ph ph-check-circle text-success fs-5"></i>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($bookTxn->voucher->date)->format('d-m-Y') }}</td>
                                        <td>
                                            <div><small class="fw-bold">{{ $bookTxn->voucher->voucher_number }}</small></div>
                                            <div class="text-muted" style="font-size:0.75rem;">{{ $bookTxn->voucher->reference_number ?? '-' }}</div>
                                        </td>
                                        <td class="text-end text-success">{{ $bookTxn->type === 'Dr' ? number_format($bookTxn->amount, 2) : '-' }}</td>
                                        <td class="text-end text-danger">{{ $bookTxn->type === 'Cr' ? number_format($bookTxn->amount, 2) : '-' }}</td>
                                        <td>
                                            @if($isMatched)
                                                <span class="badge bg-success">Reconciled</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Unreconciled</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No book transactions found for this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bank Statement Transactions (Right) -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-light">
                    <h6 class="m-0 font-weight-bold text-success">Bank Statement Transactions</h6>
                    <span class="badge bg-success rounded-pill">{{ $bankTxns->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 700px; overflow-y: auto;">
                        <table class="table table-hover table-sm mb-0 align-middle">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th width="40">Select</th>
                                    <th>Date</th>
                                    <th>Desc / Ref</th>
                                    <th class="text-end">Deposit</th>
                                    <th class="text-end">Withdrawal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bankTxns as $bankTxn)
                                    @php $isMatched = $bankTxn->reconciliation_status === 'Reconciled'; @endphp
                                    <tr class="{{ $isMatched ? 'table-success bg-opacity-25' : ($bankTxn->reconciliation_status === 'Ignored' ? 'table-secondary text-muted' : '') }}">
                                        <td class="text-center">
                                            @if(!$isMatched && $bankTxn->reconciliation_status !== 'Ignored')
                                                <input type="radio" name="bank_sel" class="bank-radio form-check-input" value="{{ $bankTxn->id }}" data-deposit="{{ $bankTxn->credit_amount }}" data-withdrawal="{{ $bankTxn->debit_amount }}">
                                            @elseif($isMatched)
                                                <i class="ph ph-check-circle text-success fs-5"></i>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($bankTxn->transaction_date)->format('d-m-Y') }}</td>
                                        <td>
                                            <div title="{{ $bankTxn->description }}" style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                                <small>{{ $bankTxn->description }}</small>
                                            </div>
                                            <div class="text-muted" style="font-size:0.75rem;">{{ $bankTxn->reference_number ?? '-' }}</div>
                                        </td>
                                        <td class="text-end text-success">{{ $bankTxn->credit_amount > 0 ? number_format($bankTxn->credit_amount, 2) : '-' }}</td>
                                        <td class="text-end text-danger">{{ $bankTxn->debit_amount > 0 ? number_format($bankTxn->debit_amount, 2) : '-' }}</td>
                                        <td>
                                            @if($isMatched)
                                                <form action="{{ route('bank-reconciliation.unmatch', [$ledger->id, $bankTxn->id]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Unreconcile" onclick="return confirm('Unreconcile this transaction?')">
                                                        <i class="ph ph-x"></i>
                                                    </button>
                                                </form>
                                            @elseif($bankTxn->reconciliation_status === 'Ignored')
                                                <span class="badge bg-secondary">Ignored</span>
                                            @else
                                                <div class="dropdown d-inline">
                                                    <button class="btn btn-sm btn-light py-0 px-1 border" type="button" data-bs-toggle="dropdown">
                                                        <i class="ph ph-dots-three-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                        <li>
                                                            <form action="{{ route('bank-reconciliation.ignore', [$ledger->id, $bankTxn->id]) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item text-secondary"><i class="ph ph-eye-slash me-2"></i> Exclude/Ignore</button>
                                                            </form>
                                                        </li>
                                                        @if($bankTxn->debit_amount > 0)
                                                            <li><a class="dropdown-item" href="{{ route('payment-vouchers.create') }}"><i class="ph ph-money me-2"></i> Create Payment</a></li>
                                                        @else
                                                            <li><a class="dropdown-item" href="{{ route('receipt-vouchers.create') }}"><i class="ph ph-receipt me-2"></i> Create Receipt</a></li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No bank transactions found. <a href="{{ route('bank-reconciliation.import', $ledger->id) }}">Import Statement</a></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Bar (Sticky Bottom) -->
    <div class="card shadow fixed-bottom mb-4 mx-4" style="left:250px; right:0; width:auto; z-index:100; display:none;" id="matchActionBar">
        <div class="card-body p-3 d-flex justify-content-between align-items-center bg-light border-top border-primary border-4">
            <div>
                <span class="me-3"><strong>Book Selected:</strong> <span id="bookSelAmount" class="text-primary">0.00</span></span>
                <span class="me-3"><strong>Bank Selected:</strong> <span id="bankSelAmount" class="text-success">0.00</span></span>
                <span id="matchStatus" class="fw-bold"></span>
            </div>
            <div>
                <button type="button" class="btn btn-secondary me-2" id="clearSelectionBtn">Clear Selection</button>
                <button type="button" class="btn btn-primary" id="manualMatchBtn" disabled>
                    <i class="ph ph-arrows-merge"></i> Match Selected
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let selectedBookId = null;
    let selectedBankId = null;
    let bookAmount = 0;
    let bankAmount = 0;
    
    function updateMatchBar() {
        let bookChecked = $('.book-radio:checked');
        let bankChecked = $('.bank-radio:checked');
        
        selectedBookId = bookChecked.val();
        selectedBankId = bankChecked.val();
        
        if (selectedBookId || selectedBankId) {
            $('#matchActionBar').slideDown();
        } else {
            $('#matchActionBar').slideUp();
        }
        
        if (selectedBookId) {
            bookAmount = parseFloat(bookChecked.data('amount')) || 0;
            let type = bookChecked.data('type');
            $('#bookSelAmount').text((type === 'Dr' ? '+' : '-') + bookAmount.toFixed(2));
        } else {
            bookAmount = 0;
            $('#bookSelAmount').text('0.00');
        }
        
        if (selectedBankId) {
            let dep = parseFloat(bankChecked.data('deposit')) || 0;
            let withd = parseFloat(bankChecked.data('withdrawal')) || 0;
            bankAmount = dep > 0 ? dep : withd;
            let type = dep > 0 ? '+' : '-';
            $('#bankSelAmount').text(type + bankAmount.toFixed(2));
        } else {
            bankAmount = 0;
            $('#bankSelAmount').text('0.00');
        }
        
        // Validate match
        if (selectedBookId && selectedBankId) {
            // Check if amounts exactly match and directions match
            // Book Dr = Bank Deposit (Credit)
            // Book Cr = Bank Withdrawal (Debit)
            let bookType = bookChecked.data('type');
            let bankDep = parseFloat(bankChecked.data('deposit')) || 0;
            let bankWithd = parseFloat(bankChecked.data('withdrawal')) || 0;
            
            let directionMatch = (bookType === 'Dr' && bankDep > 0) || (bookType === 'Cr' && bankWithd > 0);
            let amountMatch = Math.abs(bookAmount - bankAmount) < 0.01;
            
            if (directionMatch && amountMatch) {
                $('#matchStatus').text('Ready to match').removeClass('text-danger').addClass('text-success');
                $('#manualMatchBtn').prop('disabled', false);
            } else {
                $('#matchStatus').text('Amounts or directions do not match').removeClass('text-success').addClass('text-danger');
                $('#manualMatchBtn').prop('disabled', true);
            }
        } else {
            $('#matchStatus').text('');
            $('#manualMatchBtn').prop('disabled', true);
        }
    }
    
    $('.book-radio, .bank-radio').change(updateMatchBar);
    
    $('#clearSelectionBtn').click(function() {
        $('.book-radio, .bank-radio').prop('checked', false);
        updateMatchBar();
    });
    
    $('#manualMatchBtn').click(function() {
        if (!selectedBookId || !selectedBankId) return;
        
        $(this).prop('disabled', true).html('<i class="ph ph-spinner ph-spin"></i> Matching...');
        
        $.ajax({
            url: "{{ route('bank-reconciliation.match', $ledger->id) }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                journal_entry_id: selectedBookId,
                bank_statement_transaction_id: selectedBankId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    toastr.error(response.message);
                    $('#manualMatchBtn').prop('disabled', false).html('<i class="ph ph-arrows-merge"></i> Match Selected');
                }
            },
            error: function(xhr) {
                let msg = xhr.responseJSON?.message || 'An error occurred during matching.';
                toastr.error(msg);
                $('#manualMatchBtn').prop('disabled', false).html('<i class="ph ph-arrows-merge"></i> Match Selected');
            }
        });
    });
});
</script>
@endpush
