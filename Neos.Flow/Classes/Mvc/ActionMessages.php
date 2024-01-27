<?php

declare(strict_types=1);

namespace Neos\Flow\Mvc;

final readonly class ActionMessages
{
    private function __construct(
        public ActionRequest $request,
        public ActionResponse $response
    ) {
    }

    public static function create(ActionRequest $request, ActionResponse $response)
    {
        return new self($request, $response);
    }
}
