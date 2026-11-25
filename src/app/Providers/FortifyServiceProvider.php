<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::loginView(fn () => view('Pages.auth.login'));
        Fortify::registerView(fn () => view('Pages.auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('Pages.auth.forgot-password'));
        Fortify::resetPasswordView(fn ($request) => view('Pages.auth.reset-password', ['request' => $request]));
        Fortify::authenticateUsing(function (Request $request): ?User {
            Validator::make($request->only('email'), [
                'email' => ['email', 'max:255'],
            ])->validate();

            $user = User::query()
                ->where('email', Str::lower((string) $request->input('email')))
                ->where('is_active', true)
                ->whereNull('soft_deleted_at')
                ->first();

            return $user && Hash::check((string) $request->input('password'), $user->getAuthPassword())
                ? $user
                : null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
