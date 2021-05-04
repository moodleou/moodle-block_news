@ou @ou_vle @block @block_news @app @javascript
Feature: Test News area in mobile
  In order to use the News area on mobile
  As a user
  I need the element on the page display correctly.

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username |
      | student1 |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |

  Scenario: Test News area don't appear on mobile when did nothing
    When I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Course overview" in the app
    Then I should not see "News"

  Scenario: News area display on mobile with items
    # Log in as admin to create News block.
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add the "News" block
    And the following news messages exist on course "C1":
      | title  | message    | messagetype | messagedate |
      | news 1 | message 1  | news        | 1552788000  |
      | news 2 | message 2  | news        | 1552874400  |
      | news 3 | message 3  | news        | 1552960800  |
    When I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Course overview" in the app
    And I press "arrow forward" in the app
    Then I should see "News"
    # News should display newest first by messagedate
    When I press "News" in the app
    Then "news 3" "text" should appear before "news 2" "text"
    And "news 2" "text" should appear before "news 1" "text"
    And "message 3" "text" should appear before "message 2" "text"
    And "message 2" "text" should appear before "message 1" "text"
    And "19 Mar 2019" "text" should appear before "18 Mar 2019" "text"
    And "18 Mar 2019" "text" should appear before "17 Mar 2019" "text"

    # Open in browser.
    And I press the page menu button in the app
    And I press "Open in browser" in the app
    And I switch to the browser tab opened by the app
    And I log in as "student1"
    Then I should see "19 Mar 2019"
    And I should see "news 3"
    And I should see "message 3"
    And I should see "18 Mar 2019"
    And I should see "news 2"
    And I should see "message 2"
    And I should see "17 Mar 2019"
    And I should see "news 1"
    And I should see "message 1"

  Scenario: News area display on mobile with no items
    # Log in as admin to create News block.
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add the "News" block
    When I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Course overview" in the app
    And I press "arrow forward" in the app
    Then I should see "News"
    When I press "News" in the app
    Then I should see "No news messages have been posted to this website."

  Scenario: News area display on mobile with many items
    # Log in as admin to create News block.
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add the "News" block
    And the following news messages exist on course "C1":
      | title   | message         | messagetype | messagedate |
      | news 1  | news 1 message  | news        | 1487952035  |
      | news 2  | news 2 message  | news        | 1487952036  |
      | news 3  | news 3 message  | news        | 1487952037  |
      | news 4  | news 4 message  | news        | 1487952038  |
      | news 5  | news 5 message  | news        | 1487952039  |
      | news 6  | news 6 message  | news        | 1487952040  |
      | news 7  | news 7 message  | news        | 1487952041  |
      | news 8  | news 8 message  | news        | 1487952042  |
      | news 9  | news 9 message  | news        | 1487952043  |
      | news 10 | news 10 message | news        | 1487952044  |
      | news 11 | news 11 message | news        | 1487952045  |
    When I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Course overview" in the app
    And I press "arrow forward" in the app
    Then I should see "News"
    When I press "News" in the app
    Then I should see "news 11 message"
    And I should not see "news 1 message"
    # Scroll down to bottom of the screen.
    When I trigger the news block infinite scroll "block_news_infinite_load_messages"
    Then I should see "news 1 message"
