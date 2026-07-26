<?php

use App\Http\Middleware\CashierPin;
use App\Http\Middleware\CompanyContext;
use App\Http\Middleware\EnsureCompanyIsActive;
use App\Http\Middleware\RequireFeature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'company.active' => EnsureCompanyIsActive::class,
            'company.context' => CompanyContext::class,
            'pos.cashier' => CashierPin::class,
            'feature' => RequireFeature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
