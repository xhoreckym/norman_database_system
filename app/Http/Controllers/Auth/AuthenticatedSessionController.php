<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Backend\UserLoginRetention;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Log the user login
        $user = Auth::user();
        if ($user) {
            $metaData = [
                'user_agent' => $request->userAgent(),
                'country' => null, // Implement IP geolocation service if needed
                'referer' => $request->header('referer'),
                'session_id' => $request->session()->getId(),
            ];

            UserLoginRetention::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'login_datetime' => now(),
                'meta_data' => $metaData,
            ]);
        }

        return redirect()->intended(route('home', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
