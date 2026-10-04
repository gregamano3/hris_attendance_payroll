<?php

namespace App\Features\Health\CheckHealth;

use Illuminate\Http\JsonResponse;

class CheckHealthController
{
    public function __invoke(CheckHealthAction $action): JsonResponse
    {
        $checks = $action->handle();
        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}
