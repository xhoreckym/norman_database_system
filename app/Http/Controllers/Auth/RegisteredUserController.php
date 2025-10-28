<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Backend\UserLoginRetention;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = new User([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        // Set explicit ID to avoid sequence conflict
        $user->id = User::max('id') + 1;
        $user->save();

        $user->assignRole('user');

        event(new Registered($user));

        Auth::login($user);

        // Track the initial login after registration
        try {
            $metaData = [
                'user_agent' => $request->userAgent(),
                'country' => null,
                'referer' => $request->header('referer'),
                'session_id' => $request->session()->getId(),
                'registration' => true, // Flag to indicate this was a registration login
            ];

            $loginRetention = UserLoginRetention::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'login_datetime' => now(),
                'meta_data' => $metaData,
            ]);

            Log::info('User registration login tracked successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'retention_id' => $loginRetention->id,
                'timestamp' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track user registration login', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toDateTimeString(),
            ]);
        }

        return redirect(route('dashboard', absolute: false));
    }
}
