<?php

use App\Models\Store;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createStoreWithDomain(string $slug, string $domain): Store
{
    $store = Store::create([
        'name' => ucfirst($slug),
        'slug' => $slug,
        'status' => 'active',
    ]);

    $store->domains()->create([
        'domain' => $domain,
        'is_primary' => true,
        'is_active' => true,
    ]);

    return $store;
}

function createVehicleFor(Store $store, string $slug, string $status = 'available'): Vehicle
{
    return $store->vehicles()->create([
        'slug' => $slug,
        'brand' => 'Honda',
        'model' => 'Civic',
        'version' => 'EXL',
        'manufacture_year' => 2023,
        'model_year' => 2024,
        'mileage' => 10000,
        'transmission' => 'automatic',
        'fuel_type' => 'flex',
        'doors' => 4,
        'price' => 120000,
        'status' => $status,
        'published_at' => $status === 'available' ? now() : null,
        'sold_at' => $status === 'sold' ? now() : null,
    ]);
}

it('resolves the public store from the validated domain', function (): void {
    createStoreWithDomain('autovale', 'loja-a.localhost');

    $this->getJson('/api/v1/public/store', ['X-Store-Domain' => 'loja-a.localhost'])
        ->assertOk()
        ->assertJsonPath('data.slug', 'autovale');

    $this->getJson('/api/v1/public/store', ['X-Store-Domain' => 'loja-inexistente.localhost'])
        ->assertNotFound();
});

it('does not expose another store vehicle through public endpoints', function (): void {
    $storeA = createStoreWithDomain('autovale', 'loja-a.localhost');
    $storeB = createStoreWithDomain('prime', 'loja-b.localhost');
    createVehicleFor($storeA, 'honda-civic');
    $vehicleB = createVehicleFor($storeB, 'toyota-corolla');

    $this->getJson('/api/v1/public/vehicles', ['X-Store-Domain' => 'loja-a.localhost'])
        ->assertOk()
        ->assertJsonMissing(['slug' => $vehicleB->slug]);

    $this->getJson('/api/v1/public/vehicles/'.$vehicleB->slug, ['X-Store-Domain' => 'loja-a.localhost'])
        ->assertNotFound();
});

it('blocks an authenticated user from selecting an unauthorized store', function (): void {
    $storeA = createStoreWithDomain('autovale', 'loja-a.localhost');
    createStoreWithDomain('prime', 'loja-b.localhost');
    $user = User::factory()->create();
    $user->stores()->attach($storeA, ['role' => 'store_admin', 'is_active' => true]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/admin/vehicles', ['X-Store-Domain' => 'loja-b.localhost'])
        ->assertForbidden();
});

it('removes sold vehicles from the public inventory while retaining them', function (): void {
    $store = createStoreWithDomain('autovale', 'loja-a.localhost');
    $vehicle = createVehicleFor($store, 'honda-civic', 'sold');

    $this->getJson('/api/v1/public/vehicles', ['X-Store-Domain' => 'loja-a.localhost'])
        ->assertOk()
        ->assertJsonMissing(['slug' => $vehicle->slug]);

    expect($vehicle->fresh()->sold_at)->not->toBeNull();
});

it('allows authorized staff to reserve and sell through the status endpoint', function (): void {
    $store = createStoreWithDomain('autovale', 'loja-a.localhost');
    $vehicle = createVehicleFor($store, 'honda-civic');
    $user = User::factory()->create();
    $user->stores()->attach($store, ['role' => 'store_admin', 'is_active' => true]);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/admin/vehicles/'.$vehicle->id.'/status', ['status' => 'reserved'], ['X-Store-Domain' => 'loja-a.localhost'])
        ->assertOk()
        ->assertJsonPath('data.status', 'reserved');

    expect($vehicle->fresh()->status)->toBe('reserved');
});

it('restricts platform store management to global administrators', function (): void {
    $user = User::factory()->create(['global_role' => 'platform_admin']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/platform/stores')
        ->assertOk();
});
