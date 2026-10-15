<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleRequest;
use App\Http\Requests\VehicleStatusRequest;
use App\Models\Vehicle;
use App\Models\VehicleStatusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $store = $request->attributes->get('store');
        $query = Vehicle::query()->where('store_id', $store->id)->with(['images', 'features'])->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('brand', 'ilike', "%{$search}%")->orWhere('model', 'ilike', "%{$search}%"));
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function history(Request $request): JsonResponse
    {
        $store = $request->attributes->get('store');
        $query = VehicleStatusHistory::query()->whereHas('vehicle', fn ($builder) => $builder->where('store_id', $store->id))->with(['vehicle:id,brand,model,version', 'user:id,name'])->latest();
        if ($status = $request->string('status')->toString()) {
            $query->where('to_status', $status);
        }

        return response()->json(['data' => $query->paginate(20)]);
    }

    public function store(VehicleRequest $request): JsonResponse
    {
        $store = $request->attributes->get('store');
        $this->authorize('create', Vehicle::class);
        $data = $request->validated();
        $featureIds = $data['feature_ids'] ?? [];
        unset($data['feature_ids']);
        $data['store_id'] = $store->id;
        $data['slug'] = Str::slug(implode('-', array_filter([$data['brand'], $data['model'], $data['version'] ?? null]))).'-'.Str::lower(Str::random(6));
        $data['license_plate'] = $data['license_plate'] ?? null;
        if (($data['status'] ?? 'draft') === 'available') {
            $data['published_at'] = now();
        }

        $vehicle = DB::transaction(function () use ($data, $featureIds): Vehicle {
            $vehicle = Vehicle::create($data);
            $vehicle->features()->sync($featureIds);

            return $vehicle;
        });

        return response()->json(['data' => $vehicle->load('features', 'images')], 201);
    }

    public function show(Request $request, Vehicle $vehicle): JsonResponse
    {
        abort_unless($vehicle->store_id === $request->attributes->get('store')->id, 404);

        return response()->json(['data' => $vehicle->load('features', 'images')]);
    }

    public function update(VehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('update', $vehicle);
        abort_unless($vehicle->store_id === $request->attributes->get('store')->id, 404);
        $data = $request->validated();
        $featureIds = $data['feature_ids'] ?? null;
        unset($data['feature_ids']);
        if (($data['status'] ?? $vehicle->status) === 'sold' && $vehicle->status !== 'sold') {
            $data['sold_at'] = now();
        }
        if (($data['status'] ?? $vehicle->status) === 'available' && ! $vehicle->published_at) {
            $data['published_at'] = now();
        }
        $vehicle->update($data);
        if ($featureIds !== null) {
            $vehicle->features()->sync($featureIds);
        }

        return response()->json(['data' => $vehicle->fresh()->load('features', 'images')]);
    }

    public function status(VehicleStatusRequest $request, Vehicle $vehicle): JsonResponse
    {
        abort_unless($vehicle->store_id === $request->attributes->get('store')->id, 404);
        $status = $request->validated('status');
        $vehicle->status = $status;
        if ($status === 'sold') {
            $vehicle->sold_at = $request->validated('sold_at') ?? $vehicle->sold_at ?? now();
        }
        if ($status !== 'sold') {
            $vehicle->sold_at = null;
        }
        if ($status === 'available' && ! $vehicle->published_at) {
            $vehicle->published_at = now();
        }
        $fromStatus = $vehicle->getOriginal('status');
        $vehicle->save();
        if ($fromStatus !== $status) {
            VehicleStatusHistory::create(['vehicle_id' => $vehicle->id, 'user_id' => $request->user()->id, 'from_status' => $fromStatus, 'to_status' => $status]);
        }

        return response()->json(['data' => $vehicle->fresh()->load('features', 'images')]);
    }

    public function destroy(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('delete', $vehicle);
        abort_unless($vehicle->store_id === $request->attributes->get('store')->id, 404);
        $vehicle->delete();

        return response()->json(status: 204);
    }
}
