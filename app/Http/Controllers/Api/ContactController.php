<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => Contact::query()->where('store_id', $request->attributes->get('store')->id)->with('vehicle:id,brand,model')->latest()->paginate(20)]);
    }

    public function store(ContactRequest $request): JsonResponse
    {
        $store = $request->attributes->get('store');
        $data = $request->safe()->except(['website']);

        if (! empty($data['vehicle_id']) && ! $store->vehicles()->whereKey($data['vehicle_id'])->exists()) {
            return response()->json(['message' => 'Veículo não pertence a esta loja.'], 422);
        }

        $contact = Contact::create([...$data, 'store_id' => $store->id]);

        return response()->json(['message' => 'Contato enviado com sucesso.', 'id' => $contact->id], 201);
    }
}
