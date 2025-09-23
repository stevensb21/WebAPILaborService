<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiToken;

class ApiTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization');
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'API токен не предоставлен'
            ], 401);
        }

        // Убираем "Bearer " префикс если есть
        $token = str_replace('Bearer ', '', $token);

        $apiToken = ApiToken::where('token', $token)->first();

        if (!$apiToken) {
            return response()->json([
                'success' => false,
                'message' => 'Неверный API токен'
            ], 401);
        }

        if (!$apiToken->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'API токен неактивен или истек'
            ], 401);
        }

        // Обновляем время последнего использования
        $apiToken->updateLastUsed();

        // Добавляем токен в запрос для дальнейшего использования
        $request->merge(['api_token' => $apiToken]);

        return $next($request);
    }
}
