<?php

namespace Neos\Flow\Security\Authorization;

use Neos\Flow\Security\Authorization\Privilege\Parameter\PrivilegeParameterInterface;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeInterface;

/**
 *
 */
class PrivilegePermissionResult
{
    /**
     * @var int
     */
    protected $denies = 0;

    /**
     * @var int
     */
    protected $grants = 0;

    /**
     * @var int
     */
    protected $abstains = 0;

    /**
     * @var array<int,string>
     */
    protected $effectivePrivilegeIdentifiersWithPermission = [];

    /**
     * PrivilegePermissionResult constructor.
     */
    public function __construct(int $denies = 0, int $grants = 0, int $abstains = 0)
    {
        $this->denies = $denies;
        $this->grants = $grants;
        $this->abstains = $abstains;
    }

    /**
     * @param PrivilegeInterface $privilege
     */
    public function withPrivilege(?PrivilegeInterface $privilege = null): PrivilegePermissionResult
    {
        $newResult = clone $this;
        if ($privilege === null) {
            return $newResult;
        }

        if ($privilege->isGranted()) {
            $newResult->grants++;
        }

        if ($privilege->isDenied()) {
            $newResult->denies++;
        }

        if ($privilege->isAbstained()) {
            $newResult->abstains++;
        }

        $parameterStrings = array_map(fn (PrivilegeParameterInterface $parameter) => sprintf('%s: "%s"', $parameter->getName(), $parameter->getValue()), $privilege->getParameters());

        $privilegeName = $privilege->getPrivilegeTargetIdentifier() . ($parameterStrings !== [] ? ' (with parameters: ' . implode(', ', $parameterStrings) . ')' : '');
        $newResult->effectivePrivilegeIdentifiersWithPermission[] = sprintf('"%s": %s', $privilegeName, strtoupper($privilege->getPermission()));

        return $newResult;
    }

    public function getDenies(): int
    {
        return $this->denies;
    }

    public function getGrants(): int
    {
        return $this->grants;
    }

    public function getAbstains(): int
    {
        return $this->abstains;
    }

    /**
     * @return array<int,string>
     */
    public function getEffectivePrivilegeIdentifiersWithPermission(): array
    {
        return $this->effectivePrivilegeIdentifiersWithPermission;
    }
}
