<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VehicleImageController extends Controller
{
    public function store(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('update', $vehicle);
        $this->assertStore($request, $vehicle);
        $validated = $request->validate(['images' => ['required', 'array', 'min:1', 'max:20'], 'images.*' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240']]);
        $storedPaths = [];

        try {
            $images = DB::transaction(function () use ($vehicle, $validated, &$storedPaths): array {
                $lockedVehicle = Vehicle::query()->whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
                $currentCount = $lockedVehicle->images()->count();
                if ($currentCount + count($validated['images']) > 20) {
                    abort(422, 'Cada veículo pode ter no máximo 20 imagens.');
                }

                $nextOrder = (int) $lockedVehicle->images()->max('sort_order') + 1;
                $created = [];
                foreach ($validated['images'] as $file) {
                    $path = $file->store('vehicles/'.$lockedVehicle->id, 'public');
                    $storedPaths[] = $path;
                    $created[] = $lockedVehicle->images()->create(['path' => $path, 'sort_order' => $nextOrder++, 'is_primary' => $currentCount === 0 && count($created) === 0]);
                }

                return $created;
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }

        return response()->json(['data' => $images], 201);
    }

    public function update(Request $request, Vehicle $vehicle, VehicleImage $image): JsonResponse
    {
        $this->authorize('update', $vehicle);
        $this->assertStore($request, $vehicle);
        abort_unless($image->vehicle_id === $vehicle->id, 404);
        $data = $request->validate(['sort_order' => ['sometimes', 'integer', 'min:0', 'max:19'], 'is_primary' => ['sometimes', 'boolean']]);
        DB::transaction(function () use ($vehicle, $image, $data): void {
            if (($data['is_primary'] ?? false) === true) {
                $vehicle->images()->update(['is_primary' => false]);
            }
            $image->update($data);
        });

        return response()->json(['data' => $image->fresh()]);
    }

    public function destroy(Request $request, Vehicle $vehicle, VehicleImage $image): JsonResponse
    {
        $this->authorize('update', $vehicle);
        $this->assertStore($request, $vehicle);
        abort_unless($image->vehicle_id === $vehicle->id, 404);
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return response()->json(status: 204);
    }

    private function assertStore(Request $request, Vehicle $vehicle): void
    {
        abort_unless($vehicle->store_id === $request->attributes->get('store')->id, 404);
    }
}
