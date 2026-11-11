<?php

use Illuminate\Support\Facades\Route;

// API and missing static assets must never be mistaken for client-side routes.
Route::get('/{path?}', function (?string $path = null) {
    if ($path !== null && str_contains(basename($path), '.')) {
        abort(404);
    }

    $index = public_path('app/index.html');
    abort_unless(is_file($index), 503, 'Frontend build is not available.');

    return response(file_get_contents($index), 200, ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-cache']);
})->where('path', '(?!(?:api|app|up)(?:/|$)).*');
