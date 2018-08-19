@enrol @ewallah @enrol_coursecompleted @javascript
Feature: Enrolment on course completion

  Background:
    Given the following "courses" exist:
      | fullname | shortname | numsections | startdate  | enddate    | enablecompletion |
      | Course 1 | C1        | 1           | 957139200  | 960163200  | 1                |
      | Course 2 | C2        | 1           | 2524644000 | 2529741600 | 0                |
    And the following "users" exist:
      | username | firstname | lastname |
      | user1    | Username  | 1        |
      | user2    | Username  | 2        |
      | teacher  | Teacher   | 1        |
    And the following "course enrolments" exist:
      | user    | course   | role           |
      | user1   | C1       | student        |
      | teacher | C1       | editingteacher |
      | teacher | C2       | editingteacher |
    And I log in as "admin"
    And I navigate to "Manage enrol plugins" node in "Site administration > Plugins > Enrolments"
    And I click on "Disable" "link" in the "Guest access" "table_row"
    And I click on "Disable" "link" in the "Self enrolment" "table_row"
    And I click on "Disable" "link" in the "Cohort sync" "table_row"
    And I click on "Enable" "link" in the "Course completed enrolment" "table_row"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Teacher" to "1"
    And I press "Save changes"
    And I am on "Course 2" course homepage
    And I navigate to "Enrolment methods" node in "Course administration > Users"

  Scenario: Later start date
    Given I add "Course completed enrolment" enrolment method with:
       | Course                    | Course 1   |
       | id_enrolperiod_enabled    | 1          |
       | id_enrolperiod_number     | 30         |
       | id_enrolstartdate_enabled | 1          |
       | id_enrolstartdate_year    | 2020       |
    And I log out
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I follow "Click to mark user complete"
    And I wait until the page is ready
    And I run the scheduled task "core\task\completion_regular_task"
    And I run all adhoc tasks
    And I wait until the page is ready
    And I log out
    And I log in as "user1"
    And I wait until the page is ready
    And I am on "Course 2" course homepage
    Then I should see "You will be enrolled in this course when"

  Scenario: When a course is completed, a user is auto enrolled into another course
    Given I add "Course completed enrolment" enrolment method with:
       | Course | Course 1 |
    And I log out
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I follow "Click to mark user complete"
    And I wait until the page is ready
    And I run the scheduled task "core\task\completion_regular_task"
    And I run all adhoc tasks
    And I wait until the page is ready
    And I run the scheduled task "core\task\completion_regular_task"
    And I run all adhoc tasks
    And I wait until the page is ready
    And I log out
    And I log in as "user1"
    And I wait until the page is ready
    And I am on "Course 1" course homepage
    Then I should not see "You will be enrolled in this course when"
    And I am on "Course 2" course homepage
    Then I should not see "You will be enrolled in this course when"

  Scenario: Manage enrolled users
    Given I add "Course completed enrolment" enrolment method with:
       | Course | Course 1 |
    And I log out
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I follow "Click to mark user complete"
    And I wait until the page is ready
    And I run the scheduled task "core\task\completion_regular_task"
    And I run all adhoc tasks
    And I run the scheduled task "core\task\completion_regular_task"
    And I run all adhoc tasks
    And I wait until the page is ready
    And I am on "Course 2" course homepage
    And I follow "Participants"
    Then I should see "Username 1" in the "participants" "table"
    And I log out
    And I log in as "admin"
    And I am on "Course 2" course homepage
    And I follow "Participants"
    When I click on "//a[@data-action='unenrol']" "xpath_element"
    And I click on "Unenrol" "button" in the "Unenrol" "dialogue"
    And I click on "//a[@title='Unenrol']" "xpath_element"
    And I click on "Continue" "button"
    Then I should not see "Username 1"
    And I should not see "Teacher 1"
    When I am on "Course 2" course homepage
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "[aria-label='Enrol users']" "css_element" in the "tr.lastrow" "css_element"
    Then I should see "Username 1"
    And I click on "Enrol users"
    Then I should see "1 Users enrolled"