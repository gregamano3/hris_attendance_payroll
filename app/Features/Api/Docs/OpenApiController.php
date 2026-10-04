<?php

namespace App\Features\Api\Docs;

use Illuminate\Http\Response;

/**
 * Serves the hand-maintained OpenAPI 3.1 description of the v1 API.
 */
class OpenApiController
{
    public function __invoke(): Response
    {
        abort_unless(config('hris.api.enabled'), 404);

        $yaml = str_replace('{{APP_URL}}', rtrim((string) config('app.url'), '/'), (string) file_get_contents(__DIR__.'/openapi.yaml'));

        return response($yaml, 200, ['Content-Type' => 'application/yaml']);
    }
}
