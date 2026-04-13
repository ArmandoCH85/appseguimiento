<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreGpsTrackRequest;
use App\Http\Resources\Api\GpsTrackListResource;
use App\Http\Resources\Api\GpsTrackResource;
use App\Jobs\StoreGpsTrackBatchJob;
use App\Models\Tenant\GpsTrack;
use App\Services\GpsTrackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class GpsTrackController extends Controller
{
    public function __construct(
        protected GpsTrackService $gpsTrackService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $query = GpsTrack::query()
            ->with('device.user')
            ->orderByDesc('time');

        if (request()->has('device_id')) {
            $query->where('device_id', request()->query('device_id'));
        }

        if (request()->has('from')) {
            $query->where('time', '>=', (int) request()->query('from'));
        }

        if (request()->has('to')) {
            $query->where('time', '<=', (int) request()->query('to'));
        }

        return GpsTrackListResource::collection($query->paginate(50));
    }

    public function show(GpsTrack $track): GpsTrackResource
    {
        $track->load('device.user');

        return new GpsTrackResource($track);
    }

    public function store(StoreGpsTrackRequest $request): JsonResponse
    {
        $data = $request->validated();

        $validated = $this->gpsTrackService->validateBatch($data['imei'], $data['points']);

        StoreGpsTrackBatchJob::dispatch(
            tenant()->getTenantKey(),
            $validated['validPoints'],
        );

        return response()->json([
            'message' => 'Puntos GPS recibidos correctamente.',
            'points_count' => count($validated['validPoints']),
        ], Response::HTTP_ACCEPTED);
    }
}
