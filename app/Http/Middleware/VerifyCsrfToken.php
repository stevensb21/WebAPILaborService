<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'safety/store-person',
        'safety/update-person/*',
        'safety/store-certificate',
        'safety/update-certificate/*',
        'safety/update-certificate-info/*',
        'safety/delete-person/*',
        'safety/delete-certificate/*',
    ];
}
