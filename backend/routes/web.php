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

Route::get('/{any?}', function (\Illuminate\Http\Request $request) {
    $indexPath = public_path('index.html');
    if (!file_exists($indexPath)) {
        return response()->view('welcome', [], 200, [
            'X-Robots-Tag' => 'noindex, nofollow, noarchive, nosnippet, noimageindex',
        ]);
    }

    $content = file_get_contents($indexPath);

    // Calculate dynamic base URL from the incoming request (e.g. "" for root or "/connectly" or "/subdir")
    $baseUrl = rtrim($request->getBaseUrl(), '/');
    if ($baseUrl === '') {
        $segments = array_values(array_filter(explode('/', $request->path())));
        $reserved = [
            'login', 'register', 'activate-account', 'reset-password', 'verify-email-change',
            'api', 'sanctum', 'broadcasting', 'storage', 'robots.txt', 'assets', 'favicon.ico',
            'up', 'index.php'
        ];
        if (!empty($segments) && !in_array($segments[0], $reserved, true)) {
            $baseUrl = '/' . $segments[0];
        }
    }
    $baseHref = $baseUrl !== '' ? "{$baseUrl}/" : '/';
    $apiBase = $baseUrl !== '' ? "{$baseUrl}/api/v1" : '/api/v1';

    $injectedConfig = '<base href="' . htmlspecialchars($baseHref, ENT_QUOTES, 'UTF-8') . '">' . "\n    " .
        '<script>window.__APP_CONFIG__ = ' . json_encode([
            'basePath' => $baseHref,
            'apiBase' => $apiBase,
            'rootUrl' => $baseUrl,
        ]) . ';</script>';

    if (str_contains($content, '<base ')) {
        $content = preg_replace('/<base[^>]*>/i', '<base href="' . htmlspecialchars($baseHref, ENT_QUOTES, 'UTF-8') . '">', $content);
        $content = str_replace('</head>', '<script>window.__APP_CONFIG__ = ' . json_encode([
            'basePath' => $baseHref,
            'apiBase' => $apiBase,
            'rootUrl' => $baseUrl,
        ]) . ';</script></head>', $content);
    } else {
        $content = str_replace('<head>', "<head>\n    " . $injectedConfig, $content);
    }

    return response($content, 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
        'X-Robots-Tag' => 'noindex, nofollow, noarchive, nosnippet, noimageindex',
    ]);
})->where('any', '^(?!(?:.+/)?(?:api|sanctum|broadcasting|storage|robots\.txt)(?:/|$)).*$');
