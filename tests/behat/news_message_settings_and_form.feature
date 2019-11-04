@ou @ou_vle @block @block_news
Feature: Usage of settings and checking the message_form in block
  In order to show news and events
  As a teacher
  I need to be able to create a block, post, create, modify and view messages

  Background:
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add the "News" block
    And the news block for course "C1" is in news and events mode
    And I turn editing mode off

  @javascript
  Scenario: Add a new message with default settings
    # Add a news item
    When I click on "Add a message" "link"
    Then I should see "Add a new message"
    And I should see "Image"
    And I should see "maximum number of files: 1"
    And I set the field "Title" to "News item 001"
    And I set the field "Text" to "This is the text message of News item 001"
    Then I press "Save changes"

  @javascript
  Scenario: Block setting and conditional display of elements on message type in the form
    When I click on "Add a message" "link"

    # Add another message with an image.
    And I set the field "Title" to "News item 002"
    And I set the field "Text" to "This is the text message of News item 002"
    And I set the field "Type" to "News item"
    And I upload "blocks/news/tests/fixtures/kitten1.jpg" file to "Image" filemanager
    And I should see "kitten1.jpg"
    Then I press "Save changes"

    # Modify existing message by changing the title and checking if the image is still there.
    When I follow "View all news and events"
    And I click on "Edit News item 002" "link"
    And I set the field "Title" to "Edited news item"
    And I should see "kitten1.jpg"
    Then I press "Save changes"

  @javascript
  Scenario: Post a news message with an oversized image.
    When I click on "Add a message" "link"

    # Add another message with an image.
    And I set the field "Title" to "News item 002"
    And I set the field "Text" to "This is the text message of News item 002"
    And I set the field "Type" to "News item"
    And I upload "blocks/news/tests/fixtures/oversize.jpg" file to "Image" filemanager
    And I press "Save changes"
    Then I should see "The message image must be less than 100KB"
    And I should see "The message image must be exactly 700x330 pixels"
