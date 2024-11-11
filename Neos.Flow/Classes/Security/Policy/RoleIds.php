<?php

declare(strict_types=1);
namespace Neos\Flow\Security\Policy;

/**
 * @implements \IteratorAggregate<RoleId>
 */
final readonly class RoleIds implements \IteratorAggregate, \Countable
{
    /**
     * array<RoleId>
     */
    private array $roleIds;

    /**
     * @param array<RoleId> $roleIds
     */
    private function __construct(
        RoleId ...$roleIds
    ) {
        $this->roleIds = $roleIds;
    }

    public static function forAnonymousUser(): self
    {
        return self::fromArray([RoleId::everybody(), RoleId::anonymous()]);
    }

    /**
     * @param array<RoleId|string> $roleIds
     */
    public static function fromArray(array $roleIds): self
    {
        $processedIds = [];
        foreach ($roleIds as $roleId) {
            if (is_string($roleId)) {
                $roleId = RoleId::fromString($roleId);
            } elseif (!$roleId instanceof RoleId) {
                throw new \InvalidArgumentException(sprintf('Expected string or instance of %s, got: %s', RoleId::class, get_debug_type($roleId)), 1731338164);
            }
            $processedIds[] = $roleId;
        }
        return new self(...$processedIds);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->roleIds;
    }

    public function count(): int
    {
        return count($this->roleIds);
    }

    /**
     * @template T
     * @param callable(RoleId): T $callback
     * @return array<T>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->roleIds);
    }
}
