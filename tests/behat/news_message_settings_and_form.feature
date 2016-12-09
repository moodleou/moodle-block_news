@ou @ou_vle @block @block_news
Feature: Usage of settings and checking the message_form in block
  In order to show news and events
  As a teacher
  I need to be able to create a block, post, create, modify and view messages

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | format    |
      | Course 1 | C1        | 0        | ousubject |
    And I am using the OSEP theme
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |

    Given I log in as "admin" (in the OSEP theme)

  @javascript
  Scenario: Add a new message with default settings
    And I am on site homepage
    And I follow "Course 1"

    # Add a news item
    When I click on "Add a message" "link"
    Then I should see "Add a new message"
    And I should not see "Type"
    And I should see "Image"
    And I should see "maximum attachments: 1, overall limit: 50KB"
    And I set the field "Title" to "News item 001"
    And I set the field "Text" to "This is the text message of News item 001"
    Then I press "Save changes"

  @javascript
  Scenario: Block setting and conditional display of elements on message type in the form
    Given I am on site homepage
    And I follow "Course 1"
    And I turn editing mode on in the OSEP theme
    When I open the "News and events" blocks action menu
    And I follow "Configure News and events block"
    And I set the following fields to these values:
      | Separate into events and news items | Yes |
    And I press "Save changes"
    Then I turn editing mode off in the OSEP theme
    When I click on "View all" "link"

    # Add a aonther message with an image
    When I click on "Add a message" "link"
    And I set the field "Title" to "News item 002"
    And I set the field "Text" to "This is the text message of News item 002"
    And I set the field "Type" to "News item"
    And I upload "blocks/news/tests/fixtures/testimage.png" file to "Image" filemanager
    And I should see "testimage.png"
    Then I press "Save changes"

    # Modify existing message by changing the tpe and checking if the image is still there
    When I click on "View all" "link"
    And I click on "Edit News item 002" "link"
    And I set the field "Title" to "Event 001"
    And I set the field "Type" to "Event"
    And I should see "testimage.png"
    Then I press "Save changes"
