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

Route::get('/{any?}', function () {
    $indexPath = public_path('index.html');
    if (file_exists($indexPath)) {
        return response(file_get_contents($indexPath), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    return view('welcome');
})->where('any', '^(?!api|sanctum|broadcasting|storage).*$');
