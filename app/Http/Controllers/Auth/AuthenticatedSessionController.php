<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Backend\UserLoginRetention;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Stevebauman\Location\Facades\Location;

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
            try {
                $ipAddress = $request->ip();
                $country = null;

                // Get country from IP using geolocation
                $position = Location::get($ipAddress);
                if ($position) {
                    $country = $position->countryCode;
                }

                $metaData = [
                    'user_agent' => $request->userAgent(),
                    'country' => $country,
                    'referer' => $request->header('referer'),
                    'session_id' => $request->session()->getId(),
                ];

                $loginRetention = UserLoginRetention::create([
                    'user_id' => $user->id,
                    'ip_address' => $ipAddress,
                    'login_datetime' => now(),
                    'meta_data' => $metaData,
                ]);

                Log::info('User login tracked successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ipAddress,
                    'country' => $country,
                    'retention_id' => $loginRetention->id,
                    'timestamp' => now()->toDateTimeString(),
                ]);
            } catch (\Exception $e) {
                // Log the error but allow login to proceed
                Log::error('Failed to track user login in UserLoginRetention', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $request->ip(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'timestamp' => now()->toDateTimeString(),
                ]);
            }
        } else {
            Log::warning('User login tracking skipped - Auth::user() returned null after authentication');
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
