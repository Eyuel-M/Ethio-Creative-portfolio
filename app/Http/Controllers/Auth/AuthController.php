<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ClientProfile;
use App\Models\FreelancerProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required']);
        if (! Auth::attempt(['email' => strtolower($data['email']), 'password' => $data['password']], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        if ($request->user()->isAdmin()) {
            \App\Models\AuditLog::record('admin.login', $request->user());
        }

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(Request $request)
    {
        return view('auth.register', ['role' => $request->query('role', 'freelancer')]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => 'required|in:freelancer,client',
            'terms' => 'accepted',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
            ]);
            self::enableRole($user, $data['role']);
            $user->forceFill(['active_workspace' => $data['role']])->save();

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    public static function enableRole(User $user, string $role): void
    {
        if ($role === 'freelancer' && ! $user->freelancerProfile) {
            $user->forceFill(['is_freelancer' => true])->save();
            FreelancerProfile::forceCreate(['user_id' => $user->id, 'slug' => Str::slug($user->name).'-'.Str::lower(Str::random(5))]);
        }
        if ($role === 'client' && ! $user->clientProfile) {
            $user->forceFill(['is_client' => true])->save();
            ClientProfile::forceCreate(['user_id' => $user->id, 'type' => 'individual']);
        }
        $user->unsetRelation('freelancerProfile')->unsetRelation('clientProfile');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
