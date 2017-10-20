@ou @ou_vle @block @block_news
Feature: Usage of news block feeds
  In order to share news and events
  As a teacher
  I need to be able to add a feed to the news block

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
    And the following "users" exist:
      | username |
      | teacher1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
    And the following config values are set as admin:
      | enablerssfeeds | 1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add the "News" block
    And the news block for course "C1" is in news and events mode
    And the following news messages exist on course "C1":
      | title     | message      | image                                   | messagedate |
      | Message 1 | Message text | /blocks/news/tests/fixtures/kitten1.jpg | 1483228800  |
    And I reload the page
    # Requires a second go or the empty image causes an issue.
    And the following news messages exist on course "C1":
      | title   | message    | messagedate | messagetype | eventstart | eventend   | eventlocation |
      | Event 1 | Event text | 1483228700  | event       | 1612086400 | 1735689600 | Milton Keynes |
    And I reload the page

  Scenario: Add a feed in the news block settings
    Given I configure the "News and events (new)" block
    When I set the news block feedurls field to fixture file "feed.xml"
    And I press "Save changes"
    Then I should see "Fourth message" in the "News and events (new)" "block"
    When I follow "Fourth message"
    Then I should see "Fourth message"
    And "View original message" "link" should exist
    And the "href" attribute of "View original message" "link" should contain "blocks/news/message.php?m=575003"

  Scenario: Import a feed from another course - events not enabled - message image included
    Given I am on "Course 2" course homepage
    And I add the "News" block
    Then I should not see "Message 1"
    And I configure the "(new News block)" block
    And I set the news block feedurls field to another course "C1" feed
    And I press "Save changes"
    Then I should see "Message 1" in the "(new News block) (new)" "block"
    And I should see image "thumbnail.jpg" in news message "Message 1"
    And I should not see "Event 1"
    # Check as student only enrolled on C2 not C1.
    And the following "users" exist:
      | username |
      | student1 |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C2     | student |
    And I log out
    And I log in as "student1"
    And I am on "Course 2" course homepage
    Then I should see "Message 1" in the "(new News block) (new)" "block"
    And I should see image "thumbnail.jpg" in news message "Message 1"

  Scenario: Import a feed from another course - events enabled - detail included
    Given I am on "Course 2" course homepage
    And I add the "News" block
    And the news block for course "C2" is in news and events mode
    Then I should not see "Message 1"
    And I configure the "(new News block)" block
    And I set the news block feedurls field to another course "C1" feed
    And I press "Save changes"
    Then I should see "Message 1" in the "News and events (new)" "block"
    And I should see image "thumbnail.jpg" in news message "Message 1"
    And I should see "Event 1"
    And I should see "Milton Keynes"
    And I should see "Sunday, 31 January 2021, 5:46 PM to Wednesday, 1 January 2025, 8:00 AM"

  Scenario: Check author in the feed
    Given I configure the "News and events (new)" block
    When I set the news block feedurls field to fixture file "feed2.xml"
    And I press "Save changes"
    Then I should see "Authorless message"
    And I should see "No author here"
    And I should not see "-" in the "News and events (new)" "block"
