<?php
declare(strict_types=1);

namespace Neos\Http\Factories;

use Psr\Http\Message\UploadedFileFactoryInterface;

/**
 *
 */
class UploadedFileFactory implements UploadedFileFactoryInterface
{
    use UploadedFileFactoryTrait;
}
