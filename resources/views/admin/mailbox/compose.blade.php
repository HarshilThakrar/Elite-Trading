@extends('layouts.app')

@section('title', 'Compose Mail')

@section('content')
<div class="row">
    <!-- Compose Mail -->
    <div class="col-md-12">
        <form action="{{ route('mailbox.send') }}" method="POST">
            @csrf
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h3 class="card-title m-0 fw-semibold" style="color: var(--text-primary); font-size: 1.25rem;">
                        Compose New Message
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('mailbox.index') }}" class="btn btn-sm btn-light border"><i class="ph ph-x me-1"></i> Discard</a>
                    </div>
                </div>
                
                <div class="card-body p-4">
                    <div class="form-group mb-3">
                        <input name="to" class="form-control form-control-lg border-0 border-bottom bg-transparent px-0" style="border-radius: 0; box-shadow: none;" placeholder="To (Email Address)" required>
                    </div>
                    <div class="form-group mb-4">
                        <input name="subject" class="form-control form-control-lg border-0 border-bottom bg-transparent px-0" style="border-radius: 0; box-shadow: none;" placeholder="Subject" required>
                    </div>
                    <div class="form-group">
                        <textarea id="compose-textarea" name="body" class="form-control border bg-light" style="height: 300px; border-radius: 8px;" placeholder="Write your message here..."></textarea>
                    </div>
                </div>
                
                <div class="card-footer bg-white border-top py-3">
                    <button type="submit" class="btn btn-primary px-4"><i class="ph ph-paper-plane-tilt me-2"></i> Send</button>
                    <button type="reset" class="btn btn-outline-secondary ms-2"><i class="ph ph-eraser me-2"></i> Draft</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
