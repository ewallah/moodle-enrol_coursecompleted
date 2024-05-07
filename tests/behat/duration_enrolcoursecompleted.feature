@enrol @ewallah @enrol_coursecompleted @javascript
Feature: Duration Enrolment on course completion

  Background:
    Given the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
      | Course 2 | C2        | 1                |
      | Course 3 | C3        | 1                |
    And the following "users" exist:
      | username |
      | user1    |
      | teacher1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
      | teacher1 | C3     | editingteacher |
    And the following config values are set as admin:
      | expiredaction | Unenrol user from course | enrol_coursecompleted |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" in current page administration
    And I expand all fieldsets
    And I set the field "Teacher" to "1"
    And I press "Save changes"
    And I add "Course completed enrolment" enrolment method in "Course 2" with:
       | Course                  | Course 1 |
       | id_enrolperiod_enabled  | 1        |
       | id_enrolperiod_number   | 3        |
       | id_enrolperiod_timeunit | seconds  |
    And I add "Course completed enrolment" enrolment method in "Course 3" with:
       | Course                  | Course 1 |
       | id_customint4_enabled   | 1        |
       | id_customint4_year      | 2030     |
    And I log out

  Scenario: Guest users see basic coursecompleted enrolment info.
    Given I log in as "guest"
    And I am on course index
    When I follow "Course 2"
    Then I should see "You will be enrolled in this course when you complete course"

  Scenario: Normal students see basic coursecompleted enrolment info.
    Given I log in as "user1"
    And I am on course index
    When I follow "Course 2"
    Then I should see "You will be enrolled in this course when you complete course"

  Scenario: Course completion with duration set
    Given I am on the "C2" "Course" page logged in as "teacher1"
    And I navigate to course participants
    And I should see "1 participants found"
    And I am on "Course 2" course homepage
    And I am on the "C1" "Course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    When I follow "Click to mark user complete"
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"
    And I am on "Course 2" course homepage
    And I navigate to course participants
    Then I should see "2 participants found"
    And I wait "4" seconds
    And I run the scheduled task "\enrol_coursecompleted\task\process_expirations"
    And I am on "Course 2" course homepage
    And I navigate to course participants
    And I should see "2 participants found"
    But I should see "Not current"

  Scenario: Course completion with future date set
    Given I am on the "C1" "Course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Course completion" "link" in the "region-main" "region"
    When I follow "Click to mark user complete"
    And I log out
    And I wait "1" seconds
    And I run the scheduled task "core\task\completion_regular_task"

    # There should be an adhoc task for future enrolments in course 3.
    And I log in as "admin"
    And I run all adhoc tasks
    And I navigate to "Server > Tasks > Ad hoc tasks" in site administration
    Then I should see "2030" in the "process_future" "table_row"
    And I am on the "Course 3" "enrolment methods" page
    And I click on "Edit" "link" in the "After completing course: C1" "table_row"
    And I set the field "Enabled" to "No"
    And I click on "Save" "button"
    And I navigate to "Development > Purge caches" in site administration
    And I follow "Purge all caches"
    And I trigger cron
    And I run all adhoc tasks
    And I am on course index
    And I am on the "Course 3" "enrolment methods" page
    And I navigate to "Server > Tasks > Ad hoc tasks" in site administration
    But I should not see "2030"
