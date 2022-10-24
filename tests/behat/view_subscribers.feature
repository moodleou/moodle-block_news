@ou @ou_vle @block @block_news @javascript
Feature: View subscribers
  In order to view subscribers with extra columns
  As a user
  I need to be able to view subscribers with extra columns

  Scenario: Check user identity in list news subscribers
    Given the following "courses" exist:
      | fullname | shortname | format      |
      | Course 1 | C1        | oustudyplan |
    And the following "users" exist:
      | username   | firstname | lastname | email                  | phone1     | profile_field_oucu | profile_field_staffid |
      | student100 | Student   | 100      | student100@example.com | 1234567892 | st111              | S100                  |
    And the following "course enrolments" exist:
      | user       | course | role    |
      | student100 | C1     | student |
    And the following config values are set as admin:
      | showuseridentity | username,email,phone1,profile_field_oucu,profile_field_staffid|
    And I am using the OSEP theme
    And I log in as "admin" (in the OSEP theme)
    And I am on the "Course 1" "Course" page
    And I turn editing mode on in the OSEP theme
    And I set the field "Add a block" to "News"
    And I log out (in the OSEP theme)
    And I am on the "Course 1" "Course" page logged in as "student100"
    And I follow "News"
    And I press "Subscribe to news"
    Then I log out (in the OSEP theme)
    And I log in as "admin" (in the OSEP theme)
    And I am on the "Course 1" "Course" page
    And I follow "News"
    And I press "View subscribers"
    And I should see "Username"
    And I should see "student100"
    And I should see "Email address"
    And I should see "student100@example.com"
    And I should see "Phone"
    And I should see "1234567892"
    And I should see "OUCU"
    And I should see "st111"
    And I should see "Staff ID"
    And I should see "S100"
