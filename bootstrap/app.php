<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // グローバルミドルウェア: セキュリティヘッダーを付与
        $middleware->append(SecurityHeadersMiddleware::class);

        // リバプロ配下の信頼設定(ホスト nginx → ECS コンテナ内 nginx → PHP-FPM)
        // X-Forwarded-Proto を信頼させて HTTPS 配下と認識させる(asset/url の http:// 防止)
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
