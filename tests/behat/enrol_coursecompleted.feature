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
      | teacher1 | Teacher   | 1        |
    And the following "course enrolments" exist:
      | user    | course   | role           |
      | user1   | C1       | student        |
      | teacher1| C1       | editingteacher |
      | teacher1| C2       | editingteacher |
    And I log in as "admin"
    And I navigate to "Plugins > Enrolments > Manage enrol plugins" in site administration
    And I click on "Disable" "link" in the "Guest access" "table_row"
    And I click on "Disable" "link" in the "Self enrolment" "table_row"
    And I click on "Disable" "link" in the "Cohort sync" "table_row"
    And I click on "Enable" "link" in the "Course completed enrolment" "table_row"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the field "Teacher" to "1"
    And I press "Save changes"
    And I am on "Course 2" course homepage
    And I navigate to "Users > Enrolment methods" in current page administration
    And I select "Course completed enrolment" from the "Add method" singleselect

  Scenario: Duration
    When I set the following fields to these values:
       | Course                    | Course 1   |
       | id_enrolperiod_enabled    | 1          |
       | id_enrolperiod_number     | 3 days     |
       | id_enrolstartdate_enabled | 1          |
       | id_enrolstartdate_day     | 1          |
       | id_enrolstartdate_month   | 1          |
       | id_enrolstartdate_year    | 2030       |
    And I press "Add method"
    And I am on "Course 2" course homepage
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Course completion" in current page administration
    And I follow "Click to mark user complete"
    And I wait until the page is ready
    And I run the scheduled task "core\task\completion_regular_task"
    And I run all adhoc tasks
    And I wait until the page is ready
    And I am on "Course 2" course homepage
    And I wait until the page is ready
    And I follow "Participants"
    And I open the autocomplete suggestions list
    And I click on "Role: Student" item in the autocomplete list
    When I click on "//a[@title='Edit']" "xpath_element"
    Then I should see "2030"
    And I should see "4"
    And I log out

  Scenario: Later start date
    When I set the following fields to these values:
       | Course                    | Course 1   |
       | id_enrolperiod_enabled    | 1          |
       | id_enrolperiod_number     | 3 days     |
       | id_enrolstartdate_enabled | 1          |
       | id_enrolstartdate_year    | 2030       |

    And I press "Add method"
    And I am on "Course 2" course homepage
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Course completion" in current page administration
    And I follow "Click to mark user complete"
    And I wait until the page is ready
    And I run the scheduled task "core\task\completion_regular_task"
    And I run all adhoc tasks
    And I wait until the page is ready
    And I log out
    And I log in as "user1"
    And I am on "Course 2" course homepage
    And I wait until the page is ready
    Then I should see "You will be enrolled in this course when"
    And I log out

  Scenario: When a course is completed, a user is auto enrolled into another course
    When I set the following fields to these values:
       | Course                    | Course 1   |
    And I press "Add method"
    And I am on "Course 2" course homepage
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Course completion" in current page administration
    And I follow "Click to mark user complete"
    And I wait until the page is ready
    And I log out
    And I log in as "admin"
    And I run the scheduled task "core\task\completion_regular_task"
    And I wait until the page is ready
    And I run all adhoc tasks
    And I wait until the page is ready
    And I log out
    And I log in as "user1"
    And I wait until the page is ready
    And I am on "Course 1" course homepage
    Then I should not see "You will be enrolled in this course when"
    And I am on "Course 2" course homepage
    Then I should not see "You will be enrolled in this course when"
    And I log out

  Scenario: Manage enrolled users
    When I set the following fields to these values:
       | Course                    | Course 1   |
    And I press "Add method"
    And I am on "Course 2" course homepage
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Reports > Course completion" in current page administration
    And I wait until the page is ready
    And I follow "Click to mark user complete"
    And I wait until the page is ready
    And I log out
    And I log in as "admin"
    And I run the scheduled task "core\task\completion_regular_task"
    And I wait until the page is ready
    And I run all adhoc tasks
    And I wait until the page is ready
    And I log out
    And I log in as "teacher1"
    And I am on "Course 2" course homepage
    And I wait until the page is ready
    And I follow "Participants"
    And I wait until the page is ready
    Then I should see "Username 1" in the "participants" "table"
    And I log out
    And I log in as "admin"
    And I am on "Course 2" course homepage
    And I wait until the page is ready
    And I follow "Participants"
    And I wait until the page is ready
    When I click on "//a[@data-action='unenrol']" "xpath_element"
    And I click on "Unenrol" "button" in the "Unenrol" "dialogue"
    And I click on "//a[@title='Unenrol']" "xpath_element"
    And I click on "Continue" "button"
    And I wait until the page is ready
    Then I should not see "Username 1"
    And I should not see "Teacher 1"
    When I am on "Course 2" course homepage
    And I navigate to "Users > Enrolment methods" in current page administration
    And I wait until the page is ready
    And I click on "[aria-label='Enrol users']" "css_element" in the "tr.lastrow" "css_element"
    Then I should see "Username 1"
    And I press "Enrol users"
    Then I should see "1 Users enrolled"
    And I am on "Course 2" course homepage
    And I follow "Participants"
    And I wait until the page is ready
    Then I should see "Username 1" in the "participants" "table"
    And I log out
