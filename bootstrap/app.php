<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'upload.limits' => \App\Http\Middleware\IncreaseUploadLimits::class,
        ]);
        
        // Исключения для CSRF защиты
        $middleware->validateCsrfTokens(except: [
            'safety/store-person',
            'safety/update-person/*',
            'safety/store-certificate',
            'safety/update-certificate/*',
            'safety/update-certificate-info/*',
            'safety/delete-person/*',
            'safety/delete-certificate/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
