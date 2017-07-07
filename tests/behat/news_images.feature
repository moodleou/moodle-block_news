@ou @ou_vle @block @block_news
Feature: Display images in posts
  In order to make my news messages more visually appealing
  As an author
  I want display an image with each news message

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And I log in as "admin"
    And I am on site homepage
    And I follow "Course 1"
    And I turn editing mode on
    And I add the "News" block
    And the news block for course "C1" is in news and events mode
    And the following news messages exist on course "C1":
      | title    | message           | image                                   | messagedate |
      | message1 | Lorem ipsum dolor | /blocks/news/tests/fixtures/kitten1.jpg | 1483228800  |
      | message2 | sit amet          | /blocks/news/tests/fixtures/kitten2.jpg | 1483315200  |
    And I reload the page

  Scenario: The news block should display image thumbnails
    Then I should see "message1"
    And I should see "message2"
    And I should see image "thumbnail.jpg" in news message "message1"
    And I should see image "thumbnail.jpg" in news message "message2"

  Scenario: The single view page should display the full image for the message
    When I click on "message2" "link" in the ".block_news_msg" "css_element"
    Then I should see "message2"
    And I should see image "kitten2.jpg" in news message "message2"
    And I should not see "message1"
    When I follow "Previous"
    Then I should see "message1"
    And I should see image "kitten1.jpg" in news message "message1"
    And I should not see "message2"

  Scenario: The "View all" page should display the thumbnail image for each message
    When I follow "View all news and events"
    Then I should see "message1"
    And I should see "message2"
    And I should see image "thumbnail.jpg" in news message "message1"
    And I should see image "thumbnail.jpg" in news message "message2"
