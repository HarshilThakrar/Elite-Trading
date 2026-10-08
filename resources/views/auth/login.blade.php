@extends('layouts.auth')

@section('content')
<div class="col-md-5 col-lg-4">
    <div class="text-center mb-4">
        <h2 class="text-primary-custom fw-bold">Demo ERP</h2>
        <p class="text-muted">Enter your credentials to access your account</p>
    </div>
    
    <div class="card card-custom p-4">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label for="password" class="form-label fw-semibold mb-0">Password</label>
                        <a href="{{ route('password.request') }}" class="text-decoration-none small text-primary-custom">Forgot Password?</a>
                    </div>
                    <input type="password" class="form-control mt-2" id="password" name="password" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <button type="submit" class="btn btn-primary-custom w-100 py-2 fw-semibold">Sign In</button>
            </form>
        </div>
    </div>
    <div class="text-center mt-4 text-muted small">
        &copy; {{ date('Y') }} Demo ERP. All rights reserved.
    </div>
</div>
@endsection
