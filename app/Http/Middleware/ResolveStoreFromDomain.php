<?php

namespace App\Http\Middleware;

use App\Models\StoreDomain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveStoreFromDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $domain = strtolower(rtrim((string) ($request->header('X-Store-Domain') ?: $request->getHost()), '.'));
        $store = StoreDomain::query()
            ->where('domain', $domain)
            ->where('is_active', true)
            ->whereHas('store', fn ($query) => $query->where('status', 'active'))
            ->with('store')
            ->first()?->store;

        if (! $store) {
            return response()->json(['message' => 'Loja não encontrada para este domínio.'], 404);
        }

        $request->attributes->set('store', $store);

        return $next($request);
    }
}
