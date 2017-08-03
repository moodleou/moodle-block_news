@ou @ou_vle @block @block_news @javascript
Feature: Display events in news block
  In order to Display events on a page
  As an author
  I want create Event messages as well as news messages

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
    And I am on site homepage
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add the "News" block
    And the news block for course "C1" is in news and events mode
    And I reload the page

  Scenario: Test activitation of additional form fields for events
    Given I click on "Add" "link" in the "News and events" "block"
    And the field "Type" matches value "News item"
    And the "eventstart[day]" "field" should be disabled
    And the "eventstart[month]" "field" should be disabled
    And the "eventstart[year]" "field" should be disabled
    And the "eventstart[hour]" "field" should be disabled
    And the "eventstart[minute]" "field" should be disabled
    And the "All day event" "field" should be disabled
    And the "eventend[day]" "field" should be disabled
    And the "eventend[month]" "field" should be disabled
    And the "eventend[year]" "field" should be disabled
    And the "eventend[hour]" "field" should be disabled
    And the "eventend[minute]" "field" should be disabled
    And the "Event location" "field" should be disabled
    And the "class" attribute of "//label[contains(., 'Image')]/../.." "xpath_element" should not contain "disabled"
    When I set the field "Type" to "Event"
    Then the "eventstart[day]" "field" should be enabled
    And the "eventstart[month]" "field" should be enabled
    And the "eventstart[year]" "field" should be enabled
    And the "eventstart[hour]" "field" should be disabled
    And the "eventstart[minute]" "field" should be disabled
    And the "All day event" "field" should be enabled
    And the "eventend[day]" "field" should be disabled
    And the "eventend[month]" "field" should be disabled
    And the "eventend[year]" "field" should be disabled
    And the "eventend[hour]" "field" should be disabled
    And the "eventend[minute]" "field" should be disabled
    And the "Event location" "field" should be enabled
    And the "class" attribute of "//label[contains(., 'Image')]/../.." "xpath_element" should contain "disabled"
    When I set the field "All day event" to ""
    Then the "eventstart[day]" "field" should be enabled
    And the "eventstart[month]" "field" should be enabled
    And the "eventstart[year]" "field" should be enabled
    And the "eventstart[hour]" "field" should be enabled
    And the "eventstart[minute]" "field" should be enabled
    And the "All day event" "field" should be enabled
    And the "eventend[day]" "field" should be enabled
    And the "eventend[month]" "field" should be enabled
    And the "eventend[year]" "field" should be enabled
    And the "eventend[hour]" "field" should be enabled
    And the "eventend[minute]" "field" should be enabled
    And the "Event location" "field" should be enabled
    And the "class" attribute of "//label[contains(., 'Image')]/../.." "xpath_element" should contain "disabled"

  Scenario: Event date validation
    Given I click on "Add" "link" in the "News and events" "block"
    When I set the following fields to these values:
      | Title             | message1      |
      | Text              | message1 text |
      | Type              | Event         |
      | eventstart[day]   | 1             |
      | eventstart[month] | January       |
      | eventstart[year]  | 2000          |
    And I press "Save changes"
    Then I should see "Event start must be in the future"
    When I set the following fields to these values:
      | eventstart[day]   | 1       |
      | eventstart[month] | January |
      | eventstart[year]  | 2038    |
      | alldayevent       | 0       |
      | eventend[day]     | 1       |
      | eventend[month]   | January |
      | eventend[year]    | 2037    |
    And I press "Save changes"
    Then I should see "Event end must be after event start"
    When I set the following fields to these values:
      | eventend[day]     | 2    |
      | eventend[year]    | 2038 |
    And I press "Save changes"
    Then I should see "message1" in the "News and events" "block"

  Scenario: Display all day event
    # Create an all day event on 1/1/2038 (Perth, Australia time).
    Given the following news messages exist on course "C1":
      | title    | message           | messagetype | messagedate | eventstart | eventlocation |
      | message1 | Lorem ipsum dolor | 2           | 1483228800  | 2145888000 | Anywhere      |
    And I reload the page
    Then I should see "01" in the ".block_news_event time" "css_element"
    And I should see "Jan" in the ".block_news_event time" "css_element"
    And I should see "message1" in the "News and events" "block"
    And I should see "Friday, 1 January 2038" in the "News and events" "block"
    And I should not see "00:00" in the "News and events" "block"
    And I should see "Anywhere" in the "News and events" "block"

  Scenario: Display event ending on the same day
    # Create an event starting at 10:00 and ending at 11:00 1/1/2038 (Perth, Australia time).
    Given the following news messages exist on course "C1":
      | title    | message           | messagetype | messagedate | eventstart | eventend   |
      | message1 | Lorem ipsum dolor | 2           | 1483228800  | 2145924000 | 2145927600 |
    And I reload the page
    Then I should see "01" in the ".block_news_event time" "css_element"
    And I should see "Jan" in the ".block_news_event time" "css_element"
    And I should see "Friday, 1 January 2038, 10:00 AM to 11:00 AM" in the "News and events" "block"

  Scenario: Display event ending on another day
    # Create an event starting at 10:00 on 1/1/2038 and ending at 11:00 2/1/2038 (Perth, Australia time)
    Given the following news messages exist on course "C1":
      | title    | message           | messagetype | messagedate | eventstart | eventend   |
      | message1 | Lorem ipsum dolor | 2           | 1483228800  | 2145924000 | 2146014000 |
    And I reload the page
    Then I should see "01" in the ".block_news_event time" "css_element"
    And I should see "Jan" in the ".block_news_event time" "css_element"
    And I should see "Friday, 1 January 2038, 10:00 AM to Saturday, 2 January 2038, 11:00 AM" in the "News and events" "block"

  Scenario: View all day event from a time zone behind the server
    # Create an all day event on 1/1/2038 (Perth, Australia time).
    Given the following news messages exist on course "C1":
      | title    | message           | messagetype | messagedate | eventstart |
      | message1 | Lorem ipsum dolor | 2           | 1483228800  | 2145888000 |
    And I log out
    # View the event as a user in the UK (8 hours behind).
    And I log in as "ukuser"
    And I am on "Course 1" course homepage
    Then I should see "01" in the ".block_news_event time" "css_element"
    And I should see "Jan" in the ".block_news_event time" "css_element"
    And I should see "message1" in the "News and events" "block"
    And I should see "Friday, 1 January 2038" in the "News and events" "block"
    And I should not see "00:00" in the "News and events" "block"

  Scenario: Display multiple events in order of start date excluding past events
    Given the following news messages exist on course "C1":
      | title    | message                         | messagetype | messagedate | eventstart |
      | message1 | Posted first, occuring second   | 2           | 1483228800  | 2145974400 |
      | message2 | Posted second, already occurred | 2           | 1483228801  | 946656000  |
      | message3 | Posted third, occuring first    | 2           | 1483228802  | 2145924000 |
    And I reload the page
    And I should see "message1" in the "News and events" "block"
    And I should see "message3" in the "News and events" "block"
    And I should not see "message2" in the "News and events" "block"
    And "message3" "text" should appear before "message1" "text"

  Scenario: Only display the first 2 of each message type
    Given the following news messages exist on course "C1":
      | title  | message | messagetype | messagedate | eventstart |
      | news1  | news1   | 1           | 1483228800  |            |
      | news2  | news2   | 1           | 1483228801  |            |
      | news3  | news3   | 1           | 1483228802  |            |
      | event1 | event1  | 2           | 1483228800  | 2145924000 |
      | event2 | event2  | 2           | 1483228801  | 2145924001 |
      | event3 | event3  | 2           | 1483228802  | 2145924002 |
    When I reload the page
    Then I should see "news2" in the "News and events (new)" "block"
    And I should see "news3" in the "News and events (new)" "block"
    And I should not see "news1" in the "News and events (new)" "block"
    And I should see "event1" in the "News and events (new)" "block"
    And I should see "event2" in the "News and events (new)" "block"
    And I should not see "event3" in the "News and events (new)" "block"

  Scenario: Don't show byline on event pages
    Given the following news messages exist on course "C1":
      | title    | message      | messagetype | messagedate | eventstart |
      | message1 | test message | 2           | 1483228800  | 2145974400 |
    And I reload the page
    And I click on "message1" "link" in the "News and events" "block"
    Then I should see "message1"
    And I should see "test message"
    And I should not see "by Admin User"
