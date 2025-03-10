@ou @ou_vle @block @block_news
Feature: Show unread messages
  In order to see if there are new messages
  As anyone
  I want to see an unread icon in the news block

  Background:
    Given I am using the OSEP theme
    And the following OU websites exist:
      | type       | shortname | overridetitle | visible |
      | osepmodule | B747-25B  | Course 1      | 1       |
    And the following news messages exist on course "B747-25B":
      | title    | message           | image                                   | messagedate      |
      | message1 | Lorem ipsum dolor | /blocks/news/tests/fixtures/kitten1.jpg | ## 2025-01-01 ## |
      | message2 | sit amet          | /blocks/news/tests/fixtures/kitten2.jpg | ## 2025-01-02 ## |

  @javascript
  Scenario: Unread icon should show initially, but go away if you read both messages
    When I am on the "Course 1" "Course" page logged in as "admin"
    Then ".block_news .card-title img[title='(new)']" "css_element" should be visible

    When I follow "message1"
    And I am on the "Course 1" "Course" page
    Then ".block_news .card-title img[title='(new)']" "css_element" should be visible

    When I follow "message2"
    And I am on the "Course 1" "Course" page
    Then ".block_news .card-title img[title='(new)']" "css_element" should not exist

  @javascript
  Scenario: Unread icon should show initially, but go away if you go to the view all page
    When I am on the "Course 1" "Course" page logged in as "admin"
    Then ".block_news .card-title img[title='(new)']" "css_element" should be visible

    When I follow "View all messages"
    And I am on the "Course 1" "Course" page
    Then ".block_news .card-title img[title='(new)']" "css_element" should not exist
