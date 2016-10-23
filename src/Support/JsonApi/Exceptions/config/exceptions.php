<?php

use Support\Validation\ValidationException;
use Support\JsonApi\Exceptions\Renderers\DefaultRenderer;
use Support\JsonApi\Exceptions\Renderers\NotFoundRenderer;
use Support\JsonApi\Exceptions\Renderers\NoContentRenderer;
use Support\JsonApi\Exceptions\Renderers\ValidationErrorRenderer;
use Illuminate\Database\Eloquent\ModelNotFoundException;

return [
    'default' => DefaultRenderer::class,
    'list' => [
        ModelNotFoundException::class => NotFoundRenderer::class,
        ValidationException::class => ValidationErrorRenderer::class,
    ]
];