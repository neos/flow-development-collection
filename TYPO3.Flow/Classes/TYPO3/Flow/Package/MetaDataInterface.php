<?php
namespace TYPO3\Flow\Package;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Interface for Package MetaData information
 *
 */
interface MetaDataInterface
{
    const CONSTRAINT_TYPE_DEPENDS = 'depends';
    const CONSTRAINT_TYPE_CONFLICTS = 'conflicts';
    const CONSTRAINT_TYPE_SUGGESTS = 'suggests';

    const PARTY_TYPE_PERSON = 'person';
    const PARTY_TYPE_COMPANY = 'company';

    const CONSTRAINT_SCOPE_PACKAGE = 'package';
    const CONSTRAINT_SCOPE_SYSTEM = 'system';

    /**
     * @return string The package key
     */
    public function getPackageKey();

    /**
     * @return string The package version
     */
    public function getVersion();

    /**
     * @return string The package description
     */
    public function getDescription();

    /**
     * @return Array of string The package categories
     */
    public function getCategories();

    /**
     * @return array<MetaData\Party> The package parties
     */
    public function getParties();

    /**
     * @param string $constraintType Type of the constraints to get: CONSTRAINT_TYPE_*
     * @return array<MetaData\Constraint> Package constraints
     */
    public function getConstraintsByType($constraintType);

    /**
     * Get all constraints
     *
     * @return array <MetaData\Constraint> Package constraints
     */
    public function getConstraints();
}
