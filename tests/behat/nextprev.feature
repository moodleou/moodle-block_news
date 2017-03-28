@ou @ou_vle @block @block_news
Feature: Next/Previous message navigation
  In order to read full messages in sequence
  As a student
  I want navigate to the next and previous news and event, from the single view page.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username |
      | teacher1 |
      | student1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add the "News" block

  Scenario: Navigate through messages in default mode
    Given the following news messages exist on course "C1":
      | title  | message | messagetype | messagedate |
      | news 1 | news 1  | news        | 1487952035  |
      | news 2 | news 2  | news        | 1487952036  |
      | news 3 | news 3  | news        | 1487952037  |
    And I reload the page
    And I click on "View" "link" in the "block_news" "block"
    And I should see "news 3"
    And "Previous" "link" should exist
    And "Next" "link" should not exist
    When I follow "Previous"
    Then I should see "news 2"
    And "Previous" "link" should exist
    And "Next" "link" should exist
    When I follow "Previous"
    Then I should see "news 1"
    And "Previous" "link" should not exist
    And "Next" "link" should exist

  Scenario: Navigate through message in news and events mode
    Given the news block for course "C1" is in news and events mode
    And the following news messages exist on course "C1":
      | title   | message | messagetype | messagedate | eventstart |
      | news 1  | news 1  | news        | 1487952035  |            |
      | event 1 | event 1 | event       | 1487952036  | 2145916803 |
      | news 2  | news 2  | news        | 1487952037  |            |
      | event 2 | event 2 | event       | 1487952038  | 2145916802 |
      | news 3  | news 3  | news        | 1487952039  |            |
      | event 3 | event 3 | event       | 1487952040  | 2145916801 |
    And I reload the page
    And I click on "View" "link" in the "block_news" "block"
    And I should see "news 3"
    And "Previous" "link" should exist
    And "Next" "link" should not exist
    When I follow "Previous"
    Then I should see "news 2"
    And "Previous" "link" should exist
    And "Next" "link" should exist
    When I follow "Previous"
    Then I should see "news 1"
    And "Previous" "link" should not exist
    And "Next" "link" should exist
    When I follow "C1"
    And I click on "View" "link" in the ".block_news_event" "css_element"
    And I should see "event 3"
    And "Previous" "link" should exist
    And "Next" "link" should not exist
    When I follow "Previous"
    Then I should see "event 2"
    And "Previous" "link" should exist
    And "Next" "link" should exist
    When I follow "Previous"
    Then I should see "event 1"
    And "Previous" "link" should not exist
    And "Next" "link" should exist
