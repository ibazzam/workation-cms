<?php

namespace App\Http\Controllers;

use App\Models\TransportHold;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;

class TransportHoldController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'schedule_id' => 'required|integer',
            'seat_class' => 'nullable|string',
            'seats' => 'required|integer|min:1',
            'idempotency_key' => 'nullable|string',
            'ttl_seconds' => 'nullable|integer|min:1',
        ]);

        try {
            $hold = TransportHold::createHold(
                $data['schedule_id'],
                $data['seat_class'] ?? null,
                $data['seats'],
                $data['idempotency_key'] ?? null,
                $data['ttl_seconds'] ?? 900,
                ['user' => $request->user()?->id ?? null]
            );

            return response()->json(['hold' => $hold], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function confirm(TransportHold $hold): JsonResponse
    {
        try {
            $hold->confirm();
            return response()->json(['hold' => $hold]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function release(TransportHold $hold): JsonResponse
    {
        try {
            $hold->release();
            return response()->json(['hold' => $hold]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
