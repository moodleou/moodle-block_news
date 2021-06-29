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
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add the "News" block
    And I log out

  Scenario: Subscription options should appear for those who have permission to add a message
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "News"
    Then I should see "Subscribe to news"
    And I log out
    And I log in as "student"
    And I am on "Course 1" course homepage
    And I follow "News"
    Then I should not see "Subscribe to news"

  Scenario: Check user Subscribe and Unsubscribe
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "News"
    And I press "Subscribe to news"
    Then I should see "Unsubscribe to news"
    And I press "Unsubscribe"
    Then I should see "Subscribe to news"

  Scenario: Manage subscribers of news block
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "News"
    And I press "Subscribe to news"
    Then I should see "Unsubscribe to news"
    And I press "View subscribers"
    Then I should see "Teacher 1"
    And I press "Select all"
    And I click on "#block-news-subscription-list #block-news-buttons input:nth-last-child(3)" "css_element"
    And I click on ".buttons .singlebutton:first-child button" "css_element"
    Then I should see "There are no subscribers yet for this news."
