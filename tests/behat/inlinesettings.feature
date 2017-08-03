@ou @ou_vle @block @block_news @javascript
Feature: Inline settings form
  In order to edit settings when the full editing form is disabled
  As a website manager
  I want edit selected settings from a form displayed in the block

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | format    |
      | Course 1 | C1        | 0        | ousubject |
    And I log in as "admin"
    And I am on site homepage
    And I am using the OSEP theme
    And I am on "Course 1" course homepage
    And the news block for course "C1" is in news and events mode
    And the following news messages exist on course "C1":
      | title      | message    | messagetype | messagedate | eventstart |
      | news 1     | news 1     | news        | 1487952035  |            |
      | upcoming 1 | upcoming 1 | event       | 1487952037  | 2145916800 |
    And I reload the page

  Scenario: Show the editing form when editing mode is on
    Given I should see "news 1" in the "News and events" "block"
    And I should see "upcoming 1" in the "News and events" "block"
    And "Edit configuration" "fieldset" should not exist in the "News and events" "block"
    When I follow "Turn editing on"
    Then I should see "news 1" in the "News and events" "block"
    And I should see "upcoming 1" in the "News and events" "block"
    And "Add a message" "link" should exist in the "News and events" "block"
    And "Edit configuration" "fieldset" should exist in the "News and events" "block"
    Then I should not see "Include messages from the listed feeds (URLs)" in the "News and events" "block"
    When I click on "Edit configuration" "fieldset" in the "News and events" "block"
    Then I should see "Include messages from the listed feeds (URLs)" in the "News and events" "block"

  Scenario: Enter and save feed URLs
    Given I follow "Turn editing on"
    And I click on "Edit configuration" "fieldset" in the "News and events" "block"
    And the field "feedurls" matches value ""
    When I set the field "feedurls" to "http://example.com"
    And I click on "Save changes" "button" in the "News and events" "block"
    And I click on "Edit configuration" "fieldset" in the "News and events" "block"
    Then the field "feedurls" matches value "http://example.com"
    When I follow "Turn editing off"
    And I follow "Turn editing on"
    Then the field "feedurls" matches value "http://example.com"

  Scenario: Enter invalid URL
    Given I follow "Turn editing on"
    And I click on "Edit configuration" "fieldset" in the "News and events" "block"
    When I set the field "feedurls" to "http://example.com/reallylongurlthatexceedsthe255characterlimitbyhavingastupidlylongbitattheendthatimmakingupasigoalongwowurlscanbereallylongcanttheylittlebitmorealmostthereokletsjustuserandomrubbishnowdsdgasdgawegtrebrbaerbeerbaerberhgfhgfhdgfhdfgdgfjdgfdfgerhge"
    And I click on "Save changes" "button" in the "News and events" "block"
    Then I should see "URLs limited to 255 characters"

  Scenario: Don't show the inline form on website that doesn't use the subject format
    Given I log out (in the OSEP theme)
    And I am not using the OSEP theme
    And the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 2 | C2        | 0        | topics |
    And I log in as "admin"
    And I am on "Course 2" course homepage
    And I turn editing mode on
    And I add the "News" block
    And the news block for course "C2" is in news and events mode
    When I reload the page
    Then "Edit configuration" "fieldset" should not exist in the "News and events" "block"
