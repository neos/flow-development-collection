<?php
namespace Neos\FluidAdaptor\Tests\Unit\View\Fixtures;

use Neos\FluidAdaptor\View\TemplatePaths;

/**
 *
 */
class TemplatePathsTestAccessor
{
    public function __construct(public TemplatePaths $templatePaths)
    {
    }

    public function expandGenericPathPattern($pattern, array $patternReplacementVariables, $bubbleControllerAndSubpackage, $formatIsOptional): array
    {
        $accessor = function ($pattern, array $patternReplacementVariables, $bubbleControllerAndSubpackage, $formatIsOptional): array {
            return $this->expandGenericPathPattern($pattern, $patternReplacementVariables, $bubbleControllerAndSubpackage, $formatIsOptional);
        };

        $accessor = $accessor->bindTo($this->templatePaths, TemplatePaths::class);

        return $accessor($pattern, $patternReplacementVariables, $bubbleControllerAndSubpackage, $formatIsOptional);
    }
}
