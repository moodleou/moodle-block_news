@ou @ou_vle @block @block_news @javascript

Feature: Manage and display news messages in the News Block
  In order to effectively manage course announcements
  As a teacher
  I want to configure the News Block, manage its messages, and control how they are displayed

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname |
      | teacher1 | ANNE      |
      | student1 | BOB       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I am on the "Course 1" "course" page logged in as teacher1
    And I turn editing mode on
    And I add the "News" block

  Scenario: Showing and hiding news titles in the News block
    Given I click on "Add" "link" in the "(new News block)" "block"
    And I set the following fields to these values:
      | Title | Title 1   |
      | Text  | Message 1 |
    When I press "Save changes"
    And I wait until the page is ready
    Then I should see "Message 1" in the "(new News block)" "block"
    And I should see "Title 1" in the "(new News block)" "block"

    And I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the field "Hide titles" to "Yes"
    And I press "Save changes"
    And I should see "Message 1" in the "(new News block)" "block"
    And I should not see "Title 1" in the "(new News block)" "block"

    And I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the field "Hide titles" to "No"
    And I press "Save changes"
    And I should see "Message 1" in the "(new News block)" "block"
    And I should see "Title 1" in the "(new News block)" "block"

  Scenario: Configure the summary display in the news block
    # Verify Summary is None.
    Given I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    When I set the field "Summary" to "None"
    And I press "Save changes"
    And I click on "Add" "link" in the "(new News block)" "block"
    And I set the following fields to these values:
      | Title | Message 1                                                                                                                             |
      | Text  | Message1 text is longer and contains over 100 characters but will get cut off on the front page at some point before the word JACKPOT |
    And I press "Save changes"
    And I wait until the page is ready
    Then I should see "Message 1" in the "(new News block)" "block"
    And I should not see "Message1 text is longer and contains over 100 characters but will get cut off on the front page at some point before the word JACKPOT"

    # Verify Summary is Short.
    And I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the field "Summary" to "Short"
    And I press "Save changes"
    And I wait until the page is ready
    And I should see "Message 1" in the "(new News block)" "block"
    And I should see "Message1 text is longer and contains ..." in the "(new News block)" "block"

    # Verify Summary is Medium.
    And I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the field "Summary" to "Medium"
    And I press "Save changes"
    And I wait until the page is ready
    And I should see "Message 1" in the "(new News block)" "block"
    And I should see "Message1 text is longer and contains over 100 characters but will get cut off on the front page ..." in the "(new News block)" "block"

    # Verify Summary is Long.
    And I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the field "Summary" to "Long"
    And I press "Save changes"
    And I wait until the page is ready
    And I should see "Message 1" in the "(new News block)" "block"
    And I should see "Message1 text is longer and contains over 100 characters but will get cut off on the front page at some point before the word JACKPOT" in the "(new News block)" "block"

  Scenario: Verify that only the latest message is displayed
    Given the following news messages exist on course "C1":
      | title   | message   |
      | Title 1 | Message 1 |
      | Title 2 | Message 2 |
      | Title 3 | Message 3 |
      | Title 4 | Message 4 |
      | Title 5 | Message 5 |
    And I reload the page
    When I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the field "Show messages" to "Latest message only"
    And I press "Save changes"
    And I wait until the page is ready
    Then I should not see "Title 5" in the "(new News block)" "block"
    And I should not see "Message 5" in the "(new News block)" "block"
    And I should not see "Title 4" in the "(new News block)" "block"
    And I should not see "Message 4" in the "(new News block)" "block"
    And I should not see "Title 3" in the "(new News block)" "block"
    And I should not see "Message 3" in the "(new News block)" "block"
    And I should not see "Title 2" in the "(new News block)" "block"
    And I should not see "Message 2" in the "(new News block)" "block"
    And I should see "Title 1" in the "(new News block)" "block"
    And I should see "Message 1" in the "(new News block)" "block"

  Scenario: Verify that only the 2 most recent messages are displayed (default setting)
    Given the following news messages exist on course "C1":
      | title   | message   |
      | Title 1 | Message 1 |
      | Title 2 | Message 2 |
      | Title 3 | Message 3 |
      | Title 4 | Message 4 |
      | Title 5 | Message 5 |
    And I reload the page
    And I wait until the page is ready
    And I should not see "Title 5" in the "(new News block)" "block"
    And I should not see "Message 5" in the "(new News block)" "block"
    And I should not see "Title 4" in the "(new News block)" "block"
    And I should not see "Message 4" in the "(new News block)" "block"
    And I should not see "Title 3" in the "(new News block)" "block"
    And I should not see "Message 3" in the "(new News block)" "block"
    And I should see "Title 2" in the "(new News block)" "block"
    And I should see "Message 2" in the "(new News block)" "block"
    And I should see "Title 1" in the "(new News block)" "block"
    And I should see "Message 1" in the "(new News block)" "block"

  Scenario: Verify that only the 3 most recent messages are displayed
    Given the following news messages exist on course "C1":
      | title   | message   |
      | Title 1 | Message 1 |
      | Title 2 | Message 2 |
      | Title 3 | Message 3 |
      | Title 4 | Message 4 |
      | Title 5 | Message 5 |
    And I reload the page
    And I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the field "Show messages" to "3 most recent"
    And I press "Save changes"
    And I wait until the page is ready
    And I should not see "Title 5" in the "(new News block)" "block"
    And I should not see "Message 5" in the "(new News block)" "block"
    And I should not see "Title 4" in the "(new News block)" "block"
    And I should not see "Message 4" in the "(new News block)" "block"
    And I should see "Title 3" in the "(new News block)" "block"
    And I should see "Message 3" in the "(new News block)" "block"
    And I should see "Title 2" in the "(new News block)" "block"
    And I should see "Message 2" in the "(new News block)" "block"
    And I should see "Title 1" in the "(new News block)" "block"
    And I should see "Message 1" in the "(new News block)" "block"

  Scenario: Verify that only the 4 most recent messages are displayed
    Given the following news messages exist on course "C1":
      | title   | message   |
      | Title 1 | Message 1 |
      | Title 2 | Message 2 |
      | Title 3 | Message 3 |
      | Title 4 | Message 4 |
      | Title 5 | Message 5 |
    And I reload the page
    And I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the field "Show messages" to "4 most recent"
    And I press "Save changes"
    And I wait until the page is ready
    And I should not see "Title 5" in the "(new News block)" "block"
    And I should not see "Message 5" in the "(new News block)" "block"
    And I should see "Title 4" in the "(new News block)" "block"
    And I should see "Message 4" in the "(new News block)" "block"
    And I should see "Title 3" in the "(new News block)" "block"
    And I should see "Message 3" in the "(new News block)" "block"
    And I should see "Title 2" in the "(new News block)" "block"
    And I should see "Message 2" in the "(new News block)" "block"
    And I should see "Title 1" in the "(new News block)" "block"
    And I should see "Message 1" in the "(new News block)" "block"

  Scenario: Verify that only the 5 most recent messages are displayed
    Given the following news messages exist on course "C1":
      | title   | message   |
      | Title 1 | Message 1 |
      | Title 2 | Message 2 |
      | Title 3 | Message 3 |
      | Title 4 | Message 4 |
      | Title 5 | Message 5 |
    And I reload the page
    And I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the field "Show messages" to "5 most recent"
    And I press "Save changes"
    And I wait until the page is ready
    And I should see "Title 5" in the "(new News block)" "block"
    And I should see "Message 5" in the "(new News block)" "block"
    And I should see "Title 4" in the "(new News block)" "block"
    And I should see "Message 4" in the "(new News block)" "block"
    And I should see "Title 3" in the "(new News block)" "block"
    And I should see "Message 3" in the "(new News block)" "block"
    And I should see "Title 2" in the "(new News block)" "block"
    And I should see "Message 2" in the "(new News block)" "block"
    And I should see "Title 1" in the "(new News block)" "block"
    And I should see "Message 1" in the "(new News block)" "block"

  Scenario: Hide and unhide news messages in the News block using eye icon updates student view
    Given the following news messages exist on course "C1":
      | title   | message   |
      | Title 1 | Message 1 |
      | Title 2 | Message 2 |
      | Title 3 | Message 3 |
      | Title 4 | Message 4 |
      | Title 5 | Message 5 |
    And I am on the "Course 1" "Course" page logged in as "teacher1"
    And I reload the page

    # Teacher hides Title 1 and Title 5.
    When I click on "View all" "link" in the "(new News block)" "block"
    And I click on "hide" "icon" in the "article:contains('Title 1')" "css_element"
    Then I should see "This message has been hidden from students" in the "article:contains('Title 1')" "css_element"
    And I click on "hide" "icon" in the "article:contains('Title 5')" "css_element"
    And I should see "This message has been hidden from students" in the "article:contains('Title 5')" "css_element"

    # Student verifies hidden messages are not visible.
    And I am on the "Course 1" "Course" page logged in as "student1"
    And I click on "View all" "link" in the "(new News block)" "block"
    And I should not see "Title 1"
    And I should not see "Message 1"
    And I should not see "Title 5"
    And I should not see "Message 5"

    # Teacher unhides Title 1 and Title 5.
    And I am on the "Course 1" "Course" page logged in as "teacher1"
    And I click on "View all" "link" in the "(new News block)" "block"
    And I click on "show" "icon" in the "article:contains('Title 1')" "css_element"
    And I should not see "This message has been hidden from students" in the "article:contains('Title 1')" "css_element"
    And I click on "show" "icon" in the "article:contains('Title 5')" "css_element"
    And I should not see "This message has been hidden from students" in the "article:contains('Title 5')" "css_element"

    # Student verifies messages are visible again.
    And I am on the "Course 1" "Course" page logged in as "student1"
    And I click on "View all" "link" in the "(new News block)" "block"
    And I should see "Title 1"
    And I should see "Message 1"
    And I should see "Title 5"
    And I should see "Message 5"

  @_file_upload
  Scenario: Edit a news message using the edit icon
    Given the following news messages exist on course "C1":
      | title   | message   |
      | Title 1 | Message 1 |
      | Title 2 | Message 2 |
      | Title 3 | Message 3 |
      | Title 4 | Message 4 |
      | Title 5 | Message 5 |
    And I reload the page
    When I click on "View all" "link" in the "(new News block)" "block"
    And I click on "Edit Title 1" "icon" in the "article:contains('Title 1')" "css_element"
    And I set the following fields to these values:
      | Title | Title 1 - edit   |
      | Text  | Message 1 - edit |
    And I press "Save changes"
    Then I should see "Title 1 - edit"
    And I should see "Message 1 - edit"
    And I click on "Edit Title 5" "icon" in the "article:contains('Title 5')" "css_element"
    And I wait until the page is ready
    And I set the following fields to these values:
      | Title | Title 5 - edit   |
      | Text  | Message 5 - edit |
    And I upload "blocks/news/tests/fixtures/kitten1.jpg" file to "Attachments" filemanager
    And I press "Save changes"
    And I should see "Title 5 - edit"
    And I should see "Message 5 - edit"
    And I should see "kitten1.jpg" in the ".news-message-attachments" "css_element"

  Scenario: Delete a news message using the delete icon
    Given the following news messages exist on course "C1":
      | title   | message   |
      | Title 1 | Message 1 |
      | Title 2 | Message 2 |
      | Title 3 | Message 3 |
      | Title 4 | Message 4 |
      | Title 5 | Message 5 |
    And I reload the page
    When I click on "View all" "link" in the "(new News block)" "block"
    And I click on "Delete Title 2" "icon" in the "article:contains('Title 2')" "css_element"
    Then I should see "Are you sure you want to delete the message 'Title 2'? This action cannot be undone"
    And I press "Cancel"
    And I should see "Title 2"
    And I should see "Message 2"
    And I click on "Delete Title 2" "icon" in the "article:contains('Title 2')" "css_element"
    And I press "Continue"
    And I should not see "Title 2"
    And I should not see "Message 2"
    And I click on "Delete Title 4" "icon" in the "article:contains('Title 4')" "css_element"
    And I should see "Are you sure you want to delete the message 'Title 4'? This action cannot be undone"
    And I press "Continue"
    And I should not see "Title 4"
    And I should not see "Message 4"

  @_file_upload
  Scenario: Verify multiple files can be uploaded to a news message
    Given I click on "Add" "link" in the "(new News block)" "block"
    When I set the following fields to these values:
      | Title | Title 1        |
      | Text  | Message text 1 |
    Then I upload "blocks/news/tests/fixtures/kitten1.jpg" file to "Attachments" filemanager
    And I upload "blocks/news/tests/fixtures/feed.xml" file to "Attachments" filemanager
    And I upload "blocks/news/tests/fixtures/attachment.txt" file to "Attachments" filemanager
    And I press "Save changes"
    And I click on "View all" "link" in the "(new News block)" "block"
    And I should see "Title 1" in the ".block_news_message" "css_element"
    And I should see "Message text 1" in the ".block_news_message" "css_element"
    And I should see "kitten1.jpg" in the ".news-message-attachments" "css_element"
    And I should see "feed.xml" in the ".news-message-attachments" "css_element"
    And I should see "attachment.txt" in the ".news-message-attachments" "css_element"

  Scenario: Verify items are separated into Events and News in the block
    Given I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    When I set the field "Separate into events and news items" to "Yes"
    And I press "Save changes"
    Then I should see "No news messages have been posted to this website." in the "News and events" "block"
    And I should see "Upcoming events" in the "News and events" "block"
    And I should see "There are no upcoming events to display." in the "News and events" "block"

    And I click on "View all" "link" in the "News and events" "block"
    And I should see "News and events"
    And I should see "No news messages have been posted to this website."
    And I should see "Upcoming events"
    And I should see "There are no upcoming events to display."
    And I should see "Past events"
    And I should see "There are no past events to display"

    And I am on "Course 1" course homepage
    And I open the "News and events" blocks action menu
    And I follow "Configure News and events block"
    And I set the field "Separate into events and news items" to "No"
    And I press "Save changes"
    And I should see "There is no news yet" in the "News and events" "block"

  Scenario: Verify that only the latest message and event is displayed
    Given I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the field "Separate into events and news items" to "Yes"
    And I press "Save changes"
    And the following news messages exist on course "C1":
      | title  | message | messagetype | messagedate | eventstart |
      | news1  | news1   | 1           | 1483228800  |            |
      | news2  | news2   | 1           | 1483228801  |            |
      | news3  | news3   | 1           | 1483228802  |            |
      | news4  | news4   | 1           | 1483228803  |            |
      | news5  | news5   | 1           | 1483228804  |            |
      | event1 | event1  | 2           | 1483228800  | 2145924000 |
      | event2 | event2  | 2           | 1483228801  | 2145924001 |
      | event3 | event3  | 2           | 1483228802  | 2145924002 |
      | event4 | event5  | 2           | 1483228803  | 2145924003 |
      | event5 | event5  | 2           | 1483228804  | 2145924004 |
    And I reload the page
    When I open the "News and events" blocks action menu
    And I follow "Configure News and events block"
    And I set the field "Show messages" to "Latest message only"
    And I press "Save changes"
    And I wait until the page is ready

    Then I should see "news5" in the "News and events" "block"
    And I should not see "news1" in the "News and events" "block"
    And I should not see "news2" in the "News and events" "block"
    And I should not see "news3" in the "News and events" "block"
    And I should not see "news4" in the "News and events" "block"

    And I should see "event1" in the "News and events" "block"
    And I should not see "event5" in the "News and events" "block"
    And I should not see "event4" in the "News and events" "block"
    And I should not see "event3" in the "News and events" "block"
    And I should not see "event2" in the "News and events" "block"

  Scenario: Verify that only the 2 most recent messages and events are displayed (default setting)
    Given I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the field "Separate into events and news items" to "Yes"
    And I press "Save changes"
    And the following news messages exist on course "C1":
      | title  | message | messagetype | messagedate | eventstart |
      | news1  | news1   | 1           | 1483228800  |            |
      | news2  | news2   | 1           | 1483228801  |            |
      | news3  | news3   | 1           | 1483228802  |            |
      | news4  | news4   | 1           | 1483228803  |            |
      | news5  | news5   | 1           | 1483228804  |            |
      | event1 | event1  | 2           | 1483228800  | 2145924000 |
      | event2 | event2  | 2           | 1483228801  | 2145924001 |
      | event3 | event3  | 2           | 1483228802  | 2145924002 |
      | event4 | event5  | 2           | 1483228803  | 2145924003 |
      | event5 | event5  | 2           | 1483228804  | 2145924004 |
    And I reload the page
    And I wait until the page is ready

    Then I should see "news5" in the "News and events" "block"
    And I should see "news4" in the "News and events" "block"
    And I should not see "news3" in the "News and events" "block"
    And I should not see "news1" in the "News and events" "block"
    And I should not see "news2" in the "News and events" "block"

    And I should see "event1" in the "News and events" "block"
    And I should see "event2" in the "News and events" "block"
    And I should not see "event5" in the "News and events" "block"
    And I should not see "event4" in the "News and events" "block"
    And I should not see "event3" in the "News and events" "block"

  Scenario: Verify that only the 3 most recent messages and events are displayed
    Given I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the field "Separate into events and news items" to "Yes"
    And I press "Save changes"
    And the following news messages exist on course "C1":
      | title  | message | messagetype | messagedate | eventstart |
      | news1  | news1   | 1           | 1483228800  |            |
      | news2  | news2   | 1           | 1483228801  |            |
      | news3  | news3   | 1           | 1483228802  |            |
      | news4  | news4   | 1           | 1483228803  |            |
      | news5  | news5   | 1           | 1483228804  |            |
      | event1 | event1  | 2           | 1483228800  | 2145924000 |
      | event2 | event2  | 2           | 1483228801  | 2145924001 |
      | event3 | event3  | 2           | 1483228802  | 2145924002 |
      | event4 | event5  | 2           | 1483228803  | 2145924003 |
      | event5 | event5  | 2           | 1483228804  | 2145924004 |
    And I reload the page
    When I open the "News and events" blocks action menu
    And I follow "Configure News and events block"
    And I set the field "Show messages" to "3 most recent"
    And I press "Save changes"
    And I wait until the page is ready

    Then I should see "news3" in the "News and events" "block"
    And I should see "news4" in the "News and events" "block"
    And I should see "news5" in the "News and events" "block"
    And I should not see "news1" in the "News and events" "block"
    And I should not see "news2" in the "News and events" "block"

    And I should see "event1" in the "News and events" "block"
    And I should see "event3" in the "News and events" "block"
    And I should see "event2" in the "News and events" "block"
    And I should not see "event5" in the "News and events" "block"
    And I should not see "event4" in the "News and events" "block"

  Scenario: Verify that only the 4 most recent messages and events are displayed
    Given I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the field "Separate into events and news items" to "Yes"
    And I press "Save changes"
    And the following news messages exist on course "C1":
      | title  | message | messagetype | messagedate | eventstart |
      | news1  | news1   | 1           | 1483228800  |            |
      | news2  | news2   | 1           | 1483228801  |            |
      | news3  | news3   | 1           | 1483228802  |            |
      | news4  | news4   | 1           | 1483228803  |            |
      | news5  | news5   | 1           | 1483228804  |            |
      | event1 | event1  | 2           | 1483228800  | 2145924000 |
      | event2 | event2  | 2           | 1483228801  | 2145924001 |
      | event3 | event3  | 2           | 1483228802  | 2145924002 |
      | event4 | event5  | 2           | 1483228803  | 2145924003 |
      | event5 | event5  | 2           | 1483228804  | 2145924004 |
    And I reload the page
    When I open the "News and events" blocks action menu
    And I follow "Configure News and events block"
    And I set the field "Show messages" to "4 most recent"
    And I press "Save changes"
    And I wait until the page is ready

    Then I should see "news2" in the "News and events" "block"
    And I should see "news3" in the "News and events" "block"
    And I should see "news4" in the "News and events" "block"
    And I should see "news5" in the "News and events" "block"
    And I should not see "news1" in the "News and events" "block"

    And I should see "event1" in the "News and events" "block"
    And I should see "event2" in the "News and events" "block"
    And I should see "event3" in the "News and events" "block"
    And I should see "event4" in the "News and events" "block"
    And I should not see "event5" in the "News and events" "block"

  Scenario: Verify that only the 5 most recent messages and events are displayed
    Given I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the field "Separate into events and news items" to "Yes"
    And I press "Save changes"
    And the following news messages exist on course "C1":
      | title  | message | messagetype | messagedate | eventstart |
      | news1  | news1   | 1           | 1483228800  |            |
      | news2  | news2   | 1           | 1483228801  |            |
      | news3  | news3   | 1           | 1483228802  |            |
      | news4  | news4   | 1           | 1483228803  |            |
      | news5  | news5   | 1           | 1483228804  |            |
      | event1 | event1  | 2           | 1483228800  | 2145924000 |
      | event2 | event2  | 2           | 1483228801  | 2145924001 |
      | event3 | event3  | 2           | 1483228802  | 2145924002 |
      | event4 | event5  | 2           | 1483228803  | 2145924003 |
      | event5 | event5  | 2           | 1483228804  | 2145924004 |
    And I reload the page
    When I open the "News and events" blocks action menu
    And I follow "Configure News and events block"
    And I set the field "Show messages" to "5 most recent"
    And I press "Save changes"
    And I wait until the page is ready

    Then I should see "news2" in the "News and events" "block"
    And I should see "news3" in the "News and events" "block"
    And I should see "news4" in the "News and events" "block"
    And I should see "news5" in the "News and events" "block"
    And I should see "news1" in the "News and events" "block"

    And I should see "event1" in the "News and events" "block"
    And I should see "event2" in the "News and events" "block"
    And I should see "event3" in the "News and events" "block"
    And I should see "event4" in the "News and events" "block"
    And I should see "event5" in the "News and events" "block"
