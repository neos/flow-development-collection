@flowEntities
Feature: Arbitrary file upload protection
  During file upload, if any exception occurs, be it security or constraint validation, I expect the file to be neither
    - persisted in the resource repository
    - stored in the resource storage
    - published to the publication target

  Background:
    Given I have the following policies:
      """yaml
      privilegeTargets:

        'Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilege':

          'Neos.Flow:Tests.ResourceUploadController.unrestrictedUploadAction':
            matcher: 'method(Neos\Flow\Tests\Functional\Security\Fixtures\Controller\ResourceUploadController->unrestrictedUploadAction())'

          'Neos.Flow:Tests.ResourceUploadController.arbitraryFailingUploadAction':
            matcher: 'method(Neos\Flow\Tests\Functional\Security\Fixtures\Controller\ResourceUploadController->arbitraryFailingUploadAction())'

          'Neos.Flow:Tests.ResourceUploadController.restrictedUploadAction':
            matcher: 'method(Neos\Flow\Tests\Functional\Security\Fixtures\Controller\ResourceUploadController->restrictedUploadAction())'

          'Neos.Flow:Tests.ResourceUploadController.constrainedUploadAction':
            matcher: 'method(Neos\Flow\Tests\Functional\Security\Fixtures\Controller\ResourceUploadController->constrainedUploadAction())'

          'Neos.Flow:Tests.ResourceUploadController.restrictedValidationConstrainedUpload':
            matcher: 'method(Neos\Flow\Tests\Functional\Security\Fixtures\Controller\ResourceUploadController->restrictedValidationConstrainedUploadAction())'

      roles:
        'Neos.Flow:Everybody':
          privileges:
            -
              privilegeTarget: 'Neos.Flow:Tests.ResourceUploadController.unrestrictedUploadAction'
              permission: GRANT
            -
              privilegeTarget: 'Neos.Flow:Tests.ResourceUploadController.arbitraryFailingUploadAction'
              permission: GRANT
            -
              privilegeTarget: 'Neos.Flow:Tests.ResourceUploadController.constrainedUploadAction'
              permission: GRANT

        'Neos.Flow:Anonymous':
          privileges: []
      """

  @Isolated @fixtures
  Scenario: Countercheck: unrestricted upload works if exposed
    Given I am not authenticated
    And I upload fixture resource "bat.jpg" using action "unrestrictedUpload" of controller "ResourceUpload" with additional parameter
    Then I expect this resource to be persisted
    Then I expect this resource to be stored
    Then I expect this resource to be published

  @Isolated @fixtures
  Scenario: missing parameter prevent persistence, storage and publication
    Given I am not authenticated
    And I upload fixture resource "bat.jpg" using action "unrestrictedUpload" of controller "ResourceUpload"
    Then I expect this resource not to be persisted
    Then I expect this resource not to be stored
    Then I expect this resource not to be published

  @Isolated @fixtures
  Scenario: arbitrary exceptions prevent persistence, storage and publication
    Given I am not authenticated
    And I upload fixture resource "bat.jpg" using action "arbitraryFailingUpload" of controller "ResourceUpload" with additional parameter
    Then I expect this resource not to be persisted
    Then I expect this resource not to be stored
    Then I expect this resource not to be published

  @Isolated @fixtures
  Scenario: arbitrary exceptions and missing parameter prevent persistence, storage and publication
    Given I am not authenticated
    And I upload fixture resource "bat.jpg" using action "arbitraryFailingUpload" of controller "ResourceUpload"
    Then I expect this resource not to be persisted
    Then I expect this resource not to be stored
    Then I expect this resource not to be published

  @Isolated @fixtures
  Scenario: access denial prevents persistence, storage and publication
    Given I am not authenticated
    And I upload fixture resource "bat.jpg" using action "restrictedUpload" of controller "ResourceUpload" with additional parameter
    Then I expect this resource not to be persisted
    Then I expect this resource not to be stored
    Then I expect this resource not to be published

  @Isolated @fixtures
  Scenario: access denial and missing parameter prevents persistence, storage and publication
    Given I am not authenticated
    And I upload fixture resource "bat.jpg" using action "restrictedUpload" of controller "ResourceUpload"
    Then I expect this resource not to be persisted
    Then I expect this resource not to be stored
    Then I expect this resource not to be published

  @Isolated @fixtures
  Scenario: validation exceptions prevent persistence, storage and publication
    Given I am not authenticated
    And I upload fixture resource "bat.jpg" using action "constrainedUpload" of controller "ResourceUpload" with additional parameter
    Then I expect this resource not to be persisted
    Then I expect this resource not to be stored
    Then I expect this resource not to be published

  @Isolated @fixtures
  Scenario: validation exceptions and missing parameter prevent persistence, storage and publication
    Given I am not authenticated
    And I upload fixture resource "bat.jpg" using action "constrainedUpload" of controller "ResourceUpload"
    Then I expect this resource not to be persisted
    Then I expect this resource not to be stored
    Then I expect this resource not to be published

  @Isolated @fixtures
  Scenario: annotated validation exceptions do not by themselves prevent persistence, storage and publication because we want to keep the files for correction
    Given I am not authenticated
    And I upload fixture resource "bat.jpg" using action "validationConstrainedUpload" of controller "ResourceUpload" with additional parameter
    Then I expect this resource to be persisted
    Then I expect this resource to be stored
    Then I expect this resource to be published

  @Isolated @fixtures
  Scenario: annotated validation exceptions and missing parameter prevent persistence, storage and publication
    Given I am not authenticated
    And I upload fixture resource "bat.jpg" using action "validationConstrainedUpload" of controller "ResourceUpload"
    Then I expect this resource not to be persisted
    Then I expect this resource not to be stored
    Then I expect this resource not to be published

  @Isolated @fixtures
  Scenario: access denial prevents persistence, storage and publication while also validation fails
    Given I am not authenticated
    And I upload fixture resource "bat.jpg" using action "restrictedValidationConstrainedUpload" of controller "ResourceUpload" with additional parameter
    Then I expect this resource not to be persisted
    Then I expect this resource not to be stored
    Then I expect this resource not to be published

  @Isolated @fixtures
  Scenario: access denial prevents persistence, storage and publication while also validation fails and a parameter is missing
    Given I am not authenticated
    And I upload fixture resource "bat.jpg" using action "restrictedValidationConstrainedUpload" of controller "ResourceUpload"
    Then I expect this resource not to be persisted
    Then I expect this resource not to be stored
    Then I expect this resource not to be published
