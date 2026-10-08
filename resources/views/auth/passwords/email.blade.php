@extends('layouts.auth')

@section('content')
<div class="col-md-5 col-lg-4">
    <div class="text-center mb-4">
        <h2 class="text-primary-custom fw-bold">Demo ERP</h2>
        <p class="text-muted">Reset your password</p>
    </div>
    
    <div class="card card-custom p-4">
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="#">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary-custom w-100 py-2 fw-semibold">Send Password Reset Link</button>
            </form>

            <div class="mt-3 text-center">
                <a href="{{ route('login') }}" class="text-decoration-none small text-primary-custom">Back to Login</a>
            </div>
        </div>
    </div>
</div>
@endsection
