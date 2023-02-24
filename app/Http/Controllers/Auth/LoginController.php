<?php

namespace App\Http\Controllers\Auth;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(): View
    {
        return view('auth.login');
    }

    public function doLogin(Request $request)
    {
        return $this->attemptLogin($request);
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();

        return redirect()->route('dashboard');
    }

    protected function attemptLogin(Request $request)
    {
        if (method_exists($this, 'hasTooManyLoginAttempts') && $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($this->looksLikeAMatric($request->username)) {
            abort(Response::HTTP_FORBIDDEN, 'Matric numbers are not allowed');
        }

        if (config('ldap.authentication', true)) {
            if (! \Ldap::authenticate($request->username, $request->password)) {
                throw ValidationException::withMessages([
                    'authentication' => 'You have entered an invalid GUID or password',
                ]);
            }
        }

        $user = User::where('username', '=', $request->username)->first();
        if (! $user) {
            $ldapUser = \Ldap::findUser($request->username);
            if (! $ldapUser) {
                throw ValidationException::withMessages([
                    'authentication' => 'You have entered an invalid GUID or password',
                ]);
            }
            $user = new User();
            $user->username = $ldapUser->username;
            $user->forenames = $ldapUser->forenames;
            $user->surname = $ldapUser->surname;
            $user->email = $ldapUser->email;
            $user->password = bcrypt(Str::random(64));
            $user->is_staff = true;
            $user->save();
        }

        Auth::login($user);

        return redirect(route('home'));
    }

    protected function looksLikeAMatric(string $username)
    {
        return preg_match('/^[0-9]/', trim($username)) === 1;
    }

    protected function looksLikeStudentAccount(string $username): bool
    {
        $user = User::where('username', '=', $username)->first();
        if ($user && $user->is_staff) {
            return false;
        }

        return preg_match('/^[0-9].+/', $username) === 1;
    }
}
