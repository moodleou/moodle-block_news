@ou @ou_vle @block @block_news
Feature: News message restriction by grouping or group
  In order to restrict access to message
  As a teacher
  I need to be able to add group or grouping restrict to a message

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname |
      | teacher1 | ANNE      |
      | student1 | BOB       |
      | student2 | TOM       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "groups" exist:
      | name | description | course | idnumber |
      | Group 1 | Group description | C1 | GROUP1 |
      | Group 2 | Group description | C1 | GROUP2 |
    And the following "groupings" exist:
      | name | course | idnumber |
      | Grouping 1 | C1 | GROUPING1 |
    # Add student 1 to Group 1, student 2 to Group 2.
    And the following "group members" exist:
      | user        | group |
      | student1    | GROUP1 |
      | student2    | GROUP2 |
    And the following "grouping groups" exist:
      | grouping | group |
      | GROUPING1 | GROUP1 |
    # Add block.
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add the "News" block

  @javascript
  Scenario: Set Enable message restriction in Block News configuration to Grouping and set Grouping
            to a message to restrict user.
    # Go to settings page. Set Enable message restriction to Grouping.
    Given I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the following fields to these values:
      | Enable message restriction | Grouping |
    And I press "Save changes"

    # Post message and set grouping is grouping 1.
    When I click on "Add" "link" in the "(new News block)" "block"
    And I set the following fields to these values:
      | Title | Short message title 1 |
      | Text  | Message1 text |
      | Grouping | Grouping 1 |
    And I press "Save changes"

    # Teacher see the grouping indication.
    Then I should see "Short message title 1"
    Then I should see "Not available unless: You belong to Grouping 1"

    # Student 1 in grouping 1 see the message 1.
    And I log out
    When I log in as "student1"
    And I follow "Course 1"
    Then I should see "Short message title 1"

    # Student 2 do not see the message 1.
    And I log out
    When I log in as "student2"
    And I follow "Course 1"
    Then I should not see "Short message title 1"

  Scenario: Set Enable message restriction in Block News configuration to Group and set Group to a
            message to restrict user.
    # Go to settings page. Set Enable message restriction to Group.
    Given I open the "(new News block)" blocks action menu
    And I follow "Configure (new News block) block"
    And I set the following fields to these values:
      | Enable message restriction | Group |
    And I press "Save changes"

    # Post message and set group is group 2.
    When I click on "Add" "link" in the "(new News block)" "block"
    And I set the following fields to these values:
      | Title | Short message title 2 |
      | Text  | Message2 text |
      | Group | Group 2 |
    And I press "Save changes"

    # Teacher see the group indication.
    Then I should see "Short message title 2"
    Then I should see "Not available unless: You belong to Group 2"

    # Student 1 in group 1 do not see the message 2.
    And I log out
    When I log in as "student1"
    And I follow "Course 1"
    Then I should not see "Short message title 2"

    # Student 2 see the message 2.
    And I log out
    When I log in as "student2"
    And I follow "Course 1"
    Then I should see "Short message title 2"
