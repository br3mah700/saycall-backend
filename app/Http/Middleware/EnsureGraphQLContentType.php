<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureGraphQLContentType
{
    /**
     * Handle an incoming request.
     *
     * Ensures that POST requests to GraphQL always have a Content-Type header set,
     * preventing Symfony 7 / Laragraph assertion errors when the client omits it.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->isMethod('POST') && ! $request->headers->has('Content-Type')) {
            $request->headers->set('Content-Type', 'application/json');
        }

        return $next($request);
    }
}

