<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'field-agent' => \App\Http\Middleware\EnsureFieldAgent::class,
            'distributor' => \App\Http\Middleware\EnsureDistributor::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'agent_stock_request' => \App\Http\Middleware\AgentStockRequestAccess::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\ShareNotificationData::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
