<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'force.password.change' => \App\Http\Middleware\ForcePasswordChange::class,
            'check.admin.active' => \App\Http\Middleware\CheckAdminActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // PHP throws the body away when it is larger than post_max_size, so the
        // request never reaches validation. The registration forms upload ID
        // photos and business permits, and a bare 413 page is what makes an
        // oversized file look like a dead submit button.
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'That upload is larger than this server accepts. Use a file under 10MB, or have the server raise its upload limit.',
            ], 413);
        });
    })->create();
