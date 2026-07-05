<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
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
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return url("/reset-password?" . http_build_query([
                "token" => $token,
                "email" => $user->getEmailForPasswordReset(),
            ]));
        });

        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            if( $response->request->is("api/*") ) {
                return null;
            }

            $pages = [
                401 => "Errors/Unauthorized",
                403 => "Errors/Forbidden",
                404 => "Errors/NotFound",
            ];

            if( !isset($pages[$response->statusCode()]) ) {
                return null;
            }

            return $response
                ->render($pages[$response->statusCode()])
                ->withSharedData();
        });
    }
}
