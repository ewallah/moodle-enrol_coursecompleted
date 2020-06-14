@enrol @ewallah @enrol_coursecompleted @javascript
Feature: Duration Enrolment on course completion

  Background:
    Given the following "courses" exist:
      | fullname | shortname | numsections | startdate | enddate | enablecompletion |
      | Course 1 | C1 | 1 | ##yesterday## | ##tomorrow## | 1 |
      | Course 2 | C2 | 1 | ##yesterday## | ##tomorrow## | 1 |
    And the following "users" exist:
      | username | firstname | lastname |
      | user1 | Username | 1 |
      | user2 | Username | 2 |
      | teacher1 | Teacher | 1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | user1 | C1 | student |
      | teacher1| C1 | editingteacher |
      | teacher1| C2 | editingteacher |
    And the following config values are set as admin:
      | expiredaction | Unenrol user from course | enrol_coursecompleted |
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

  Scenario: Course completion with duration set
    When I set the following fields to these values:
       | Course | Course 1 |
       | id_enrolperiod_enabled | 1 |
       | id_enrolperiod_number | 2 |
       | id_enrolperiod_timeunit | seconds |
    And I press "Add method"
    And I am on "Course 2" course homepage
    And I log out
    And I am on the "C1" "Course" page logged in as "teacher1"
    And I navigate to "Reports > Course completion" in current page administration
    And I follow "Click to mark user complete"
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"
    And I run all adhoc tasks
    And I am on "Course 2" course homepage
    And I navigate to course participants
    Then I should see "Username 1"
    And I should not see "Not current"
    And I wait "2" seconds
    And I run the scheduled task "\enrol_coursecompleted\task\process_expirations"
    And I am on "Course 2" course homepage
    And I navigate to course participants
    Then I should see "Not current"
    And I log out
    And I am on the "C2" "Course" page logged in as "user1"
    Then I should see "You will be enrolled in this course when you complete course"

  Scenario: Course completion with end date set
    When I set the following fields to these values:
       | Course | Course 1 |
       | id_enrolenddate_enabled | 1 |
    And I press "Add method"
    And I am on "Course 2" course homepage
    And I log out
    And I am on the "C1" "Course" page logged in as "teacher1"
    And I navigate to "Reports > Course completion" in current page administration
    And I follow "Click to mark user complete"
    # Running completion task just after clicking sometimes fail, as record
    # should be created before the task runs.
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"
    And I run all adhoc tasks
    And I am on "Course 2" course homepage
    And I navigate to course participants
    Then I should see "Username 1"
    And I run the scheduled task "enrol_coursecompleted\task\process_expirations"
    And I trigger cron
    And I am on "Course 2" course homepage
    And I navigate to course participants
    Then I should see "Username 1"
    And I log out
    And I am on the "C2" "Course" page logged in as "user1"
    Then I should see "You will be enrolled in this course when you complete course"
