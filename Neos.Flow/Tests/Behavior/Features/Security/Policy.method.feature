Feature: Method policy enforcement
  In order to protect arbitrary methods from being called by certain users
  As a developer
  I need a way to specify access control policies

  Background:
    Given I have the following policies:
      """
      privilegeTargets:

        'Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilege':

          'Neos.Flow:Tests.RestrictedController.customerAction':
            matcher: 'method(Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController->customerAction())'

          'Neos.Flow:Tests.RestrictedController.adminAction':
            matcher: 'method(Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController->adminAction())'

          'Neos.Flow:Tests.RestrictedController.argumentsAction':
            matcher: 'method(Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController->argumentsAction(argument1 == current.testContext.nameOfTheWeek))'

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
              privilegeTarget: 'Neos.Flow:Tests.RestrictedController.customerAction'
              permission: GRANT
            -
              privilegeTarget: 'Neos.Flow:Tests.RestrictedController.adminAction'
              permission: DENY

        'Neos.Flow:Administrator':
          privileges:
            -
              privilegeTarget: 'Neos.Flow:Tests.RestrictedController.customerAction'
              permission: DENY

            #This should override the permission set in previous lines!
            -
              privilegeTarget: 'Neos.Flow:Tests.RestrictedController.customerAction'
              permission: GRANT

            -
              privilegeTarget: 'Neos.Flow:Tests.RestrictedController.adminAction'
              permission: GRANT
            -
              privilegeTarget: 'Neos.Flow:Tests.RestrictedController.argumentsAction'
              permission: GRANT
      """

  @Isolated
  Scenario: public action is granted for everybody
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

  @Isolated
  Scenario: customer action is denied for everybody
    Given I am not authenticated
    Then I can not call the method "customerAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: customer action is granted for customer
    Given I am authenticated with role "Neos.Flow:Customer"
    Then I can call the method "customerAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated @fixtures
  Scenario: customer action is granted for administrator (deny permission is overridden, due to a second equivalent privilege definition in the same role)
    Given I am authenticated with role "Neos.Flow:Administrator"
    Then I can call the method "customerAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: admin action is denied for everybody
    Given I am not authenticated
    Then I can not call the method "adminAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: admin action is denied for customer
    Given I am authenticated with role "Neos.Flow:Customer"
    Then I can not call the method "adminAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: admin action is granted for administrator
    Given I am authenticated with role "Neos.Flow:Administrator"
    Then I can call the method "adminAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController"

  @Isolated
  Scenario: arguments action with specified arguments is denied for everybody
    Given I am not authenticated
    Then I can not call the method "argumentsAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController" with arguments "Robbie"

  @Isolated
  Scenario: arguments action with different arguments is granted for everybody
    Given I am not authenticated
    Then I can call the method "argumentsAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController" with arguments "NotRobbie"

  @Isolated
  Scenario: arguments action with specified arguments is denied for customer
    Given I am authenticated with role "Neos.Flow:Customer"
    Then I can not call the method "argumentsAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController" with arguments "Robbie"

  @Isolated
  Scenario: arguments action with specified arguments is granted for administrator
    Given I am authenticated with role "Neos.Flow:Administrator"
    Then I can call the method "argumentsAction" of class "Neos\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController" with arguments "Robbie"
