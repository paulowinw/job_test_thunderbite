<?php

use App\Exceptions\CampaignValidationException;
use App\Exceptions\GameValidationException;
use App\Providers\AppServiceProvider;
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
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(AppServiceProvider::HOME);

        $middleware->throttleApi();

        $middleware->alias([
            'redirectIfNoActiveCampaign' => \App\Http\Middleware\RedirectIfNoActiveCampaign::class,
            'setActiveCampaign' => \App\Http\Middleware\SetActiveCampaign::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (GameValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'tileImage' => $e->tileImage,
                    'message' => $e->getMessage(),
                ], 200);
            }
        });

        $exceptions->render(function (CampaignValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 200);
            }

            return response()->view('frontend.campaign-unavailable', [
                'message' => $e->getMessage(),
            ], 200);
        });
    })->create();
