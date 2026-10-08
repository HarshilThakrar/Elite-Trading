@extends('layouts.auth')

@section('content')
<div class="col-md-5 col-lg-4">
    <div class="text-center mb-4">
        <h2 class="text-primary-custom fw-bold">Demo ERP</h2>
        <p class="text-muted">Enter your credentials to access your account</p>
    </div>
    
    <div class="card card-custom p-4 border-0 shadow-sm rounded-4">
        <div class="card-body p-2">
            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <i class="bi bi-x-circle-fill me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('status'))
                <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i> {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger mb-3">
                    <ul class="mb-0 small ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" id="loginForm">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="name@company.com" required autofocus>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label for="password" class="form-label fw-semibold mb-0">Password</label>
                        <a href="{{ route('password.request') }}" class="text-decoration-none small text-primary-custom">Forgot Password?</a>
                    </div>
                    <input type="password" class="form-control mt-2" id="password" name="password" placeholder="Enter password" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label small" for="remember">Remember me</label>
                </div>
                <button type="submit" class="btn btn-primary-custom w-100 py-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                </button>
            </form>
        </div>
    </div>
    <div class="text-center mt-4 text-muted small">
        &copy; {{ date('Y') }} Demo ERP. All rights reserved.
    </div>
</div>

<script>
    // If the login page is open for more than 45 minutes, automatically refresh to keep session and CSRF token fresh
    setTimeout(function() {
        window.location.reload();
    }, 45 * 60 * 1000);
</script>
@endsection
