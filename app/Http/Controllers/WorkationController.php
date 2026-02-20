<?php

namespace App\Http\Controllers;

use App\Models\Workation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Workation::query()->latest()->get());
    }

    public function show(Workation $workation): JsonResponse
    {
        return response()->json($workation);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $workation = Workation::create($validated);

        return response()->json($workation, 201);
    }

    public function update(Request $request, Workation $workation): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after_or_equal:start_date'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
        ]);

        $workation->update($validated);

        return response()->json($workation);
    }

    public function destroy(Workation $workation): JsonResponse
    {
        $workation->delete();

        return response()->json(null, 204);
    }
}
