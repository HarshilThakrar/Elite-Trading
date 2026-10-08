<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Gracefully handle CSRF Token Mismatch (419 Page Expired)
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session has expired. Please refresh the page and try again.'
                ], 419);
            }

            if ($request->is('login') || !auth()->check()) {
                return redirect()->route('login')->with('warning', 'Your session expired. Please sign in again.');
            }

            return redirect()->back()
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->with('warning', 'Your session expired due to inactivity. Please submit again.');
        });

        // Also catch generic 419 HTTP exceptions
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Your session has expired. Please refresh the page and try again.'
                    ], 419);
                }

                if ($request->is('login') || !auth()->check()) {
                    return redirect()->route('login')->with('warning', 'Your session expired. Please sign in again.');
                }

                return redirect()->back()
                    ->withInput($request->except('_token', 'password', 'password_confirmation'))
                    ->with('warning', 'Your session expired due to inactivity. Please submit again.');
            }
        });
    })->create();
