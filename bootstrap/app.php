<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsurePasswordChanged;

return Application::configure(basePath: dirname(__DIR__))
    ->registered(function ($app) {
        $publicPath = file_exists(base_path('public_html')) 
            ? base_path('public_html') 
            : dirname(base_path()) . '/public_html';
        $app->usePublicPath(path: realpath($publicPath) ?: $publicPath);
    })
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'password.changed' => EnsurePasswordChanged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
