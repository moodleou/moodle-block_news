@ou @ou_vle @block @block_news
Feature: Basic usage of news block
  In order to show news
  As a teacher
  I need to be able to create a block, post, and view messages

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname |
      | teacher1 | ANNE      |
      | student1 | BOB       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: Basic usage of news block
    # Add news block.
    When I am on the "Course 1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add the "News" block
    Then I should see "(new News block)"

    # Post a message.
    When I click on "Add" "link" in the "(new News block)" "block"
    And I set the following fields to these values:
      | Title | My first message  |
      | Text  | Some message text |
    And I press "Save changes"
    And I wait until the page is ready
    Then I should see "My first message"
    And I should see "Some message text"

    # Check student can view it.
    When I am on the "Course 1" "Course" page logged in as "student1"
    Then I should see "My first message"

  Scenario: Test post and view multiple messages, summaries
    # Add block.
    Given I am on the "Course 1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add the "News" block

    # Post 2 messages.
    And I click on "Add" "link" in the "(new News block)" "block"
    And I set the following fields to these values:
      | Title | Short message |
      | Text  | Message1 text |
    And I press "Save changes"
    And I wait "1" seconds
    And I click on "Add" "link" in the "(new News block)" "block"
    And I set the following fields to these values:
      | Title | Medium message                                                                                                                        |
      | Text  | Message2 text is longer and contains over 100 characters but will get cut off on the front page at some point before the word JACKPOT |
    And I press "Save changes"
    And I wait "1" seconds

    # Check the contents and the summarising.
    Then I should see "Message1"
    And I should see "Message2"
    And I should not see "JACKPOT"

    # Post another message (default is 2 visible).
    When I click on "Add" "link" in the "(new News block)" "block"
    And I set the following fields to these values:
      | Title | Third message |
      | Text  | Message3 text |
    And I press "Save changes"
    And I wait "1" seconds
    Then I should see "Message3"
    And I should not see "Message1"

    # Click on the view link (it has the message title in, accesshide).
    When I follow "Third message"
    Then I should see "Message3 text"
    And I should not see "Next (newer) message"

    # Click to previous message, check it has entire text.
    When I follow "Previous (older) message"
    Then I should see "Medium message"
    And I should see "JACKPOT"
    And I should see "Next (newer) message"

    # Click to previous message (first in list).
    When I follow "Previous (older) message"
    Then I should see "Short message"
    And I should not see "Previous (older) message"

    # Click to all messages page using breadcrumb.
    When I follow "(new News block)"
    Then I should see "All messages"
    And I should see "Third message"
    And I should see "Short message"

    # Delete the earliest message from this page. (Try cancel first.)
    When I click on "Delete Short message" "link"
    Then I should see "Confirm deletion"
    And I should see "Short message"
    When I press "Cancel"
    Then I should see "All messages"
    When I click on "Delete Short message" "link"
    And I press "Continue"
    Then I should see "All messages"
    And I should see "Medium message"
    And I should not see "Short message"

    # Hide the 'Third message'.
    When I click on "hide" "link" in the ".block_news_message" "css_element"
    Then "show" "link" should exist in the ".block_news_message" "css_element"

    # Add another message
    When I press "Add a new message"
    And I set the following fields to these values:
      | Title | Fourth message |
      | Text  | Message4 text  |
    And I press "Save changes"
    Then I should see "All messages"
    And I should see "Fourth message"

    # Go back in as a student and check the hide part worked.
    When I am on the "Course 1" "Course" page logged in as "student1"
    Then I should see "Fourth message"
    And I should not see "Third message"
    And I should see "Medium message"

  @javascript
  Scenario: Test block options
    # Add block.
    Given I am on the "Course 1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add the "News" block

    # Go to settings page. Rename block and set other settings.
    When I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the following fields to these values:
      | Block title   | Breaking!           |
      | Show messages | Latest message only |
      | Summary       | Short               |
      | Hide titles   | Yes                 |
    And I press "Save changes"
    And I wait until the page is ready
    Then I should see "Breaking!"

    # Add 2 messages.
    When I click on "Add" "link" in the "Breaking!" "block"
    And I set the following fields to these values:
      | Title | Message 1      |
      | Text  | Message 1 text |
    And I press "Save changes"
    And I wait until the page is ready
    Then I should see "Message 1"

    And I wait "2" seconds
    When I click on "Add" "link" in the "Breaking!" "block"
    And I set the following fields to these values:
      | Title | Message 2                                                         |
      | Text  | Message 2 text is longer than 40 characters. Hidden text: JACKPOT |
    And I press "Save changes"
    And I wait until the page is ready
    Then I should see "Message 2"

    # Check: only showing latest message.
    And I should not see "Message 1"

    # Check: Short (40 character) summaries.
    And I should not see "JACKPOT"

  @javascript
  Scenario: Test author name option
    # Add block.
    Given I am on the "Course 1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add the "News" block

    # Normal message.
    When I click on "Add" "link" in the "(new News block)" "block"
    And I set the following fields to these values:
      | Title | Message 1      |
      | Text  | Message 1 text |
    And I press "Save changes"
    And I wait until the page is ready
    Then I should see "ANNE" in the "(new News block)" "block"

    # In view all.
    Given "View all news and events" "link" should not exist
    When I click on "View all" "link" in the "(new News block)" "block"
    Then I should see "ANNE" in the ".block_news_message" "css_element"

    # Turn off author display.
    When I click on "Edit" "link"
    And I set the field "Hide author" to "Yes"
    And I press "Save changes"
    And I wait until the page is ready
    Then I should not see "ANNE" in the ".block_news_message" "css_element"

    # On main page either.
    When I am on "Course 1" course homepage
    Then I should not see "ANNE" in the "(new News block)" "block"
