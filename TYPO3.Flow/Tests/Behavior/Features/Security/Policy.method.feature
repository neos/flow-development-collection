Feature: Method policy enforcement
  In order to protect arbitrary methods from being called by certain users
  As a developer
  I need a way to specify access control policies

  Background:
    Given I have the following policies:
      """
      resources:
        methods:
          TYPO3_Flow_Tests_Functional_Security_Fixtures_RestrictedController_customerAction: 'method(TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController->customerAction())'
          TYPO3_Flow_Tests_Functional_Security_Fixtures_RestrictedController_adminAction: 'method(TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller\RestrictedController->adminAction())'
        entities: []

      roles:
        'TYPO3.Flow:Everybody': []
        'TYPO3.Flow:Customer': []
        'TYPO3.Flow:Administrator': []

      acls:

        Customer:
          methods:
            TYPO3_Flow_Tests_Functional_Security_Fixtures_RestrictedController_customerAction: GRANT
            TYPO3_Flow_Tests_Functional_Security_Fixtures_RestrictedController_adminAction:    DENY

        Administrator:
          methods:
            TYPO3_Flow_Tests_Functional_Security_Fixtures_RestrictedController_customerAction: GRANT
            TYPO3_Flow_Tests_Functional_Security_Fixtures_RestrictedController_adminAction:    GRANT
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