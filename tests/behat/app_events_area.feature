@ou @ou_vle @block @block_news @app @javascript
Feature: Events block
  In order to view events on a mobile app
  As an student
  In the app, events page area (tab) should work.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | startdate  |
      | Course 1 | C1        | 1554360614 |
    And the following "users" exist:
      | username | timezone      |
      | student1 | Europe/London |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |

  Scenario: Events area display items on mobile app.
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "News" block
    And the news block for course "C1" is in news and events mode
    And the following news messages exist on course "C1":
      | title   | message         | messagetype | messagedate | eventstart | eventend   | eventlocation |
      | Event 1 | Event message 1 | 2           | 1483228803  | 2145924000 | 2175024000 | Ev1 locator   |
      | Event 2 | Event message 2 | 2           | 1483228803  | 2145925000 | 2176024010 | Ev2 locator   |
      | Event 3 | Event message 3 | 2           | 1483228803  | 1546300800 | 1546387200 | Ev3 locator   |
      | Event 4 | Event message 4 | 2           | 1483228803  | 1546473600 | 1546560000 | Ev4 locator   |
      | Event 5 | Event message 5 | 2           | 1483228803  | 1546646400 | 1546732800 | Ev5 locator   |
      | Event 6 | Event message 6 | 2           | 1483228803  | 1546819200 | 1546905600 | Ev6 locator   |
      | Event 7 | Event message 7 | 2           | 1483228803  | 1546992000 | 1547078400 | Ev7 locator   |
      | Event 8 | Event message 8 | 2           | 1483228803  | 1547164800 | 1547251200 | Ev8 locator   |
      | Event 9 | Event message 9 | 2           | 1483228803  | 1547337600 | 1547424000 | Ev9 locator   |
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Course overview" in the app
    And I press "arrow forward" in the app
    # Checking events tab is visible.
    When I press "Events" in the app
    # Check recent items is visible.
    Then I should see "Upcoming events"
    And I should see "Event 1"
    And I should see "Friday, 1 January 2038, 2:00 AM to Friday, 3 December 2038, 9:20 PM"
    And I should see "Ev1 locator"
    And I should see "Event message 1"
    And I should see "Past events"
    And I should see "Event 7"
    And I should see "Wednesday, 9 January 2019, 12:00 AM to Thursday, 10 January 2019, 12:00 AM"
    And I should see "Ev7 locator"
    And I should see "Event message 7"
    # Check old item is not visible when visit the page without scrolling down.
    And I should not see "Event 8"
    And I should not see "Friday, 11 January 2019, 12:00 AM to Saturday, 12 January 2019, 12:00 AM"
    And I should not see "Ev8 locator"
    And I should not see "Event message 8"
    And I should not see "Event 9"
    And I should not see "Sunday, 13 January 2019, 12:00 AM to Monday, 14 January 2019, 12:00 AM"
    And I should not see "Ev9 locator"
    And I should not see "Event message 9"
    # Old item should be loaded and visible after we scrolled to the bottom.
    When I trigger the news block infinite scroll "block_news_infinite_load_messages"
    And I should see "Event 8"
    And I should see "Friday, 11 January 2019, 12:00 AM to Saturday, 12 January 2019, 12:00 AM"
    And I should see "Ev8 locator"
    And I should see "Event message 8"
    And I should see "Event 9"
    And I should see "Sunday, 13 January 2019, 12:00 AM to Monday, 14 January 2019, 12:00 AM"
    And I should see "Ev9 locator"
    And I should see "Event message 9"

    # Open in browser.
    And I press the page menu button in the app
    And I press "Open in browser" in the app
    And I switch to the browser tab opened by the app
    And I log in as "student1"

    # Check recent items is visible.
    Then I should see "Upcoming events"
    And I should see "Event 1"
    And I should see "Friday, 1 January 2038, 2:00 AM to Friday, 3 December 2038, 9:20 PM"
    And I should see "Ev1 locator"
    And I should see "Past events"
    And I should see "Event 9"
    And I should see "Sunday, 13 January 2019, 12:00 AM to Monday, 14 January 2019, 12:00 AM"
    And I should see "Ev9 locator"

  Scenario: Check Events tab display on mobile devices when news block existed.
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "News" block
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Course overview" in the app
    And I press "arrow forward" in the app
    Then I should not see "Events"
    When the news block for course "C1" is in news and events mode
    And I enter the app
    And I log in as "student1"
    And I press "Course 1" near "Course overview" in the app
    And I press "arrow forward" in the app
    Then I should see "Events"
