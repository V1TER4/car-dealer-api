<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('vehicle')) ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(['draft', 'available', 'reserved', 'sold', 'inactive'])], 'sold_at' => ['nullable', 'required_if:status,sold', 'date']];
    }
}
