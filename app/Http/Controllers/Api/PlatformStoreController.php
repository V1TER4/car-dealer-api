<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlatformStoreController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Store::with('domains')->withCount('vehicles')->orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'alpha_dash', 'max:80', 'unique:stores,slug'],
            'domain' => ['required', 'string', 'max:180', 'unique:store_domains,domain'],
            'status' => ['sometimes', Rule::in(['active', 'suspended'])],
        ]);
        $store = DB::transaction(function () use ($data): Store {
            $store = Store::create(['name' => $data['name'], 'slug' => $data['slug'], 'status' => $data['status'] ?? 'active']);
            $store->domains()->create(['domain' => strtolower($data['domain']), 'is_primary' => true, 'is_active' => true]);

            return $store;
        });

        return response()->json(['data' => $store->load('domains')], 201);
    }

    public function update(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'slug' => ['sometimes', 'alpha_dash', 'max:80', Rule::unique('stores', 'slug')->ignore($store->id)],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::in(['active', 'suspended'])],
        ]);
        $store->update($data);

        return response()->json(['data' => $store->fresh()->load('domains')]);
    }

    public function destroy(Store $store): JsonResponse
    {
        abort_if($store->vehicles()->exists(), 422, 'A loja possui veículos e não pode ser excluída. Suspenda a loja para preservar o histórico.');
        abort_if($store->users()->exists(), 422, 'A loja possui usuários vinculados e não pode ser excluída.');
        DB::transaction(function () use ($store): void {
            $store->domains()->delete();
            $store->delete();
        });

        return response()->json(status: 204);
    }

    public function addDomain(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate(['domain' => ['required', 'string', 'max:180', 'unique:store_domains,domain']]);
        $domain = $store->domains()->create(['domain' => strtolower($data['domain']), 'is_primary' => false, 'is_active' => true]);

        return response()->json(['data' => $domain], 201);
    }

    public function removeDomain(Store $store, string $domain): JsonResponse
    {
        $record = $store->domains()->where('domain', strtolower($domain))->firstOrFail();
        abort_if($record->is_primary, 422, 'O domínio principal não pode ser removido.');
        $record->delete();

        return response()->json(status: 204);
    }
}
