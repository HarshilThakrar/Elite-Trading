<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 - Page Expired | Demo ERP</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            max-width: 480px;
            width: 100%;
            padding: 2.5rem;
            text-align: center;
        }
        .error-icon {
            width: 72px;
            height: 72px;
            background-color: #fff7ed;
            color: #ea580c;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 1.5rem;
        }
        .btn-primary-custom {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 0.65rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-primary-custom:hover {
            background-color: #1d4ed8;
            color: #ffffff;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">
            <i class="bi bi-clock-history"></i>
        </div>
        <h3 class="fw-bold mb-2">Page Expired</h3>
        <p class="text-muted mb-4 small">
            Your secure session expired due to inactivity. Please refresh the page or return to the sign-in screen to continue.
        </p>
        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
            <a href="{{ route('login') }}" class="btn btn-primary-custom">
                <i class="bi bi-box-arrow-in-right me-1"></i> Return to Sign In
            </a>
            <button onclick="window.location.reload()" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Page
            </button>
        </div>
    </div>
</body>
</html>
