@ou @ou_vle @block @block_news @javascript
Feature: Backup/Restore news and events block
  In order to Rollforward news events on a page
  As an author
  I need to create news and event messages backup and then restore them

  Background:
    Given the following "courses" exist:
      | fullname | shortname | startdate  | format      |
      | Course 1 | C1        |            | oustudyplan |
      | Course 5 | B747-14K  | 1412899200 | oustudyplan |
    And the following "users" exist:
      | username | timezone      |
      | ukuser   | Europe/London |
    And the following "course enrolments" exist:
      | user   | course | role    |
      | ukuser | C1     | student |
    And I am using the OSEP theme
    And I am on the "Course 1" "Course" page logged in as "admin"
    And I turn editing mode on in the OSEP theme
    And I select "News" from the "bui_addblock" singleselect
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
    Then I should see "news2" in the "News and events" "block"
    And I should see "news3" in the "News and events" "block"
    And I should not see "news1" in the "News and events" "block"
    And I should see "event1" in the "News and events" "block"
    And I should see "event2" in the "News and events" "block"
    And I should not see "event3" in the "News and events" "block"

    # Without reload, Backup step fails
    And I reload the page
    Given the following config values are set as admin:
      | enableasyncbackup | 0 |
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name       | Course 2 |
      | Schema | Course short name | C2       |
    And I am on the "Course 2" "Course" page
    Then I should see "news2" in the "News and events" "block"
    And I should see "news3" in the "News and events" "block"
    And I should not see "news1" in the "News and events" "block"
    And I should see "event1" in the "News and events" "block"
    And I should see "Ev1 locator" in the "News and events" "block"
    And I should see "event2" in the "News and events" "block"
    And I should see "Ev2 locator" in the "News and events" "block"
    And I should not see "event3" in the "News and events" "block"

    When I follow "View all news and events"
    Then I should see "News"
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

  Scenario: Test sort order when rollforward news and event with false data.
    And I am on the "Course 5" "Course" page
    And I select "News" from the "bui_addblock" singleselect
    And the news block for course "B747-14K" is in news and events mode
    And I reload the page
    # Add the false data for news item.
    Given the following news messages exist on course "B747-14K":
      | title  | message | messagetype | messagedate | eventstart | eventlocation | messagerepeat |
      | news1  | news1   | 1           | 1483228800  | 2145920000 |               | 1             |
      | news2  | news2   | 1           | 1483228801  | 2145990000 |               | 1             |
      | news3  | news3   | 1           | 1483228802  | 2145950000 |               | 1             |
      | news4  | news4   | 1           | 1483228803  |            |               | 1             |
      | event1 | event1  | 2           | 1483228803  | 2145924000 | Ev1 locator   | 1             |
      | event2 | event2  | 2           | 1483228804  | 2145924001 | Ev2 locator   | 1             |
      | event3 | event3  | 2           | 1483228805  | 2145924002 | Ev3 locator   | 1             |
    When I reload the page
    When I follow "View all news and events"
    # The sort order still correct with false data.
    Then I should see "news4" in the "(//*[@class='block_news_msg'])[1]" "xpath_element"
    And I should see "news3" in the "(//*[@class='block_news_msg'])[2]" "xpath_element"
    And I should see "news2" in the "(//*[@class='block_news_msg'])[3]" "xpath_element"
    And I should see "news1" in the "(//*[@class='block_news_msg'])[4]" "xpath_element"
    #And I navigate to "Reports" in current page administration
    #And I click on "Roll forward" "link"
    When I am on the "Course 5" "report_rollforward > Report" page
    # Set up options.
    When I set the following fields to these values:
      | New presentation code | 15K     |
      | Day                   | 10      |
      | Month                 | October |
      | Year                  | 2015    |
      | Hour                  | 00      |
      | Minute                | 00      |
    And I press "id_submitbutton"
    And I should see "Roll forward task has been queued and will run shortly"
    And I run all adhoc tasks
    # Look at new course.
    And I press "Return to course"
    When I follow "View all news and events"
    # The sort order still correct after roll forward.
    Then I should see "news4" in the "(//*[@class='block_news_msg'])[1]" "xpath_element"
    And I should see "news3" in the "(//*[@class='block_news_msg'])[2]" "xpath_element"
    And I should see "news2" in the "(//*[@class='block_news_msg'])[3]" "xpath_element"
    And I should see "news1" in the "(//*[@class='block_news_msg'])[4]" "xpath_element"
