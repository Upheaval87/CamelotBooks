<?php

use App\Http\Middleware\BindTenantConnection;
use App\Http\Middleware\CashierPin;
use App\Http\Middleware\CompanyContext;
use App\Http\Middleware\EnsureCompanyIsActive;
use App\Http\Middleware\RequireFeature;
use App\Http\Middleware\SegregationOfDuty;
use App\Http\Middleware\SuperAdmin;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('branch-quotations:expire')->daily();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'company.active' => EnsureCompanyIsActive::class,
            'company.context' => CompanyContext::class,
            'pos.cashier' => CashierPin::class,
            'feature' => RequireFeature::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'sod' => SegregationOfDuty::class,
            'superadmin' => SuperAdmin::class,
            'tenant.bind' => BindTenantConnection::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return response()->view('errors.404', [], 404);
        });
    })->create();
