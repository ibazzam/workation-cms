<?php

namespace App\Http\Controllers;

use App\Models\Atoll;
use App\Models\Island;
use Illuminate\Http\JsonResponse;

class AtollIslandApiController extends Controller
{
    /**
     * Get all atolls with basic info.
     */
    public function getAllAtolls(): JsonResponse
    {
        $atolls = Atoll::select('id', 'name', 'slug', 'code')
            ->orderBy('name')
            ->get();

        return response()->json($atolls);
    }

    /**
     * Get islands by atoll ID.
     * Optionally filter by island_type.
     */
    public function getIslandsByAtoll(int $atollId, ?string $type = null): JsonResponse
    {
        $query = Island::where('atoll_id', $atollId)
            ->select('id', 'name', 'slug', 'island_type', 'is_inhabited')
            ->orderBy('name');

        if ($type && in_array($type, ['inhabited', 'uninhabited', 'resort'])) {
            $query->where('island_type', $type);
        }

        $islands = $query->get();

        return response()->json($islands);
    }

    /**
     * Get island thumbnail data for UI components.
     * Returns island with photo_path and atoll name.
     */
    public function getIslandWithMedia(int $islandId): JsonResponse
    {
        $island = Island::with('atoll')
            ->select('id', 'name', 'slug', 'atoll_id', 'photo_path', 'island_type', 'local_name')
            ->find($islandId);

        if (!$island) {
            return response()->json(['error' => 'Island not found'], 404);
        }

        return response()->json([
            'id' => $island->id,
            'name' => $island->name,
            'slug' => $island->slug,
            'atoll_id' => $island->atoll_id,
            'atoll_name' => $island->atoll?->name,
            'photo_path' => $island->photo_path,
            'island_type' => $island->island_type,
            'local_name' => $island->local_name,
        ]);
    }

    /**
     * Get featured islands for carousel/thumbnails.
     * Can filter by island_type.
     */
    public function getFeaturedIslands(?string $type = null, int $limit = 12): JsonResponse
    {
        $query = Island::with('atoll')
            ->select('id', 'name', 'slug', 'atoll_id', 'photo_path', 'island_type', 'is_inhabited')
            ->whereNotNull('photo_path')
            ->orderBy('name');

        if ($type && in_array($type, ['inhabited', 'uninhabited', 'resort'])) {
            $query->where('island_type', $type);
        }

        $islands = $query->limit($limit)->get();

        return response()->json($islands->map(fn ($island) => [
            'id' => $island->id,
            'name' => $island->name,
            'slug' => $island->slug,
            'atoll_name' => $island->atoll?->name,
            'photo_path' => $island->photo_path,
            'island_type' => $island->island_type,
        ]));
    }

    /**
     * Get atoll with island count breakdown.
     */
    public function getAtollStats(int $atollId): JsonResponse
    {
        $atoll = Atoll::with('islands')
            ->find($atollId);

        if (!$atoll) {
            return response()->json(['error' => 'Atoll not found'], 404);
        }

        $inhabited = $atoll->islands->where('island_type', 'inhabited')->count();
        $uninhabited = $atoll->islands->where('island_type', 'uninhabited')->count();
        $resort = $atoll->islands->where('island_type', 'resort')->count();

        return response()->json([
            'id' => $atoll->id,
            'name' => $atoll->name,
            'code' => $atoll->code,
            'total_islands' => $atoll->islands->count(),
            'inhabited' => $inhabited,
            'uninhabited' => $uninhabited,
            'resort' => $resort,
        ]);
    }
}
