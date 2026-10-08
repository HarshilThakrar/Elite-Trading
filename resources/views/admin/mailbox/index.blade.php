@extends('layouts.app')

@section('title', 'Mailbox')

@section('content')
<div class="row">
    <!-- Email List -->
    <div class="col-md-12">
        <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h3 class="card-title m-0 fw-semibold" style="color: var(--text-primary); font-size: 1.25rem;">
                    {{ ucfirst($folder) }}
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive mailbox-messages">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.95rem;">
                        <tbody>
                            @forelse($emails as $email)
                            <tr style="cursor: pointer; transition: background-color 0.2s;" onclick="window.location='{{ route('mailbox.show', $email->id) }}'" class="{{ $email->is_read ? 'bg-white' : 'bg-light' }}">
                                <td class="pl-4 py-3" style="width: 280px; border-bottom: 1px solid #f1f5f9;">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar bg-primary text-white d-flex justify-content-center align-items-center rounded-circle" style="width: 36px; height: 36px; font-weight: 600;">
                                            {{ strtoupper(substr($email->from_name ?? $email->from_email ?? 'U', 0, 1)) }}
                                        </div>
                                        <a href="{{ route('mailbox.show', $email->id) }}" class="text-decoration-none {{ $email->is_read ? 'text-secondary' : 'text-dark fw-bold' }}">
                                            {{ Str::limit($email->from_name ?? $email->from_email ?? $email->to_emails, 25) }}
                                        </a>
                                    </div>
                                </td>
                                <td class="py-3" style="border-bottom: 1px solid #f1f5f9;">
                                    <span class="{{ $email->is_read ? 'text-dark' : 'fw-bold text-dark' }}">{{ Str::limit($email->subject, 50) }}</span>
                                    <span class="text-muted ms-2">- {{ Str::limit(trim($email->body_plain), 60) }}</span>
                                </td>
                                <td class="py-3 text-end" style="width: 50px; border-bottom: 1px solid #f1f5f9;">
                                    @if($email->has_attachments)
                                        <i class="ph ph-paperclip text-muted"></i>
                                    @endif
                                </td>
                                <td class="mailbox-date pr-4 py-3 text-end text-muted" style="width: 140px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem;">
                                    {{ $email->created_at->diffForHumans() }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="ph ph-tray mb-2 d-block text-black-50" style="font-size: 3rem;"></i>
                                    No emails found in this folder.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-end">
                {{ $emails->appends(['folder' => $folder])->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
