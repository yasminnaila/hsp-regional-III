<?php

use App\Http\Middleware\ActiveUserMiddleware;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'active' => ActiveUserMiddleware::class,
        ]);

        $middleware->redirectGuestsTo(
            fn (Request $request) => route('login')
        );

        $middleware->redirectUsersTo(
            fn (Request $request) => route('home')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
