@ou @ou_vle @block @block_news @javascript
Feature: News subscription
  In order to subscribe news
  As a user
  I need to be able to subscribe or unsubscribe, manage subscribers news

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format      |
      | Course 1 | C1        | oustudyplan |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher  | Teacher   | 1        |
      | student  | Student   | 1        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
      | student | C1     | student        |
    And I am on the "Course 1" course page logged in as "teacher"
    And I turn editing mode on
    And I add the "News" block
    And I log out

  Scenario: Subscription options should appear for those who have permission to add a message
    And I am on the "Course 1" course page logged in as "teacher"
    And I click on "News" "link" in the "//div[@class='oustudyplan-headerstripe']/ul/li[2]" "xpath_element"
    Then "View subscribers" "button" should be visible
    And I am on the "Course 1" "Course" page logged in as "student"
    And I click on "News" "link" in the "//div[@class='oustudyplan-headerstripe']/ul/li[2]" "xpath_element"
    Then "View subscribers" "button" should not exist

  Scenario: Check user Subscribe and Unsubscribe
    And I am on the "Course 1" course page logged in as "teacher"
    And I click on "News" "link" in the "//div[@class='oustudyplan-headerstripe']/ul/li[2]" "xpath_element"
    And I press "Subscribe to news"
    Then "Unsubscribe" "button" should be visible
    And I press "Unsubscribe"
    Then I should see "Subscribe to news"

  Scenario: Manage subscribers of news block
    # Subscribe some users.
    And I am on the "Course 1" course page logged in as "student"
    And I click on "News" "link" in the "//div[@class='oustudyplan-headerstripe']/ul/li[2]" "xpath_element"
    And I press "Subscribe to news"
    Then "Unsubscribe" "button" should be visible
    And I log out

    And I am on the "Course 1" course page logged in as "teacher"
    And I click on "News" "link" in the "//div[@class='oustudyplan-headerstripe']/ul/li[2]" "xpath_element"
    And I press "Subscribe to news"
    Then "Unsubscribe" "button" should be visible
    And I press "View subscribers"
    Then "Teacher 1" "table_row" should exist
    And "Student 1" "table_row" should exist

    # Try to unsubscribe but cancel.
    And I press "Select all"
    And I press "Unsubscribe selected users"
    Then I should see "Teacher 1" in the ".block_news_unsubcribelist" "css_element"
    And I should see "Student 1" in the ".block_news_unsubcribelist" "css_element"
    And I press "Cancel"
    Then "Teacher 1" "table_row" should exist
    And "Student 1" "table_row" should exist

    # Unsubscribe one user.
    When I click on "Teacher 1" "checkbox" in the "Teacher 1" "table_row"
    And I press "Unsubscribe selected users"
    Then I should see "Teacher 1" in the ".block_news_unsubcribelist" "css_element"
    And I should not see "Student 1" in the ".block_news_unsubcribelist" "css_element"
    And I press "Unsubscribe selected users"
    Then "Teacher 1" "table_row" should not exist
    And "Student 1" "table_row" should exist

    # Use select all to unsubscribe all users.
    And I follow "(new News block)"
    And I press "Subscribe to news"
    And I press "View subscribers"
    Then "Teacher 1" "table_row" should exist
    And "Student 1" "table_row" should exist
    And I press "Select all"
    And I press "Unsubscribe selected users"
    And I should see "Teacher 1" in the ".block_news_unsubcribelist" "css_element"
    And I should see "Student 1" in the ".block_news_unsubcribelist" "css_element"
    And I press "Unsubscribe selected users"
    Then I should see "There are no subscribers yet for this news block."
