<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\PlatformStoreController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\StoreUserController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\VehicleImageController;
use App\Models\Feature;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

Route::prefix('v1/auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('v1/admin')->middleware(['auth:sanctum', 'store.user'])->group(function (): void {
    Route::get('status-history', [VehicleController::class, 'history']);
    Route::apiResource('vehicles', VehicleController::class);
    Route::patch('vehicles/{vehicle}/status', [VehicleController::class, 'status']);
    Route::post('vehicles/{vehicle}/images', [VehicleImageController::class, 'store']);
    Route::patch('vehicles/{vehicle}/images/{image}', [VehicleImageController::class, 'update']);
    Route::delete('vehicles/{vehicle}/images/{image}', [VehicleImageController::class, 'destroy']);
    Route::match(['put', 'post'], 'store', [StoreController::class, 'update']);
    Route::get('contacts', [ContactController::class, 'index']);
    Route::get('users', [StoreUserController::class, 'index']);
    Route::post('users', [StoreUserController::class, 'store']);
    Route::patch('users/{user}', [StoreUserController::class, 'update']);
});

Route::prefix('v1/platform')->middleware(['auth:sanctum', 'global.admin'])->group(function (): void {
    Route::get('stores', [PlatformStoreController::class, 'index']);
    Route::post('stores', [PlatformStoreController::class, 'store']);
    Route::patch('stores/{store}', [PlatformStoreController::class, 'update']);
    Route::delete('stores/{store}', [PlatformStoreController::class, 'destroy']);
    Route::post('stores/{store}/domains', [PlatformStoreController::class, 'addDomain']);
    Route::delete('stores/{store}/domains/{domain}', [PlatformStoreController::class, 'removeDomain']);
    Route::post('catalog/brands', [CatalogController::class, 'brand']);
    Route::patch('catalog/brands/{brand}', [CatalogController::class, 'updateBrand']);
    Route::delete('catalog/brands/{brand}', [CatalogController::class, 'destroyBrand']);
    Route::post('catalog/models', [CatalogController::class, 'model']);
    Route::patch('catalog/models/{model}', [CatalogController::class, 'updateModel']);
    Route::delete('catalog/models/{model}', [CatalogController::class, 'destroyModel']);
    Route::post('catalog/features', [CatalogController::class, 'feature']);
});

Route::prefix('v1')->middleware('store.domain')->group(function (): void {
    Route::get('/public/store', function () {
        $store = request()->attributes->get('store');

        return response()->json([
            'data' => [
                'name' => $store->name,
                'slug' => $store->slug,
                'description' => $store->description,
                'logo_path' => $store->logo_path,
                'favicon_path' => $store->favicon_path,
                'theme' => [
                    'primary' => $store->primary_color,
                    'secondary' => $store->secondary_color,
                    'header' => $store->header_background,
                    'background' => $store->site_background,
                    'text' => $store->text_color,
                    'footer' => $store->footer_background,
                    'footer_text' => $store->footer_text_color,
                    'footer_accent' => $store->footer_accent_color,
                ],
                'settings' => $store->settings ?? [],
            ],
        ]);
    });

    Route::post('/public/contacts', [ContactController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/public/catalog', [CatalogController::class, 'index']);

    Route::get('/public/features', function () {
        return response()->json(['data' => Feature::query()->orderBy('category')->orderBy('name')->get(['id', 'name', 'category'])]);
    });

    Route::get('/public/vehicles', function () {
        $store = request()->attributes->get('store');
        $search = request()->string('search')->trim()->toString();
        $query = Vehicle::query()->where('store_id', $store->id)->where('status', 'available');

        if ($search !== '') {
            $query->where(fn($builder) => $builder
                ->where('brand', 'ilike', "%{$search}%")
                ->orWhere('model', 'ilike', "%{$search}%")
                ->orWhere('version', 'ilike', "%{$search}%"));
        }

        foreach (['brand', 'transmission', 'fuel_type'] as $filter) {
            if ($value = request()->string($filter)->toString()) {
                $query->where($filter, $value);
            }
        }

        if (request()->filled('min_price')) {
            $query->where('price', '>=', request()->input('min_price'));
        }
        if (request()->filled('max_price')) {
            $query->where('price', '<=', request()->input('max_price'));
        }
        if (request()->filled('min_year')) {
            $query->where('model_year', '>=', request()->integer('min_year'));
        }
        if (request()->filled('max_year')) {
            $query->where('model_year', '<=', request()->integer('max_year'));
        }

        return $query->with(['images', 'features'])
            ->latest()
            ->paginate(12)
            ->through(fn(Vehicle $vehicle): array => [
                'id' => $vehicle->id,
                'slug' => $vehicle->slug,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'version' => $vehicle->version,
                'manufacture_year' => $vehicle->manufacture_year,
                'model_year' => $vehicle->model_year,
                'mileage' => $vehicle->mileage,
                'transmission' => $vehicle->transmission,
                'fuel_type' => $vehicle->fuel_type,
                'color' => $vehicle->color,
                'doors' => $vehicle->doors,
                'price' => $vehicle->price,
                'description' => $vehicle->description,
                'images' => $vehicle->images->map(fn($image) => ['path' => $image->path, 'is_primary' => $image->is_primary])->values(),
                'features' => $vehicle->features->map(fn($feature) => ['name' => $feature->name, 'category' => $feature->category])->values(),
            ]);
    });

    Route::get('/public/vehicles/{slug}', function (string $slug) {
        $store = request()->attributes->get('store');
        $vehicle = Vehicle::query()
            ->where('store_id', $store->id)
            ->where('slug', $slug)
            ->with(['images', 'features'])
            ->firstOrFail();

        return response()->json(['data' => [
            'id' => $vehicle->id,
            'slug' => $vehicle->slug,
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'version' => $vehicle->version,
            'manufacture_year' => $vehicle->manufacture_year,
            'model_year' => $vehicle->model_year,
            'mileage' => $vehicle->mileage,
            'transmission' => $vehicle->transmission,
            'fuel_type' => $vehicle->fuel_type,
            'color' => $vehicle->color,
            'doors' => $vehicle->doors,
            'price' => $vehicle->price,
            'description' => $vehicle->description,
            'status' => $vehicle->status,
            'images' => $vehicle->images->map(fn($image) => ['path' => $image->path, 'is_primary' => $image->is_primary])->values(),
            'features' => $vehicle->features->map(fn($feature) => ['name' => $feature->name, 'category' => $feature->category])->values(),
        ]]);
    });
});
