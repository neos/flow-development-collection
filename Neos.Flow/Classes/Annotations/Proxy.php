<?php

namespace Neos\Flow\Annotations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Neos\Flow\ObjectManagement\Exception\ProxyCompilerException;

/**
 * Controls proxy class generation behavior for a class.
 *
 * This annotation allows you to:
 * - Disable proxy building entirely (enabled=false) - useful for value objects, DTOs,
 *   or classes that should not use Dependency Injection or AOP
 * - Force generation of serialization code (forceSerializationCode=true) - rarely needed
 *   escape hatch for edge cases where automatic detection of entity relationships fails
 *
 * When proxy building is disabled (enabled=false), neither Dependency Injection nor AOP
 * can be used on the object. The class will be instantiated directly without any
 * framework enhancements.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Proxy
{
    /**
     * Whether proxy building is enabled for this class.
     *
     * When set to false, Flow will not generate a proxy class, meaning:
     * - No Dependency Injection (no Flow\Inject annotations)
     * - No Aspect-Oriented Programming (no AOP advices)
     * - No automatic serialization handling
     * - The class is instantiated directly without any framework enhancements
     *
     * This is useful for simple value objects, DTOs, or utility classes that don't need
     * framework features and where you want to avoid the minimal overhead of proxy classes.
     *
     * (Can be given as anonymous argument.)
     */
    public bool $enabled = true;

    /**
     * Force the generation of serialization code (__sleep/__wakeup methods) in the proxy class.
     *
     * Flow automatically detects when serialization code is needed (e.g., when a class has entity
     * properties, injected dependencies, or transient properties) and generates the appropriate
     * __sleep() and __wakeup() methods. These methods handle:
     * - Converting entity references to metadata (class name + persistence identifier)
     * - Removing injected and framework-internal properties before serialization
     * - Restoring entity references and re-injecting dependencies after deserialization
     *
     * This flag serves as an **escape hatch for rare edge cases** where automatic detection fails,
     * such as:
     * - Complex generic/template types that aren't fully parsed (e.g., ComplexType<Entity>)
     * - Deeply nested entity structures where type hints don't reveal the entity relationship
     * - Union or intersection types with entities that the reflection system cannot fully analyze
     * - Properties with dynamic types where documentation hints are non-standard
     *
     * IMPORTANT: You should rarely need this flag. Flow's automatic detection handles:
     * - Properties typed with Flow\Entity classes
     * - Properties with Flow\Inject annotations
     * - Properties with Flow\Transient annotations
     * - Classes with AOP advices
     * - Session-scoped objects
     *
     * If you find yourself needing this flag for standard entity properties, injected dependencies,
     * or other common cases, this indicates a bug in Flow's detection logic that should be reported
     * at https://github.com/neos/flow-development-collection/issues
     *
     * Note: Disabling serialization code (not possible via this flag) would break classes with
     * AOP, injections, or entity relationships. To completely opt out of proxy features, use
     * enabled=false instead.
     *
     * @see https://flowframework.readthedocs.io/ for more information on object serialization
     */
    public bool $forceSerializationCode = false;

    public function __construct(bool $enabled = true, bool $forceSerializationCode = false)
    {
        if ($enabled === false && $forceSerializationCode === true) {
            throw new ProxyCompilerException('Cannot disable a Proxy but forceSerializationCode at the same time.', 1756813222);
        }

        $this->enabled = $enabled;
        $this->forceSerializationCode = $forceSerializationCode;
    }
}
