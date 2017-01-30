@ou @ou_vle @block @block_news
Feature: Usage of settings and checking the message_form in block
  In order to show news and events
  As a teacher
  I need to be able to create a block, post, create, modify and view messages

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
    And I am using the OSEP theme
    And I log in as "admin" (in the OSEP theme)

  @javascript
  Scenario: Add a new message with default settings
    # Temporarily moved this code from Background because of things being turned off.
    Given the following "courses" exist:
      | fullname | shortname | category | format      |
      | Course 1 | C1        | 0        | oustudyplan |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And I am on site homepage
    And I follow "Course 1"
    And I turn editing mode on in the OSEP theme
    And I add the "News" block
    And I turn editing mode off in the OSEP theme

    # Add a news item
    When I click on "Add a message" "link"
    Then I should see "Add a new message"
    And I should not see "Type"
    # Temporarily removed Image check because image is turned off except in ousubject.
    And I should not see "Image"
    #And I should see "Image"
    #And I should see "maximum attachments: 1, overall limit: 50KB"
    And I set the field "Title" to "News item 001"
    And I set the field "Text" to "This is the text message of News item 001"
    Then I press "Save changes"

  @javascript
  Scenario: Block setting and conditional display of elements on message type in the form
    # Temporarily moved this code from Background, and changed to ousubject,
    # because of things being turned off.
    Given the following "courses" exist:
      | fullname | shortname | category | format    |
      | Course 1 | C1        | 0        | ousubject |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And I am on site homepage
    And I follow "Course 1"

    When I click on "View all" "link"

    # Add another message with an image.
    When I press "Add a new message"
    And I set the field "Title" to "News item 002"
    And I set the field "Text" to "This is the text message of News item 002"
    And I set the field "Type" to "News item"
    And I upload "blocks/news/tests/fixtures/kitten1.jpg" file to "Image" filemanager
    And I should see "kitten1.jpg"
    Then I press "Save changes"

    # Modify existing message by changing the title and checking if the image is still there.
    When I click on "Edit News item 002" "link"
    And I set the field "Title" to "Edited news item"
    And I should see "kitten1.jpg"
    Then I press "Save changes"
