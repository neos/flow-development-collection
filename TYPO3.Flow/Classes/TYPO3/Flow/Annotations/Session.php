<?php
namespace TYPO3\Flow\Annotations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Used to control the behavior of session handling when the annotated
 * method is called.
 *
 * @Annotation
 * @Target("METHOD")
 */
final class Session
{
    /**
     * Whether the annotated method triggers the start of a session.
     * @var boolean
     */
    public $autoStart = false;
}
