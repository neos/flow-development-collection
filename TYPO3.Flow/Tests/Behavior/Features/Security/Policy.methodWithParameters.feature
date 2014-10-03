Feature: Method policy enforcement
  In order to protect arbitrary methods from being called by certain users
  As a developer
  I need a way to specify access control policies

  Background:
    Given I have the following policies:
      """
      privilegeTargets:

        'TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilege':

          'TYPO3.Flow:Tests.ArbitraryController.arbitraryAction':
            matcher: 'method(TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller\{parameters.controller}Controller->{parameters.action}Action())'
            parameters:
              'controller':
                className: 'TYPO3\Flow\Security\Authorization\Privilege\Parameter\StringPrivilegeParameter'
              'action':
                className: 'TYPO3\Flow\Security\Authorization\Privilege\Parameter\StringPrivilegeParameter'

      roles:
        'TYPO3.Flow:Everybody':
          privileges: []

        'TYPO3.Flow:Anonymous':
          privileges: []

        'TYPO3.Flow:AuthenticatedUser':
          privileges: []

        'TYPO3.Flow:Customer':
          privileges:
            -
              privilegeTarget: 'TYPO3.Flow:Tests.ArbitraryController.arbitraryAction'
              parameters:
                'controller': 'Restricted'
                'action': 'customer'
              permission: GRANT
            -
              privilegeTarget: 'TYPO3.Flow:Tests.ArbitraryController.arbitraryAction'
              parameters:
                'controller': 'Restricted'
                'action': 'admin'
              permission: DENY

        'TYPO3.Flow:Administrator':
          privileges:
            -
              privilegeTarget: 'TYPO3.Flow:Tests.ArbitraryController.arbitraryAction'
              permission: GRANT
              parameters:
                'controller': 'Restricted'
                'action': 'customer'
            -
              privilegeTarget: 'TYPO3.Flow:Tests.ArbitraryController.arbitraryAction'
              permission: GRANT
              parameters:
                'controller': 'Restricted'
                'action': 'admin'
      """

  @Isolated
  Scenario: Public action is granted for everybody
    Given I am not authenticated
    Then I can call the method "publicAction" of class "TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: public action is granted for customer
    Given I am authenticated with role "TYPO3.Flow:Customer"
    Then I can call the method "publicAction" of class "TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: public action is granted for administrator
    Given I am authenticated with role "TYPO3.Flow:Administrator"
    Then I can call the method "publicAction" of class "TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: customer action is denied for everybody
    Given I am not authenticated
    Then I can not call the method "customerAction" of class "TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: customer action is granted for customer
    Given I am authenticated with role "TYPO3.Flow:Customer"
    Then I can call the method "customerAction" of class "TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: customer action is granted for administrator
    Given I am authenticated with role "TYPO3.Flow:Administrator"
    Then I can call the method "customerAction" of class "TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: admin action is denied for everybody
    Given I am not authenticated
    Then I can not call the method "adminAction" of class "TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: admin action is denied for customer
    Given I am authenticated with role "TYPO3.Flow:Customer"
    Then I can not call the method "adminAction" of class "TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: admin action is granted for administrator
    Given I am authenticated with role "TYPO3.Flow:Administrator"
    Then I can call the method "adminAction" of class "TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"