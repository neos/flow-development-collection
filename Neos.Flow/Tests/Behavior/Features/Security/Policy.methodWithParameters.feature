Feature: Method policy enforcement
  In order to protect arbitrary methods from being called by certain users
  As a developer
  I need a way to specify access control policies

  Background:
    Given I have the following policies:
      """
      privilegeTargets:

        'Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilege':

          'Neos.Flow:Tests.ArbitraryController.arbitraryAction':
            matcher: 'method(Neos\Flow\Tests\Functional\Security\Fixtures\Controller\{parameters.controller}Controller->{parameters.action}Action())'
            parameters:
              'controller':
                className: 'Neos\Flow\Security\Authorization\Privilege\Parameter\StringPrivilegeParameter'
              'action':
                className: 'Neos\Flow\Security\Authorization\Privilege\Parameter\StringPrivilegeParameter'

      roles:
        'Neos.Flow:Everybody':
          privileges: []

        'Neos.Flow:Anonymous':
          privileges: []

        'Neos.Flow:AuthenticatedUser':
          privileges: []

        'Neos.Flow:Customer':
          privileges:
            -
              privilegeTarget: 'Neos.Flow:Tests.ArbitraryController.arbitraryAction'
              parameters:
                'controller': 'Restricted'
                'action': 'customer'
              permission: GRANT
            -
              privilegeTarget: 'Neos.Flow:Tests.ArbitraryController.arbitraryAction'
              parameters:
                'controller': 'Restricted'
                'action': 'admin'
              permission: GRANT

            #This should override the permission set in previous lines!
            -
              privilegeTarget: 'Neos.Flow:Tests.ArbitraryController.arbitraryAction'
              parameters:
                'controller': 'Restricted'
                'action': 'admin'
              permission: DENY

        'Neos.Flow:Administrator':
          privileges:
            -
              privilegeTarget: 'Neos.Flow:Tests.ArbitraryController.arbitraryAction'
              permission: GRANT
              parameters:
                'controller': 'Restricted'
                'action': 'customer'
            -
              privilegeTarget: 'Neos.Flow:Tests.ArbitraryController.arbitraryAction'
              permission: GRANT
              parameters:
                'controller': 'Restricted'
                'action': 'admin'
      """

  @Isolated
  Scenario: Public action is granted for everybody
    Given I am not authenticated
    Then I can call the method "publicAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: public action is granted for customer
    Given I am authenticated with role "Neos.Flow:Customer"
    Then I can call the method "publicAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: public action is granted for administrator
    Given I am authenticated with role "Neos.Flow:Administrator"
    Then I can call the method "publicAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

#  @Isolated
#  Scenario: customer action is denied for everybody
#    Given I am not authenticated
#    Then I can not call the method "customerAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: customer action is granted for customer
    Given I am authenticated with role "Neos.Flow:Customer"
    Then I can call the method "customerAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: customer action is granted for administrator
    Given I am authenticated with role "Neos.Flow:Administrator"
    Then I can call the method "customerAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

#  @Isolated
#  Scenario: admin action is denied for everybody
#    Given I am not authenticated
#    Then I can not call the method "adminAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: admin action is denied for customer (grant permission is overridden, due to a second equivalent privilege definition in the same role)
    Given I am authenticated with role "Neos.Flow:Customer"
    Then I can not call the method "adminAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: admin action is granted for administrator
    Given I am authenticated with role "Neos.Flow:Administrator"
    Then I can call the method "adminAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"
