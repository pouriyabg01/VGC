<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
//use Illuminate\Foundation\Exceptions\Handler as Exceptions;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
//        $exceptions->render(function (ModelNotFoundException $e, $request) {
//            return (new class
//                {
//                    use \App\Traits\apiResponse;
//                    })->sendError('',class_basename($e->getModel()) . ' not found');
//                });
//
//        // Invalid route (URL not found)
//        $exceptions->render(function (NotFoundHttpException $e, $request) {
//            return (new class
//            {
//                use \App\Traits\apiResponse;
//                })->sendError('','Resource not found');
//            });
    })->create();
