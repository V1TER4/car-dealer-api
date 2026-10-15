<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Vehicle::class) ?? false;
    }

    public function rules(): array
    {
        $currentYear = (int) now()->format('Y') + 1;

        return [
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'version' => ['nullable', 'string', 'max:100'],
            'manufacture_year' => ['required', 'integer', 'min:1950', 'max:'.$currentYear],
            'model_year' => ['required', 'integer', 'gte:manufacture_year', 'max:'.$currentYear + 1],
            'mileage' => ['required', 'integer', 'min:0', 'max:2000000'],
            'transmission' => ['required', Rule::in(['manual', 'automatic', 'automated', 'cvt'])],
            'fuel_type' => ['required', Rule::in(['flex', 'gasoline', 'ethanol', 'diesel', 'electric', 'hybrid'])],
            'color' => ['nullable', 'string', 'max:60'],
            'doors' => ['nullable', 'integer', Rule::in([2, 3, 4, 5])],
            'license_plate' => ['nullable', 'string', 'regex:/^[A-Z0-9]{7}$/i'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['sometimes', Rule::in(['draft', 'available', 'reserved', 'sold', 'inactive'])],
            'feature_ids' => ['sometimes', 'array'],
            'feature_ids.*' => ['integer', 'exists:features,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->license_plate) {
            $this->merge(['license_plate' => strtoupper(preg_replace('/[^A-Z0-9]/i', '', $this->license_plate))]);
        }
    }
}
