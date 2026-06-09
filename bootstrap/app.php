<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleInertiaRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__."/../routes/api.php",
        web: __DIR__."/../routes/web.php",
        commands: __DIR__."/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        $middleware->redirectGuestsTo(fn() => route("login"));

        $middleware->alias([
            "role" => EnsureUserHasRole::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is("api/*"),
        );

        $exceptions->render(function (
            \Illuminate\Routing\Exceptions\InvalidSignatureException $e,
            Request $request,
        ) {
            if( $request->is("email/verify/*") ) {
                return redirect()
                    ->route("login")
                    ->with("error", "Link de verificação inválido ou expirado.");
            }
        });
    })->create();
