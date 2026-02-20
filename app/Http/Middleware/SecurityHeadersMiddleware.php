<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * セキュリティヘッダーを付与するミドルウェア
 *
 * XSS、クリックジャッキング、MIMEスニッフィングなどの
 * 攻撃を防ぐためのHTTPヘッダーを設定します。
 */
class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // XSSフィルターを有効化（レガシーブラウザ向け）
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // MIMEスニッフィングを防止
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // クリックジャッキングを防止
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // リファラー情報を制限
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 権限ポリシーを設定
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation()');

        // HTTPS使用時はHSTSを有効化
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
