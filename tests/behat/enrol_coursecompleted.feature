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
    And I create a course with:
      | Course full name | Course 1 |
      | Course short name | C1 |
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
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    And I am on "Course 2" course homepage
    And I log out
    And I trigger cron
    And I wait until the page is ready
    
  Scenario: Allow enrolment access
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    
  