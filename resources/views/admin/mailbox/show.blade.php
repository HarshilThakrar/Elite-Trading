@extends('layouts.app')

@section('title', 'Read Mail')

@section('content')
<div class="row">
    <!-- Read Mail -->
    <div class="col-md-12">
        <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h3 class="card-title m-0 fw-semibold" style="color: var(--text-primary); font-size: 1.25rem;">
                    {{ $email->subject }}
                </h3>
                <div class="card-tools">
                    <a href="{{ route('mailbox.index') }}" class="btn btn-sm btn-light border"><i class="ph ph-arrow-left me-1"></i> Back to Inbox</a>
                </div>
            </div>
            
            <div class="card-body p-4">
                <div class="mailbox-read-info mb-4 pb-3 border-bottom d-flex justify-content-between align-items-start">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar bg-primary text-white d-flex align-items-center justify-content-center rounded-circle" style="width: 48px; height: 48px; font-size: 1.25rem; font-weight: bold;">
                            {{ strtoupper(substr($email->from_name ?? $email->from_email ?? 'U', 0, 1)) }}
                        </div>
                        <div>
                            @if($email->folder == 'sent')
                                <h6 class="m-0 fw-semibold" style="color: var(--text-primary);">To: {{ $email->to_emails }}</h6>
                            @else
                                <h6 class="m-0 fw-semibold" style="color: var(--text-primary);">{{ $email->from_name }}</h6>
                                <span class="text-muted" style="font-size: 0.9rem;">&lt;{{ $email->from_email }}&gt;</span>
                            @endif
                        </div>
                    </div>
                    <span class="mailbox-read-time text-muted" style="font-size: 0.9rem;">{{ $email->created_at->format('d M Y, h:i A') }}</span>
                </div>
                
                <div class="mailbox-read-message" style="font-size: 1rem; color: var(--text-secondary); line-height: 1.6;">
                    {!! $email->body !!}
                </div>
            </div>
            
            <div class="card-footer bg-white border-top py-3">
                <form action="{{ route('mailbox.destroy', $email->id) }}" method="POST" style="display:inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger me-2"><i class="ph ph-trash me-1"></i> Delete</button>
                </form>
                <button type="button" class="btn btn-outline-primary me-2"><i class="ph ph-arrow-u-up-left me-1"></i> Reply</button>
                <button type="button" class="btn btn-outline-secondary"><i class="ph ph-arrow-u-up-right me-1"></i> Forward</button>
            </div>
        </div>
    </div>
</div>
@endsection
