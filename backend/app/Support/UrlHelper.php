<?php

namespace App\Support;

class UrlHelper
{
    /**
     * Resolve the frontend base URL for client links (activation, reset password, email verification).
     * Works dynamically for both Root deployments (e.g. https://domain.com) and
     * Subdirectory deployments (e.g. https://domain.com/connectly or https://domain.com/subdir).
     */
    public static function frontendUrl(): string
    {
        // 1. If active HTTP request exists, dynamically determine the scheme, host, and path
        if (!app()->runningInConsole() && request()) {
            $req = request();

            // 1a. Explicit header sent by frontend axios instance
            $clientHeader = (string) $req->header('X-Frontend-Base-Url');
            if ($clientHeader) {
                return rtrim($clientHeader, '/');
            }

            // 1b. Check Referer header (e.g. https://domain.com/connectly/login or https://domain.com/login)
            $referer = (string) $req->header('referer');
            if ($referer) {
                $parts = parse_url($referer);
                if (!empty($parts['host'])) {
                    $scheme = $parts['scheme'] ?? $req->getScheme();
                    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
                    $path = $parts['path'] ?? '';

                    // Strip known trailing client routes to find base subfolder
                    $knownRoutes = [
                        '/login', '/register', '/forgot-password', '/reset-password',
                        '/activate-account', '/verify-email-change'
                    ];
                    foreach ($knownRoutes as $route) {
                        if (str_ends_with($path, $route)) {
                            $path = substr($path, 0, -strlen($route));
                            break;
                        }
                    }
                    $path = rtrim($path, '/');
                    return "{$scheme}://{$parts['host']}{$port}{$path}";
                }
            }

            // 1c. Derive from request root (which contains the detected base URL e.g. /connectly or root)
            $root = rtrim($req->root(), '/');
            if ($root && !in_array($req->getHost(), ['localhost', '127.0.0.1'], true)) {
                return $root;
            }
        }

        // 2. Fallback to FRONTEND_URL or APP_URL config from environment
        $baseUrl = rtrim((string) (config('app.frontend_url') ?: config('app.url', 'http://localhost:8000')), '/');

        return $baseUrl;
    }
}
