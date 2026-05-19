<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];

    /**
     * Handle the token mismatch exception.
     *
     * This provides a better user experience when the CSRF token has expired
     * by redirecting back with a friendly message instead of showing a 419 error page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, \Closure $next)
    {
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException $e) {
            // Log the CSRF mismatch for debugging
            Log::warning('CSRF token mismatch', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'session_id' => $request->session()->getId(),
                'has_session' => $request->hasSession(),
                'ip' => $request->ip(),
            ]);

            // For login form, regenerate token and redirect back with message
            if ($request->routeIs('login') && $request->isMethod('post')) {
                return redirect()
                    ->back()
                    ->withInput($request->only('email', 'remember'))
                    ->withErrors(['email' => 'Phiên làm việc đã hết hạn. Vui lòng thử lại.']);
            }

            // For other pages, redirect to login
            if (!$request->expectsJson()) {
                return redirect()
                    ->route('login')
                    ->withErrors(['email' => 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.']);
            }

            throw $e;
        }
    }
}
