<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveAuthenticatedStore
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $stores = $user->stores()->wherePivot('is_active', true)->with('domains')->get();
        $requestedDomain = $request->header('X-Store-Domain');
        $domain = strtolower(rtrim((string) ($requestedDomain ?: $request->getHost()), '.'));
        $store = $stores->first(fn ($candidate) => $candidate->domains->contains('domain', $domain));

        if (! $store && ! $requestedDomain && $stores->count() === 1) {
            $store = $stores->first();
        }

        if (! $store) {
            return response()->json(['message' => 'Informe um domínio de loja autorizado.'], 403);
        }

        $request->attributes->set('store', $store);

        return $next($request);
    }
}
