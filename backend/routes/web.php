<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Single Page Application
|--------------------------------------------------------------------------
|
| Catch-all route to serve the compiled Vue 3 SPA index.html for all
| front-end views (/, /login, /register, etc.), while preserving backend
| API, storage, and broadcasting endpoints.
|
*/

Route::get('robots.txt', function () {
    $robotsPath = public_path('robots.txt');
    if (file_exists($robotsPath)) {
        return response(file_get_contents($robotsPath), 200, [
            'Content-Type' => 'text/plain',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive, nosnippet, noimageindex',
        ]);
    }

    return response("User-agent: *\nDisallow: /\n", 200, [
        'Content-Type' => 'text/plain',
        'X-Robots-Tag' => 'noindex, nofollow, noarchive, nosnippet, noimageindex',
    ]);
});

Route::get('/{any?}', function () {
    $indexPath = public_path('index.html');
    if (file_exists($indexPath)) {
        return response(file_get_contents($indexPath), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive, nosnippet, noimageindex',
        ]);
    }

    return response()->view('welcome', [], 200, [
        'X-Robots-Tag' => 'noindex, nofollow, noarchive, nosnippet, noimageindex',
    ]);
})->where('any', '^(?!(?:.+/)?(?:api|sanctum|broadcasting|storage|robots\.txt)(?:/|$)).*$');
