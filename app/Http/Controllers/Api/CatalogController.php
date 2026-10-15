<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $brands = VehicleBrand::query()->where('is_active', true)->with(['models' => fn ($query) => $query->where('is_active', true)->orderBy('name')])->orderBy('name')->get();

        return response()->json(['data' => $brands]);
    }

    public function brand(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:vehicle_brands,name']]);

        return response()->json(['data' => VehicleBrand::create($data)], 201);
    }

    public function model(Request $request): JsonResponse
    {
        $data = $request->validate(['brand_id' => ['required', 'exists:vehicle_brands,id'], 'name' => ['required', 'string', 'max:100', Rule::unique('vehicle_models', 'name')->where(fn ($query) => $query->where('brand_id', $request->integer('brand_id')))]]);
        $model = VehicleModel::create([...$data, 'is_active' => true]);

        return response()->json(['data' => $model], 201);
    }

    public function updateBrand(Request $request, VehicleBrand $brand): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('vehicle_brands', 'name')->ignore($brand->id)]]);
        $brand->update($data);

        return response()->json(['data' => $brand->fresh()]);
    }

    public function updateModel(Request $request, VehicleModel $model): JsonResponse
    {
        $data = $request->validate(['brand_id' => ['required', 'exists:vehicle_brands,id'], 'name' => ['required', 'string', 'max:100', Rule::unique('vehicle_models', 'name')->where(fn ($query) => $query->where('brand_id', $request->integer('brand_id')))->ignore($model->id)]]);
        $model->update($data);

        return response()->json(['data' => $model->fresh('brand')]);
    }

    public function destroyBrand(VehicleBrand $brand): JsonResponse
    {
        $brand->delete();

        return response()->json(status: 204);
    }

    public function destroyModel(VehicleModel $model): JsonResponse
    {
        $model->delete();

        return response()->json(status: 204);
    }

    public function feature(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120', 'unique:features,name'], 'category' => ['required', 'string', 'max:60']]);

        return response()->json(['data' => Feature::create($data)], 201);
    }
}
