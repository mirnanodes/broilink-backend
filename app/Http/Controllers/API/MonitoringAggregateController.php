<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AggregateRequest;
use App\Services\MonitoringAggregateService;
use Illuminate\Http\JsonResponse;

/**
 * Controller for aggregating IoT monitoring data (environment sensors)
 */
class MonitoringAggregateController extends Controller
{
    public function __construct(
        private readonly MonitoringAggregateService $service
    ) {
    }

    /**
     * Get aggregated monitoring data
     *
     * @param AggregateRequest $request
     * @return JsonResponse
     */
    public function __invoke(AggregateRequest $request): JsonResponse
    {
        $dto = $request->dto();

        $data = $this->service->aggregate(
            farmId: $dto->farmId,
            date: $dto->date,
            range: $dto->range
        );

        return response()->json($data, 200);
    }
}
