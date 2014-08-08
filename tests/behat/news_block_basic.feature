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
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add the "News" block
    Then I should see "(new News block)"

    # Post a message.
    When I click on "Add" "link" in the "(new News block)" "block"
    And I set the following fields to these values:
      | Title | My first message  |
      | Text  | Some message text |
    And I press "Save changes"
    Then I should see "My first message"
    And I should see "Some message text"

    # Check student can view it.
    When I log out
    And I log in as "student1"
    And I follow "Course 1"
    Then I should see "My first message"

  # Note: Scenario does not require JS, but one step randomly fails in non-JS mode.
  # Maybe possible to remove @javascript in future Moodle version if they fix the
  # bug that causes this (whatever it is).
  @javascript
  Scenario: Test post and view multiple messages, summaries
    # Add block.
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add the "News" block

    # Post 2 messages.
    And I click on "Add" "link" in the "(new News block)" "block"
    And I set the following fields to these values:
      | Title | Short message |
      | Text  | Message1 text |
    And I press "Save changes"
    And I click on "Add" "link" in the "(new News block)" "block"
    And I set the following fields to these values:
      | Title | Medium message                                                                                                                        |
      | Text  | Message2 text is longer and contains over 100 characters but will get cut off on the front page at some point before the word JACKPOT |
    And I press "Save changes"

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
    When I click on "delete" "link" in the "//div[contains(@class, 'block_news_message')][3]" "xpath_element"
    Then I should see "Confirm deletion"
    And I should see "Short message"
    When I press "Cancel"
    Then I should see "All messages"
    When I click on "delete" "link" in the "//div[contains(@class, 'block_news_message')][3]" "xpath_element"
    And I press "Continue"
    Then I should see "All messages"
    And I should see "Medium message"
    And I should not see "Short message"

    # Hide the 'Third message'.
    When I click on "hide" "link" in the "//div[contains(@class, 'block_news_message')][1]" "xpath_element"
    Then "show" "link" should exist in the "//div[contains(@class, 'block_news_message')][1]" "xpath_element"

    # Add another message
    When I press "Add a new message"
    And I set the following fields to these values:
      | Title | Fourth message |
      | Text  | Message4 text  |
    And I press "Save changes"
    Then I should see "All messages"
    And I should see "Fourth message"

    # Go back in as a student and check the hide part worked.
    When I log out
    And I log in as "student1"
    And I follow "Course 1"
    Then I should see "Fourth message"
    And I should not see "Third message"
    And I should see "Medium message"

  Scenario: Test block options
    # Add block.
    Given I log in as "teacher1"
    And I follow "Course 1"
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
    Then I should see "Breaking!"

    # Add 2 messages.
    When I click on "Add" "link" in the "Breaking!" "block"
    And I set the following fields to these values:
      | Title | Message 1      |
      | Text  | Message 1 text |
    And I press "Save changes"
    Then I should see "Message 1"

    When I click on "Add" "link" in the "Breaking!" "block"
    And I set the following fields to these values:
      | Title | Message 2                                                         |
      | Text  | Message 2 text is longer than 40 characters. Hidden text: JACKPOT |
    And I press "Save changes"
    Then I should see "Message 2"

    # Check: only showing latest message.
    And I should not see "Message 1"

    # Check: Short (40 character) summaries.
    And I should not see "JACKPOT"

  Scenario: Test author name option
    # Add block.
    Given I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add the "News" block

    # Normal message.
    When I click on "Add" "link" in the "(new News block)" "block"
    And I set the following fields to these values:
      | Title | Message 1      |
      | Text  | Message 1 text |
    And I press "Save changes"
    Then I should see "ANNE" in the "(new News block)" "block"

    # In view all.
    When I click on "View all" "link" in the "(new News block)" "block"
    Then I should see "ANNE" in the ".block_news_message" "css_element"

    # Turn off author display.
    When I click on "edit" "link"
    And I set the field "Hide author" to "Yes"
    And I press "Save changes"
    Then I should not see "ANNE" in the ".block_news_message" "css_element"

    # On main page either.
    When I follow "C1"
    Then I should not see "ANNE" in the "(new News block)" "block"
