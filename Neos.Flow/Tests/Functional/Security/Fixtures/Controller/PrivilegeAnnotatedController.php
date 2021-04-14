<?php
namespace Neos\Flow\Tests\Functional\Security\Fixtures\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;

/**
 * A controller for functional testing
 */
class PrivilegeAnnotatedController extends ActionController
{
    /**
     * This method gives GRANT permission to the role "Neos.Flow.AnnotatedRole"
     *
     * @Flow\Privilege(grantedRoles={"Neos.Flow:PrivilegeAnnotation.Role1"})
     */
    public function actionWithGrantedRolesAction()
    {
    }

    /**
     * @Flow\Privilege(id="Neos.Flow:Privilege.From.Annotation")
     */
    public function actionWithPrivilegeIdAndNoGrantedRoles()
    {
    }


    /**
     * @Flow\Privilege(id="Neos.Flow:Granted.Roles.Privilege", grantedRoles={"Neos.Flow:PrivilegeAnnotation.Role3"})
     */
    public function actionWithPrivilegeIdAndGrantedRoles()
    {
    }
}
