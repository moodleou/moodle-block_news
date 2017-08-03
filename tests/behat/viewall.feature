@ou @ou_vle @block @block_news
Feature: News and Events view all page
  In order to See historic news and events
  As a Student
  I want Have a page which displays all news and events posted on the course

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
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add the "News" block
    And the news block for course "C1" is in news and events mode
    And I reload the page

  Scenario: Display different message types in different orders
    Given the following news messages exist on course "C1":
      | title      | message    | messagetype | messagedate | eventstart |
      | news 1     | news 1     | news        | 1487952035  |            |
      | news 2     | news 2     | news        | 1487952036  |            |
      | news 3     | news 3     | news        | 1487952037  |            |
      | upcoming 1 | upcoming 1 | event       | 1487952037  | 2145916800 |
      | upcoming 2 | upcoming 2 | event       | 1487952037  | 2146003200 |
      | upcoming 3 | upcoming 3 | event       | 1487952037  | 2146089600 |
      | past 1     | past 1     | event       | 1487952037  | 946857600  |
      | past 2     | past 2     | event       | 1487952037  | 946771200  |
      | past 3     | past 3     | event       | 1487952037  | 946684800  |
    And I click on "View all news and events" "link" in the "News and events" "block"
    Then I should see "News"
    And I should see "Upcoming events"
    And I should see "Past events"
    # News should display newest first by messagedate
    And "news 3" "text" should appear before "news 2" "text"
    And "news 2" "text" should appear before "news 1" "text"
    # Upcoming events should display soonest first by eventstart
    And "upcoming 1" "text" should appear before "upcoming 2" "text"
    And "upcoming 2" "text" should appear before "upcoming 3" "text"
    # Past events should display most recent first by eventstart
    And "past 1" "text" should appear before "past 2" "text"
    And "past 2" "text" should appear before "past 3" "text"

  Scenario: Display paged messages
    Given the following news messages exist on course "C1":
      | title      | message    | messagetype | messagedate | eventstart |
      | news 1     | news 1     | news        | 1487952035  |            |
      | news 2     | news 2     | news        | 1487952036  |            |
      | news 3     | news 3     | news        | 1487952037  |            |
      | upcoming 1 | upcoming 1 | event       | 1487952037  | 2145916800 |
      | upcoming 2 | upcoming 2 | event       | 1487952037  | 2145916801 |
      | upcoming 3 | upcoming 3 | event       | 1487952037  | 2145916802 |
      | upcoming 4 | upcoming 4 | event       | 1487952037  | 2145916803 |
      | past 1     | past 1     | event       | 1487952037  | 946857601  |
      | past 2     | past 2     | event       | 1487952037  | 946857602  |
      | past 3     | past 3     | event       | 1487952037  | 946857603  |
      | past 4     | past 4     | event       | 1487952037  | 946857604  |
      | past 5     | past 5     | event       | 1487952037  | 946857605  |
      | past 6     | past 6     | event       | 1487952037  | 946857606  |
      | past 7     | past 7     | event       | 1487952037  | 946857607  |
    And I click on "View all news and events" "link" in the "News and events" "block"
    # Page 1, should see all 3 news, first 3 upcoming and first 3 past events.
    Then I should see "news 1"
    And I should see "news 2"
    And I should see "news 3"
    And I should see "upcoming 1"
    And I should see "upcoming 2"
    And I should see "upcoming 3"
    And I should not see "upcoming 4"
    And I should see "past 7"
    And I should see "past 6"
    And I should see "past 5"
    And I should not see "past 4"
    And I should not see "past 3"
    And I should not see "past 2"
    And I should not see "past 1"
    And "Previous" "link" should not exist
    And "Next" "link" should exist
    When I follow "Next"
    # Page 2, should see no news, 1 remaining upcoming event and next 3 past events.
    Then I should see "No news messages have been posted to this website."
    And I should see "upcoming 4"
    And I should see "past 4"
    And I should see "past 3"
    And I should see "past 2"
    And I should not see "past 1"
    And "Previous" "link" should exist
    And "Next" "link" should exist
    When I follow "Next"
    # Page 3, should see no news or upcoming events, and 1 remaining past event.
    Then I should see "No news messages have been posted to this website."
    And I should see "There are no upcoming events to display."
    And I should see "past 1"
    And "Previous" "link" should exist
    And "Next" "link" should not exist

