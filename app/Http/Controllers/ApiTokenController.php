<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiToken;
use Illuminate\Support\Str;

class ApiTokenController extends Controller
{
    /**
     * Показать список API токенов
     */
    public function index()
    {
        $tokens = ApiToken::orderBy('created_at', 'desc')->get();
        return view('api-tokens.index', compact('tokens'));
    }

    /**
     * Показать форму создания токена
     */
    public function create()
    {
        return view('api-tokens.create');
    }

    /**
     * Создать новый API токен
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'expires_at' => 'nullable|date|after:now'
        ]);

        $token = ApiToken::createToken(
            $request->name,
            $request->description,
            $request->expires_at ? \Carbon\Carbon::parse($request->expires_at) : null
        );

        return redirect()->route('api-tokens.index')
            ->with('success', 'API токен успешно создан!')
            ->with('new_token', $token->token);
    }

    /**
     * Показать информацию о токене
     */
    public function show(ApiToken $apiToken)
    {
        return view('api-tokens.show', compact('apiToken'));
    }

    /**
     * Деактивировать токен
     */
    public function deactivate(ApiToken $apiToken)
    {
        $apiToken->update(['is_active' => false]);
        
        return redirect()->route('api-tokens.index')
            ->with('success', 'API токен деактивирован!');
    }

    /**
     * Активировать токен
     */
    public function activate(ApiToken $apiToken)
    {
        $apiToken->update(['is_active' => true]);
        
        return redirect()->route('api-tokens.index')
            ->with('success', 'API токен активирован!');
    }

    /**
     * Удалить токен
     */
    public function destroy(ApiToken $apiToken)
    {
        $apiToken->delete();
        
        return redirect()->route('api-tokens.index')
            ->with('success', 'API токен удален!');
    }
}
