<?php
namespace TYPO3\Flow\Object\Proxy;

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
 * Trait injected to proxy classes to repair injections and advices in doctrine proxies.
 *
 * FIXME: this can be removed again once Doctrine is fixed.
 * This is covered by functional tests in "Tests/Functional/Persistence/Doctrine/LazyLoadingTest.php". If those work without the code it should be safe to remove.
 */
trait DoctrineProxyFixingTrait
{
    /**
     * Creates code that builds the targetMethodsAndGroupedAdvices array if it does not exist. This happens when a Doctrine
     * lazy loading proxy for an object is created for some specific purpose, but filled afterwards "on the fly" if this object
     * is part of a wide range "findBy" query.
     *
     * @return void
     */
    public function Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies()
    {
        if (!isset($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices) || empty($this->Flow_Aop_Proxy_targetMethodsAndGroupedAdvices)) {
            $this->Flow_Aop_Proxy_buildMethodsAndAdvicesArray();
            if (is_callable('parent::Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies')) {
                parent::Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies();
            }
        }
    }

    /**
     * Creates code that reinjects dependencies if they do not exist. This is necessary because in certain circumstances
     * Doctrine loads a proxy in UnitOfWork->createEntity() without calling __wakeup and thus does not initialize DI.
     * This happens when a Doctrine lazy loading proxy for an object is created for some specific purpose, but filled
     * afterwards "on the fly" if this object is part of a wide range "findBy" query.
     *
     * @return void
     */
    public function Flow_Aop_Proxy_fixInjectedPropertiesForDoctrineProxies()
    {
        if (!$this instanceof \Doctrine\ORM\Proxy\Proxy || isset($this->Flow_Proxy_injectProperties_fixInjectedPropertiesForDoctrineProxies)) {
            return;
        }
        $this->Flow_Proxy_injectProperties_fixInjectedPropertiesForDoctrineProxies = true;
        if (is_callable([$this, 'Flow_Proxy_injectProperties'])) {
            $this->Flow_Proxy_injectProperties();
        }
    }
}
