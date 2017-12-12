@enrol @iplus @enrol_coursecompleted @javascript
Feature: Enrolment on course completion

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher | 1 |
      | student1 | Student | 1 |
    And I log in as "admin"
    And I navigate to "Manage enrol plugins" node in "Site administration > Plugins > Enrolments"
    And I click on "Disable" "link" in the "Guest access" "table_row"
    And I click on "Disable" "link" in the "Self enrolment" "table_row"
    And I click on "Disable" "link" in the "Cohort sync" "table_row"
    And I click on "Enable" "link" in the "Course completed enrolment" "table_row"
    And I navigate to "Manage enrol plugins" node in "Site administration > Plugins > Enrolments"
    And I click on "Settings" "link" in the "Course completed enrolment" "table_row"
    And I set the following fields to these values:
      | i+ offline payment      | Yes              |
    And I press "Save changes"
    And I create a course with:
      | Course full name | Course 1 |
      | Course short name | C1 |
      | Course ID number | ENSCN001 |
      | Course summary | This course has been created by automated tests. |
      | startdate[day] | 1 |
      | startdate[month] | January |
      | startdate[year] | 2020 |
    And I enrol "Teacher 1" user as "Teacher"
    And I create a course with:
      | Course full name  | Course 2 |
      | Course short name | C2       |
    And I enrol "Teacher 1" user as "Teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    And I add "Course completed enrolment" enrolment method with:
      | enrolstartdate[enabled] | 1                |
      | enrolstartdate[day]     | 1                |
      | enrolstartdate[month]   | January          |
      | enrolstartdate[year]    | 2010             |
      | enrolstartdate[hour]    | 10               |
      | enrolstartdate[minute]  | 00               |
      | enrolenddate[enabled]   | 1                |
      | enrolenddate[day]       | 1                |
      | enrolenddate[month]     | January          |
      | enrolenddate[year]      | 2030             |
      | enrolenddate[hour]      | 10               |
      | enrolenddate[minute]    | 00               |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Attendance Register" to section "1" and I fill the form with:
      | Attendance Register name | Attendance mod |
    And I am on "Course 1" course homepage
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Edit" "link" in the "Bank transfer" "table_row"
    And I set the following fields to these values:
      | Offline Payment enabled | 1                |
      | enrolstartdate[enabled] | 0                |
      | enrolenddate[enabled]   | 1                |
      | enrolenddate[day]       | 1                |
      | enrolenddate[month]     | January          |
      | enrolenddate[year]      | 2030             |
      | enrolenddate[hour]      | 12               |
      | enrolenddate[minute]    | 30               |
    And I press "Save changes"
    And I am on "Course 2" course homepage
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Edit" "link" in the "Bank transfer" "table_row"
    And I set the following fields to these values:
      | Offline Payment enabled | 0                |
    And I press "Save changes"
    And I log out
    And I trigger cron
    And I wait until the page is ready
    
  Scenario: Allow iplus access
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Practical information"
    And I should see "Terms and conditions"
    And I should see "Cost: USD 100.00"
    And I should see "You can enrol until"
    And I should see "There are 2 options for payment."
    When I follow "Course programme"
    Then I should see "60 weeks"
    And "Printer-friendly version" "link" should exist
    When I follow "Printer-friendly version"
    And I wait until the page is ready
    Then I should see "60 weeks"
    
  Scenario: Allow iplus access
    Given I log in as "student1"
    And I am on "Course 1" course homepage 
    And I press "Bank transfer"
    And I wait until the page is ready
    Then I should see "Are you sure"
    And I follow "Continue"
    And I wait until the page is ready
    Then I should see "Thank you for registering"
    And I follow "Continue"
    Then I should see "You will be automatically enrolled in this course"
    