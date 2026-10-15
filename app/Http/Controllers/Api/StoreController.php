<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StoreController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $store = $request->attributes->get('store');
        abort_unless($request->user()->stores()->whereKey($store->id)->wherePivotIn('role', ['store_admin', 'store_manager'])->exists(), 403);
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'primary_color' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'header_background' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'site_background' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'footer_background' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'footer_text_color' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'footer_accent_color' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'settings' => ['sometimes', 'array'],
            'logo' => ['sometimes', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'favicon' => ['sometimes', 'file', 'mimes:ico,png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $oldFiles = [];
        DB::transaction(function () use ($request, $store, $validated, &$oldFiles): void {
            $data = collect($validated)->only(['name', 'description', 'primary_color', 'secondary_color', 'header_background', 'site_background', 'text_color', 'footer_background', 'footer_text_color', 'footer_accent_color', 'settings'])->all();
            foreach (['logo' => 'logo_path', 'favicon' => 'favicon_path'] as $input => $column) {
                if ($request->hasFile($input)) {
                    if ($store->{$column}) {
                        $oldFiles[] = $store->{$column};
                    }
                    $data[$column] = $request->file($input)->store('stores/'.$store->id, 'public');
                }
            }
            $store->update($data);
        });
        foreach ($oldFiles as $oldFile) {
            Storage::disk('public')->delete($oldFile);
        }

        return response()->json(['data' => $store->fresh()->makeVisible(['logo_path', 'favicon_path'])]);
    }
}
