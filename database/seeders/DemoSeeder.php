<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Store;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Honda' => ['Civic', 'HR-V'], 'Toyota' => ['Corolla', 'Hilux']] as $brandName => $models) {
            $brand = VehicleBrand::firstOrCreate(['name' => $brandName]);
            foreach ($models as $modelName) {
                VehicleModel::firstOrCreate(['brand_id' => $brand->id, 'name' => $modelName]);
            }
        }

        $stores = [
            ['name' => 'Autovale Seminovos', 'slug' => 'autovale', 'domains' => ['loja-a.localhost', 'dev.vehicle-platform-web'], 'primary_color' => '#173F5F', 'secondary_color' => '#F6AE2D'],
            ['name' => 'Prime Motors', 'slug' => 'prime-motors', 'domains' => ['loja-b.localhost'], 'primary_color' => '#202124', 'secondary_color' => '#B33F62'],
        ];

        $features = collect([
            ['name' => 'Ar condicionado', 'category' => 'Conforto'],
            ['name' => 'Câmera de ré', 'category' => 'Segurança'],
            ['name' => 'Central multimídia', 'category' => 'Tecnologia'],
            ['name' => 'Bancos de couro', 'category' => 'Conforto'],
            ['name' => 'Direção elétrica', 'category' => 'Conforto'],
            ['name' => 'Vidros elétricos', 'category' => 'Conforto'],
            ['name' => 'Travas elétricas', 'category' => 'Conforto'],
            ['name' => 'Alarme', 'category' => 'Segurança'],
            ['name' => 'Sensor de estacionamento', 'category' => 'Segurança'],
            ['name' => 'Airbag', 'category' => 'Segurança'],
            ['name' => 'Freios ABS', 'category' => 'Segurança'],
            ['name' => 'Controle de estabilidade', 'category' => 'Segurança'],
            ['name' => 'Bluetooth', 'category' => 'Tecnologia'],
            ['name' => 'Apple CarPlay', 'category' => 'Tecnologia'],
            ['name' => 'Android Auto', 'category' => 'Tecnologia'],
            ['name' => 'Teto solar', 'category' => 'Exterior'],
        ])->mapWithKeys(fn (array $feature) => [$feature['name'] => Feature::firstOrCreate(['name' => $feature['name']], $feature)]);

        foreach ($stores as $index => $data) {
            $store = Store::updateOrCreate(['slug' => $data['slug']], [
                'name' => $data['name'],
                'primary_color' => $data['primary_color'],
                'secondary_color' => $data['secondary_color'],
                'description' => 'Dados demonstrativos da loja para validação do ambiente multitenant.',
                'status' => 'active',
            ]);

            foreach ($data['domains'] as $domainIndex => $domain) {
                $store->domains()->updateOrCreate(['domain' => $domain], ['is_primary' => $domainIndex === 0, 'is_active' => true]);
            }

            $vehicle = Vehicle::updateOrCreate(['store_id' => $store->id, 'slug' => $index === 0 ? 'honda-civic-exl' : 'toyota-corolla-xei'], [
                'brand' => $index === 0 ? 'Honda' : 'Toyota',
                'model' => $index === 0 ? 'Civic' : 'Corolla',
                'version' => $index === 0 ? 'EXL 2.0' : 'XEi 2.0',
                'manufacture_year' => 2022 + $index,
                'model_year' => 2023 + $index,
                'mileage' => 32000 + ($index * 11000),
                'transmission' => 'automatic',
                'fuel_type' => 'flex',
                'color' => $index === 0 ? 'Cinza' : 'Prata',
                'doors' => 4,
                'license_plate' => $index === 0 ? 'ABC1D23' : 'DEF4G56',
                'price' => $index === 0 ? 139900 : 149900,
                'description' => 'Veículo demonstrativo para validação da vitrine pública.',
                'status' => 'available',
                'published_at' => now(),
            ]);

            $vehicle->features()->sync($features->take($index === 0 ? 3 : 2)->pluck('id'));
        }

        if ($password = env('SEED_ADMIN_PASSWORD')) {
            $user = User::updateOrCreate(['email' => env('SEED_ADMIN_EMAIL', 'admin@example.test')], [
                'name' => 'Administrador demonstrativo',
                'password' => $password,
                'global_role' => 'platform_admin',
            ]);

            $user->stores()->syncWithoutDetaching([
                Store::where('slug', 'autovale')->value('id') => ['role' => 'store_admin', 'is_active' => true],
            ]);
        }
    }
}
