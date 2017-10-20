@ou @ou_vle @block @block_news @javascript
Feature: Backup/Restore news and events block
  In order to Rollforward news events on a page
  As an author
  I need to create news and event messages backup and then restore them

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | timezone      |
      | ukuser   | Europe/London |
    And the following "course enrolments" exist:
      | user   | course | role    |
      | ukuser | C1     | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add the "News" block
    And the news block for course "C1" is in news and events mode
    And I reload the page

  Scenario: Backup and then restore Course with news and events message types
    Given the following news messages exist on course "C1":
      | title  | message | messagetype | messagedate | eventstart | eventlocation | messagerepeat |
      | news1  | news1   | 1           | 1483228800  |            |               | 1             |
      | news2  | news2   | 1           | 1483228801  |            |               | 1             |
      | news3  | news3   | 1           | 1483228802  |            |               | 1             |
      | event1 | event1  | 2           | 1483228800  | 2145924000 | Ev1 locator   | 1             |
      | event2 | event2  | 2           | 1483228801  | 2145924001 | Ev2 locator   | 1             |
      | event3 | event3  | 2           | 1483228802  | 2145924002 | Ev3 locator   | 1             |
    When I reload the page
    Then I should see "news2" in the "News and events (new)" "block"
    And I should see "news3" in the "News and events (new)" "block"
    And I should not see "news1" in the "News and events (new)" "block"
    And I should see "event1" in the "News and events (new)" "block"
    And I should see "event2" in the "News and events (new)" "block"
    And I should not see "event3" in the "News and events (new)" "block"

    # Without reload, Backup step fails
    And I reload the page
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name       | Course 2 |
      | Schema | Course short name | C2       |
    And I am on "Course 2" course homepage
    Then I should see "news2" in the "News and events (new)" "block"
    And I should see "news3" in the "News and events (new)" "block"
    And I should not see "news1" in the "News and events (new)" "block"
    And I should see "event1" in the "News and events (new)" "block"
    And I should see "Ev1 locator" in the "News and events (new)" "block"
    And I should see "event2" in the "News and events (new)" "block"
    And I should see "Ev2 locator" in the "News and events (new)" "block"
    And I should not see "event3" in the "News and events (new)" "block"

    When I follow "View all news and events"
    Then I should see "News and events: All messages"
    And I should see "News"
    And I should see "Upcoming events"
    And I should see "Past events"
    And I should see "news3"
    And I should see "news2"
    And I should see "news1"
    And I should see "event1"
    And I should see "Ev1 locator"
    And I should see "event2"
    And I should see "Ev2 locator"
    And I should see "event3"
    And I should see "Ev3 locator"
